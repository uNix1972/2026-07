<?php

namespace Dao;

class ClinicaAvanzada extends Table
{
    /**
     * Catálogo único de estados de cita. Antes existían dos fuentes de
     * verdad (CitasController solo conocía 1-5, DoctoresController movía
     * la cita a 6/7 sin que el módulo admin lo supiera). Todo el sistema
     * debe usar esta lista.
     */
    public const ESTADOS = [
        1 => 'Pendiente',
        2 => 'Confirmada',
        3 => 'Completada',
        4 => 'Cancelada',
        5 => 'No Asistió',
        6 => 'En Espera',
        7 => 'En Atención',
    ];

    /**
     * Transiciones permitidas desde cada estado. El flujo real de una
     * consulta es: Pendiente -> Confirmada (paga) -> En Espera (llega
     * físicamente) -> En Atención (el médico la inicia) -> Completada.
     * Cancelada/No Asistió pueden ocurrir mientras la cita sigue activa.
     * El módulo admin tiene un poco más de margen (puede saltar pasos para
     * corregir datos), el portal del doctor exige el orden estricto.
     */
    private const TRANSICIONES_ADMIN = [
        1 => [2, 4],
        2 => [3, 4, 5, 6],
        6 => [3, 4, 5, 7],
        7 => [3, 4],
    ];

    // El médico ya no marca "En Espera" (2 -> 6): ahora esa transición la
    // hace la enfermera/recepción desde el módulo de Citas. Al médico solo
    // le queda poder registrar que el paciente no llegó.
    private const TRANSICIONES_DOCTOR = [
        2 => [5],
        6 => [7, 5],
        7 => [3],
    ];

    public static function nombreEstado(int $estadoId): string
    {
        return self::ESTADOS[$estadoId] ?? 'Desconocido';
    }

    public static function puedeTransicionarAdmin(int $actual, int $nuevo): bool
    {
        return in_array($nuevo, self::TRANSICIONES_ADMIN[$actual] ?? [], true);
    }

    public static function puedeTransicionarDoctor(int $actual, int $nuevo): bool
    {
        return in_array($nuevo, self::TRANSICIONES_DOCTOR[$actual] ?? [], true);
    }

    /**
     * Cancela automáticamente citas Pendientes (nunca confirmadas/pagadas)
     * cuya hora ya pasó hace más de una hora. Devuelve los IDs cancelados
     * para que quien la invoque pueda auditar el cambio.
     *
     * El límite se calcula en PHP (con date(), que respeta la zona horaria
     * configurada en parameters.env) en vez de usar NOW()/DATE_SUB() de
     * MySQL. La conexión a la base de datos no fija su propia zona horaria
     * (ver Dao.php), así que MySQL puede estar corriendo en UTC mientras la
     * app trabaja en hora de Honduras; comparar contra un valor calculado
     * en PHP evita ese desfase.
     */
    public static function autoCancelarPendientesVencidas(): array
    {
        $limite = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $vencidas = parent::obtenerRegistros(
            "SELECT id FROM cita
             WHERE estado_id = 1
               AND fecha_hora <= :limite",
            ['limite' => $limite]
        );
        if ($vencidas) {
            parent::executeNonQuery(
                "UPDATE cita SET estado_id = 4
                 WHERE estado_id = 1
                   AND fecha_hora <= :limite",
                ['limite' => $limite]
            );
        }
        return array_map(static fn (array $r): int => intval($r['id']), $vencidas);
    }

    public static function getMedicoByUsuario(int $usuarioId): ?array
    {
        $sql = "SELECT m.*, e.nombre_especialidad
                FROM medico m
                LEFT JOIN especialidad e ON m.especialidad_id = e.id
                WHERE m.usuario_id = :usuario_id
                LIMIT 1";
        $row = parent::obtenerUnRegistro($sql, ['usuario_id' => $usuarioId]);
        return $row ?: null;
    }

    public static function getPacienteByUsuario(int $usuarioId): ?array
    {
        $sql = "SELECT * FROM paciente WHERE usuario_id = :usuario_id LIMIT 1";
        $row = parent::obtenerUnRegistro($sql, ['usuario_id' => $usuarioId]);
        return $row ?: null;
    }

    /**
     * Obtiene la agenda completa del médico con la ubicación de cada cita.
     */
    public static function getAgendaDoctor(int $medicoId): array
    {
        $sql = "SELECT c.*, p.nombres AS paciente_nombres, p.apellidos AS paciente_apellidos,
                       p.telefono AS paciente_telefono, ec.nombre_estado,
                       cs.nombre AS centro_nombre, c.consultorio,
                       sv.temperatura, sv.presion_sistolica, sv.presion_diastolica,
                       sv.frecuencia_cardiaca, sv.frecuencia_respiratoria,
                       sv.saturacion_oxigeno, sv.peso, sv.talla, sv.notas AS signos_notas
                FROM cita c
                INNER JOIN paciente p ON c.paciente_id = p.id
                INNER JOIN estado_cita ec ON c.estado_id = ec.id
                INNER JOIN centro_salud cs ON c.centro_salud_id = cs.id
                INNER JOIN medico_centro_salud mcs
                    ON mcs.medico_id = c.medico_id
                   AND mcs.centro_salud_id = c.centro_salud_id
                LEFT JOIN signos_vitales sv ON sv.cita_id = c.id
                WHERE c.medico_id = :medico_id
                ORDER BY c.fecha_hora ASC";
        return parent::obtenerRegistros($sql, ['medico_id' => $medicoId]);
    }

    /**
     * Obtiene la sala de espera de hoy con centro y consultorio.
     *
     * Solo incluye citas que el paciente ya confirmó que llegó (En Espera)
     * o que ya están en atención. Una cita solo "Confirmada" significa que
     * se pagó, no que el paciente esté físicamente en la clínica, así que
     * no pertenece a esta lista.
     *
     * "Hoy" se recibe ya calculado desde PHP (date('Y-m-d')) en vez de usar
     * CURDATE() de MySQL, porque la conexión no fija su zona horaria (ver
     * Dao.php) y puede quedar desfasada de la hora de Honduras que usa la
     * app, haciendo que citas de "hoy" no aparezcan aquí.
     */
    public static function getSalaEspera(int $medicoId, string $hoy): array
    {
        $sql = "SELECT c.*, p.nombres AS paciente_nombres, p.apellidos AS paciente_apellidos,
                       p.telefono AS paciente_telefono, ec.nombre_estado,
                       cs.nombre AS centro_nombre, c.consultorio,
                       sv.temperatura, sv.presion_sistolica, sv.presion_diastolica,
                       sv.frecuencia_cardiaca, sv.frecuencia_respiratoria,
                       sv.saturacion_oxigeno, sv.peso, sv.talla, sv.notas AS signos_notas
                FROM cita c
                INNER JOIN paciente p ON c.paciente_id = p.id
                INNER JOIN estado_cita ec ON c.estado_id = ec.id
                INNER JOIN centro_salud cs ON c.centro_salud_id = cs.id
                INNER JOIN medico_centro_salud mcs
                    ON mcs.medico_id = c.medico_id
                   AND mcs.centro_salud_id = c.centro_salud_id
                LEFT JOIN signos_vitales sv ON sv.cita_id = c.id
                WHERE c.medico_id = :medico_id
                  AND c.estado_id IN (6, 7)
                  AND DATE(c.fecha_hora) = :hoy
                ORDER BY c.fecha_hora ASC";
        return parent::obtenerRegistros($sql, ['medico_id' => $medicoId, 'hoy' => $hoy]);
    }

    /**
     * Un médico solo debe poder tener una cita "En Atención" (7) a la vez;
     * antes de dejarlo iniciar otra, hay que confirmar que no tenga ya
     * una consulta activa en curso.
     */
    public static function getCitaEnAtencion(int $medicoId, int $excludeCitaId): ?array
    {
        $sql = "SELECT c.id, c.fecha_hora, p.nombres AS paciente_nombres,
                       p.apellidos AS paciente_apellidos
                FROM cita c
                INNER JOIN paciente p ON p.id = c.paciente_id
                WHERE c.medico_id = :medico_id
                  AND c.estado_id = 7
                  AND c.id != :exclude_id
                LIMIT 1";
        $resultado = parent::obtenerUnRegistro($sql, [
            'medico_id' => $medicoId,
            'exclude_id' => $excludeCitaId,
        ]);
        return $resultado ?: null;
    }

    public static function actualizarEstadoCita(int $citaId, int $estadoId): void
    {
        parent::executeNonQuery(
            "UPDATE cita SET estado_id = :estado_id WHERE id = :id",
            ['estado_id' => $estadoId, 'id' => $citaId]
        );
    }

    /**
     * Igual que actualizarEstadoCita(), pero atómico: bloquea la fila con
     * FOR UPDATE y confirma que la cita sigue en el estado que el
     * llamador validó momentos antes antes de escribir.
     *
     * Sin esto, dos solicitudes casi simultáneas sobre la misma cita
     * (doble clic, dos pestañas) podían leer el mismo estado "viejo",
     * pasar las dos la validación en PHP, y las dos escribir: se
     * duplicaba la notificación y el registro de auditoría aunque el
     * resultado final (el estado) fuera el mismo.
     *
     * @return bool true si el cambio se aplicó; false si la cita ya no
     *         estaba en el estado esperado (alguien más la cambió primero
     *         mientras se esperaba el candado).
     */
    public static function actualizarEstadoCitaSiEstaba(
        int $citaId,
        int $estadoEsperado,
        int $estadoNuevo
    ): bool {
        $conn = self::getConn();
        $conn->beginTransaction();
        try {
            $cita = parent::obtenerUnRegistro(
                "SELECT estado_id FROM cita WHERE id = :id FOR UPDATE",
                ['id' => $citaId],
                $conn
            );
            if (!$cita || (int) $cita['estado_id'] !== $estadoEsperado) {
                $conn->rollBack();
                return false;
            }

            parent::executeNonQuery(
                "UPDATE cita SET estado_id = :estado_id WHERE id = :id",
                ['estado_id' => $estadoNuevo, 'id' => $citaId],
                $conn
            );
            $conn->commit();
            return true;
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Pone una cita "En Atención" (7) de forma atómica, incluyendo la
     * regla de "un médico solo puede tener una consulta activa a la vez".
     *
     * Antes, esa regla se validaba con una lectura simple (getCitaEnAtencion)
     * separada de la escritura: dos solicitudes de "Iniciar atención" sobre
     * DOS citas distintas del mismo médico, casi al mismo tiempo, podían
     * las dos leer "no tiene ninguna en curso" antes de que cualquiera
     * escribiera, y terminar con el médico "en atención" de dos pacientes
     * a la vez. Aquí se bloquea primero la fila del médico (como candado
     * general para ese médico) para que la segunda solicitud tenga que
     * esperar a que la primera termine, y entonces sí vea el estado real.
     *
     * @return array{ok:bool, motivo:?string, ocupadaCon:?array}
     */
    public static function iniciarAtencionSiPosible(int $citaId, int $medicoId): array
    {
        $conn = self::getConn();
        $conn->beginTransaction();
        try {
            parent::obtenerUnRegistro(
                "SELECT id FROM medico WHERE id = :id FOR UPDATE",
                ['id' => $medicoId],
                $conn
            );

            $cita = parent::obtenerUnRegistro(
                "SELECT estado_id FROM cita WHERE id = :id FOR UPDATE",
                ['id' => $citaId],
                $conn
            );
            if (!$cita || (int) $cita['estado_id'] !== 6) {
                $conn->rollBack();
                return ['ok' => false, 'motivo' => 'estado', 'ocupadaCon' => null];
            }

            $enAtencion = parent::obtenerUnRegistro(
                "SELECT c.id, p.nombres AS paciente_nombres, p.apellidos AS paciente_apellidos
                 FROM cita c
                 INNER JOIN paciente p ON p.id = c.paciente_id
                 WHERE c.medico_id = :medico_id
                   AND c.estado_id = 7
                   AND c.id != :cita_id
                 LIMIT 1",
                ['medico_id' => $medicoId, 'cita_id' => $citaId],
                $conn
            );
            if ($enAtencion) {
                $conn->rollBack();
                return ['ok' => false, 'motivo' => 'ocupado', 'ocupadaCon' => $enAtencion];
            }

            // hora_inicio_atencion se calcula en PHP (no con NOW() de MySQL)
            // por la misma razón que el resto de fechas del sistema: la
            // conexión no fija su propia zona horaria (ver Dao.php), así
            // que MySQL puede estar en UTC mientras la app trabaja en hora
            // de Honduras. Este valor alimenta el timer de "Iniciar
            // consulta" en el portal del doctor.
            parent::executeNonQuery(
                "UPDATE cita SET estado_id = 7, hora_inicio_atencion = :ahora WHERE id = :id",
                ['id' => $citaId, 'ahora' => date('Y-m-d H:i:s')],
                $conn
            );
            $conn->commit();
            return ['ok' => true, 'motivo' => null, 'ocupadaCon' => null];
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $e;
        }
    }

    public static function guardarHistorial(int $citaId, string $motivo, string $diagnostico, string $tratamiento, string $observaciones): int
    {
        $existe = parent::obtenerUnRegistro("SELECT id FROM historial_medico WHERE cita_id = :cita_id", ['cita_id' => $citaId]);
        if ($existe) {
            parent::executeNonQuery(
                "UPDATE historial_medico
                 SET motivo_consulta = :motivo, diagnostico = :diagnostico, tratamiento = :tratamiento, observaciones = :observaciones
                 WHERE cita_id = :cita_id",
                [
                    'cita_id' => $citaId,
                    'motivo' => $motivo,
                    'diagnostico' => $diagnostico,
                    'tratamiento' => $tratamiento,
                    'observaciones' => $observaciones,
                ]
            );
            return intval($existe['id']);
        }

        parent::executeNonQuery(
            "INSERT INTO historial_medico (cita_id, motivo_consulta, diagnostico, tratamiento, observaciones)
             VALUES (:cita_id, :motivo, :diagnostico, :tratamiento, :observaciones)",
            [
                'cita_id' => $citaId,
                'motivo' => $motivo,
                'diagnostico' => $diagnostico,
                'tratamiento' => $tratamiento,
                'observaciones' => $observaciones,
            ]
        );
        return intval(parent::getLastInsertId());
    }

    public static function guardarReceta(int $historialId, string $medicamento, string $indicaciones): int
    {
        parent::executeNonQuery(
            "INSERT INTO receta_medica (historial_id, medicamento, indicaciones)
             VALUES (:historial_id, :medicamento, :indicaciones)",
            [
                'historial_id' => $historialId,
                'medicamento' => $medicamento,
                'indicaciones' => $indicaciones,
            ]
        );
        return intval(parent::getLastInsertId());
    }

    public static function getHistorialPaciente(int $pacienteId): array
    {
        $sql = "SELECT h.*, c.fecha_hora, m.nombres AS medico_nombres, m.apellidos AS medico_apellidos,
                       e.nombre_especialidad
                FROM historial_medico h
                INNER JOIN cita c ON h.cita_id = c.id
                INNER JOIN medico m ON c.medico_id = m.id
                LEFT JOIN especialidad e ON m.especialidad_id = e.id
                WHERE c.paciente_id = :paciente_id
                ORDER BY h.fecha_registro DESC";
        return parent::obtenerRegistros($sql, ['paciente_id' => $pacienteId]);
    }

    public static function getRecetasPaciente(int $pacienteId): array
    {
        $sql = "SELECT r.*, h.diagnostico, c.fecha_hora, m.nombres AS medico_nombres, m.apellidos AS medico_apellidos
                FROM receta_medica r
                INNER JOIN historial_medico h ON r.historial_id = h.id
                INNER JOIN cita c ON h.cita_id = c.id
                INNER JOIN medico m ON c.medico_id = m.id
                WHERE c.paciente_id = :paciente_id
                ORDER BY r.fecha_emision DESC";
        return parent::obtenerRegistros($sql, ['paciente_id' => $pacienteId]);
    }

    /**
     * Pacientes que el médico ya atendió: poseen historia clínica o una cita
     * finalizada. Las citas futuras no agregan pacientes a esta lista.
     */
    public static function getPacientesAtendidosDoctor(int $medicoId): array
    {
        $sql = "SELECT p.id, p.identidad, p.nombres, p.apellidos, p.telefono,
                       COUNT(DISTINCT c.id) AS total_citas,
                       MAX(c.fecha_hora) AS ultima_cita
                FROM paciente p
                INNER JOIN cita c ON c.paciente_id = p.id
                LEFT JOIN historial_medico h ON h.cita_id = c.id
                WHERE c.medico_id = :medico_id
                  AND (h.id IS NOT NULL OR c.estado_id = 3)
                GROUP BY p.id
                ORDER BY ultima_cita DESC";
        return parent::obtenerRegistros($sql, ['medico_id' => $medicoId]);
    }

    /**
     * Expediente longitudinal de un paciente. Cuando se indica médico, limita
     * el resultado a las citas atendidas por ese profesional.
     */
    public static function getCitasExpedientePaciente(
        int $pacienteId,
        ?int $medicoId = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null
    ): array {
        $sql = "SELECT c.id, c.fecha_hora, c.medico_id, c.paciente_id,
                       c.centro_salud_id, c.estado_id, ec.nombre_estado,
                       m.nombres AS medico_nombres,
                       m.apellidos AS medico_apellidos,
                       e.nombre_especialidad, cs.nombre AS centro_nombre,
                       h.id AS historial_id, h.motivo_consulta,
                       h.diagnostico, h.tratamiento, h.observaciones,
                       sv.temperatura, sv.presion_sistolica,
                       sv.presion_diastolica, sv.frecuencia_cardiaca,
                       sv.frecuencia_respiratoria, sv.saturacion_oxigeno,
                       sv.peso, sv.talla, sv.notas AS signos_notas
                FROM cita c
                INNER JOIN medico m ON m.id = c.medico_id
                INNER JOIN centro_salud cs ON cs.id = c.centro_salud_id
                LEFT JOIN especialidad e ON e.id = m.especialidad_id
                INNER JOIN estado_cita ec ON ec.id = c.estado_id
                LEFT JOIN historial_medico h ON h.cita_id = c.id
                LEFT JOIN signos_vitales sv ON sv.cita_id = c.id
                WHERE c.paciente_id = :paciente_id
                  AND (h.id IS NOT NULL OR c.estado_id = 3)";
        $params = ['paciente_id' => $pacienteId];
        if ($medicoId !== null) {
            $sql .= " AND c.medico_id = :medico_id";
            $params['medico_id'] = $medicoId;
        }
        if ($fechaDesde !== null) {
            $sql .= " AND DATE(c.fecha_hora) >= :fecha_desde";
            $params['fecha_desde'] = $fechaDesde;
        }
        if ($fechaHasta !== null) {
            $sql .= " AND DATE(c.fecha_hora) <= :fecha_hasta";
            $params['fecha_hasta'] = $fechaHasta;
        }
        $sql .= " ORDER BY c.fecha_hora DESC";
        return parent::obtenerRegistros($sql, $params);
    }

    public static function getCitaExpediente(int $citaId): ?array
    {
        $sql = "SELECT c.id, c.fecha_hora, c.medico_id, c.paciente_id,
                       c.centro_salud_id, c.estado_id, ec.nombre_estado,
                       p.identidad, p.nombres AS paciente_nombres,
                       p.apellidos AS paciente_apellidos, p.fecha_nacimiento,
                       p.telefono, p.direccion,
                       m.nombres AS medico_nombres,
                       m.apellidos AS medico_apellidos,
                       e.nombre_especialidad, cs.nombre AS centro_nombre,
                       h.id AS historial_id, h.motivo_consulta,
                       h.diagnostico, h.tratamiento, h.observaciones,
                       sv.temperatura, sv.presion_sistolica,
                       sv.presion_diastolica, sv.frecuencia_cardiaca,
                       sv.frecuencia_respiratoria, sv.saturacion_oxigeno,
                       sv.peso, sv.talla, sv.notas AS signos_notas
                FROM cita c
                INNER JOIN paciente p ON p.id = c.paciente_id
                INNER JOIN medico m ON m.id = c.medico_id
                INNER JOIN centro_salud cs ON cs.id = c.centro_salud_id
                LEFT JOIN especialidad e ON e.id = m.especialidad_id
                INNER JOIN estado_cita ec ON ec.id = c.estado_id
                LEFT JOIN historial_medico h ON h.cita_id = c.id
                LEFT JOIN signos_vitales sv ON sv.cita_id = c.id
                WHERE c.id = :cita_id
                LIMIT 1";
        $row = parent::obtenerUnRegistro($sql, ['cita_id' => $citaId]);
        return $row ?: null;
    }

    public static function guardarSignosVitales(
        int $citaId,
        array $datos
    ): void {
        $sql = "INSERT INTO signos_vitales
                    (cita_id, temperatura, presion_sistolica,
                     presion_diastolica, frecuencia_cardiaca,
                     frecuencia_respiratoria, saturacion_oxigeno,
                     peso, talla, notas)
                VALUES
                    (:cita_id, :temperatura, :presion_sistolica,
                     :presion_diastolica, :frecuencia_cardiaca,
                     :frecuencia_respiratoria, :saturacion_oxigeno,
                     :peso, :talla, :notas)
                ON DUPLICATE KEY UPDATE
                    temperatura = VALUES(temperatura),
                    presion_sistolica = VALUES(presion_sistolica),
                    presion_diastolica = VALUES(presion_diastolica),
                    frecuencia_cardiaca = VALUES(frecuencia_cardiaca),
                    frecuencia_respiratoria = VALUES(frecuencia_respiratoria),
                    saturacion_oxigeno = VALUES(saturacion_oxigeno),
                    peso = VALUES(peso),
                    talla = VALUES(talla),
                    notas = VALUES(notas)";
        $datos['cita_id'] = $citaId;
        parent::executeNonQuery($sql, $datos);
    }

    public static function getRecetasHistorial(int $historialId): array
    {
        $sql = "SELECT medicamento, indicaciones, fecha_emision
                FROM receta_medica
                WHERE historial_id = :historial_id
                ORDER BY fecha_emision";
        return parent::obtenerRegistros(
            $sql,
            ['historial_id' => $historialId]
        );
    }

    public static function crearPago(int $citaId, float $total, string $metodo, string $transaccion): int
    {
        parent::executeNonQuery(
            "INSERT INTO pago_factura (cita_id, total, metodo_pago, id_transaccion_api)
             VALUES (:cita_id, :total, :metodo, :transaccion)
             ON DUPLICATE KEY UPDATE total = VALUES(total), metodo_pago = VALUES(metodo_pago), id_transaccion_api = VALUES(id_transaccion_api), fecha_pago = CURRENT_TIMESTAMP",
            [
                'cita_id' => $citaId,
                'total' => $total,
                'metodo' => $metodo,
                'transaccion' => $transaccion,
            ]
        );
        $row = parent::obtenerUnRegistro("SELECT id FROM pago_factura WHERE cita_id = :cita_id", ['cita_id' => $citaId]);
        $facturaId = intval($row['id'] ?? parent::getLastInsertId());
        parent::executeNonQuery("DELETE FROM factura_detalle WHERE factura_id = :factura_id", ['factura_id' => $facturaId]);
        parent::executeNonQuery(
            "INSERT INTO factura_detalle (factura_id, concepto, cantidad, precio_unitario, subtotal)
             VALUES (:factura_id, 'Consulta médica', 1, :precio, :subtotal)",
            ['factura_id' => $facturaId, 'precio' => $total, 'subtotal' => $total]
        );
        return $facturaId;
    }

    public static function getPagos(): array
    {
        $sql = "SELECT pf.*, c.fecha_hora, p.nombres AS paciente_nombres, p.apellidos AS paciente_apellidos,
                       m.nombres AS medico_nombres, m.apellidos AS medico_apellidos
                FROM pago_factura pf
                INNER JOIN cita c ON pf.cita_id = c.id
                INNER JOIN paciente p ON c.paciente_id = p.id
                INNER JOIN medico m ON c.medico_id = m.id
                ORDER BY pf.fecha_pago DESC";
        return parent::obtenerRegistros($sql, []);
    }

    public static function crearNotificacion(
        string $tipo,
        string $mensaje,
        ?int $usuarioDestino = null,
        ?string $referencia = null
    ): int {
        parent::executeNonQuery(
            "INSERT INTO notificaciones (tipo, mensaje, usuario_destino_id, referencia)
             VALUES (:tipo, :mensaje, :usuario_destino_id, :referencia)",
            [
                'tipo' => $tipo,
                'mensaje' => $mensaje,
                'usuario_destino_id' => $usuarioDestino,
                'referencia' => $referencia,
            ]
        );
        return intval(parent::getLastInsertId());
    }

    /**
     * Evita crear un aviso duplicado (p. ej. stock bajo) cada vez que se
     * recalcula la condición: mientras exista una notificación sin leer
     * con esta misma "referencia", no se crea otra. Una vez marcada como
     * leída, la próxima vez que se cumpla la condición sí se vuelve a
     * avisar.
     */
    public static function existeNotificacionActivaPorReferencia(string $referencia): bool
    {
        $row = parent::obtenerUnRegistro(
            "SELECT id FROM notificaciones WHERE referencia = :referencia AND leida = 0 LIMIT 1",
            ['referencia' => $referencia]
        );
        return (bool) $row;
    }

    /**
     * "no_leidas" (default) muestra solo las pendientes, así que marcar
     * una como leída tiene un efecto visible (desaparece de esa vista).
     * "leidas" muestra el historial de las ya atendidas, para el botón
     * "Ver leídas" del panel de Notificaciones.
     */
    public static function getNotificaciones(int $usuarioId = 0, string $filtro = 'no_leidas'): array
    {
        $condicionLeida = $filtro === 'leidas' ? ' AND leida = 1' : ' AND leida = 0';
        $sql = "SELECT * FROM notificaciones
                WHERE (usuario_destino_id IS NULL OR usuario_destino_id = :usuario_id)"
            . $condicionLeida
            . " ORDER BY fecha_creacion DESC
                LIMIT 50";
        return parent::obtenerRegistros($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * Marca una notificación como leída, pero solo si es visible para ese
     * usuario (destinatario exacto o notificación general sin destinatario).
     * Sin este filtro, cualquier usuario logueado podía marcar como leída
     * la notificación de otra persona con solo cambiar el id en la URL.
     */
    public static function marcarNotificacionLeida(int $id, int $usuarioId): bool
    {
        return parent::executeNonQuery(
            "UPDATE notificaciones
             SET leida = 1
             WHERE id = :id
               AND (usuario_destino_id IS NULL OR usuario_destino_id = :usuario_id)",
            ['id' => $id, 'usuario_id' => $usuarioId]
        );
    }

    public static function crearTokenRecuperacion(string $email, string $token): void
    {
        parent::executeNonQuery(
            "INSERT INTO password_reset_tokens (useremail, token, expires_at)
             VALUES (:email, :token, DATE_ADD(NOW(), INTERVAL 30 MINUTE))",
            ['email' => $email, 'token' => $token]
        );
    }

    public static function validarTokenRecuperacion(string $email, string $token): ?array
    {
        $sql = "SELECT * FROM password_reset_tokens
                WHERE useremail = :email AND token = :token AND used = 0 AND expires_at >= NOW()
                ORDER BY id DESC LIMIT 1";
        $row = parent::obtenerUnRegistro($sql, ['email' => $email, 'token' => $token]);
        return $row ?: null;
    }

    public static function cambiarPassword(string $email, string $passwordHash): void
    {
        parent::executeNonQuery(
            "UPDATE usuario SET userpswd = :password, userpswdchg = CURRENT_TIMESTAMP WHERE useremail = :email",
            ['password' => $passwordHash, 'email' => $email]
        );
    }

    public static function usarToken(int $tokenId): void
    {
        parent::executeNonQuery("UPDATE password_reset_tokens SET used = 1 WHERE id = :id", ['id' => $tokenId]);
    }

    public static function getUsuarioByEmail(string $email): ?array
    {
        $row = parent::obtenerUnRegistro("SELECT * FROM usuario WHERE useremail = :email LIMIT 1", ['email' => $email]);
        return $row ?: null;
    }

    /**
     * Obtiene la carga de médicos correspondiente a un centro de salud.
     *
     * Incluye asignaciones activas aunque todavía no tengan citas y conserva
     * médicos con citas históricas aunque su asignación haya sido inactivada.
     * Los dos parámetros tienen nombres distintos para mantener compatibilidad
     * con las sentencias preparadas nativas de PDO.
     */
    public static function getCargaMedicos(int $centroSaludId): array
    {
        $sql = "SELECT CONCAT(m.nombres, ' ', m.apellidos) AS medico,
                       COUNT(c.id) AS total_citas
                FROM medico_centro_salud mcs
                INNER JOIN medico m ON m.id = mcs.medico_id
                LEFT JOIN cita c
                    ON c.medico_id = m.id
                   AND c.centro_salud_id = :citas_centro_id
                WHERE mcs.centro_salud_id = :asignacion_centro_id
                  AND (mcs.estado = 'ACT' OR c.id IS NOT NULL)
                GROUP BY m.id
                ORDER BY total_citas DESC, medico ASC";

        return parent::obtenerRegistros($sql, [
            'citas_centro_id' => $centroSaludId,
            'asignacion_centro_id' => $centroSaludId
        ]);
    }

    /**
     * Resume los principales indicadores operativos de un centro.
     *
     * Todas las subconsultas reciben un marcador independiente para evitar
     * reutilizar parámetros nombrados en PDO. Los estados 3, 4 y 5 se
     * consideran terminales al contar citas futuras activas. Los indicadores
     * mensuales usan limites de fecha para conservar el uso de indices.
     */
    public static function getResumenBI(int $centroSaludId): array
    {
        $sql = "SELECT
                    (
                        SELECT COUNT(*)
                        FROM cita
                        WHERE centro_salud_id = :citas_centro_id
                    ) AS total_citas,
                    (
                        SELECT COUNT(*)
                        FROM cita
                        WHERE centro_salud_id = :citas_mes_centro_id
                          AND fecha_hora >= DATE_FORMAT(
                              CURRENT_DATE,
                              '%Y-%m-01'
                          )
                          AND fecha_hora < DATE_ADD(
                              DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'),
                              INTERVAL 1 MONTH
                          )
                    ) AS citas_mes_actual,
                    (
                        SELECT COUNT(*)
                        FROM cita
                        WHERE centro_salud_id = :futuras_centro_id
                          AND fecha_hora >= CURRENT_TIMESTAMP
                          AND estado_id NOT IN (3, 4, 5)
                    ) AS citas_futuras,
                    (
                        SELECT COUNT(*)
                        FROM medico_centro_salud
                        WHERE centro_salud_id = :medicos_centro_id
                          AND estado = 'ACT'
                    ) AS medicos_asignados,
                    (
                        SELECT COALESCE(SUM(pf.total), 0)
                        FROM pago_factura pf
                        INNER JOIN cita c ON c.id = pf.cita_id
                        WHERE c.centro_salud_id = :ingresos_centro_id
                    ) AS ingresos_total,
                    (
                        SELECT COALESCE(SUM(pf.total), 0)
                        FROM pago_factura pf
                        INNER JOIN cita c ON c.id = pf.cita_id
                        WHERE c.centro_salud_id = :ingresos_mes_centro_id
                          AND pf.fecha_pago >= DATE_FORMAT(
                              CURRENT_DATE,
                              '%Y-%m-01'
                          )
                          AND pf.fecha_pago < DATE_ADD(
                              DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'),
                              INTERVAL 1 MONTH
                          )
                    ) AS ingresos_mes_actual";

        $row = parent::obtenerUnRegistro($sql, [
            'citas_centro_id' => $centroSaludId,
            'citas_mes_centro_id' => $centroSaludId,
            'futuras_centro_id' => $centroSaludId,
            'medicos_centro_id' => $centroSaludId,
            'ingresos_centro_id' => $centroSaludId,
            'ingresos_mes_centro_id' => $centroSaludId
        ]);

        return is_array($row)
            ? $row
            : [
                'total_citas' => 0,
                'citas_mes_actual' => 0,
                'citas_futuras' => 0,
                'medicos_asignados' => 0,
                'ingresos_total' => 0,
                'ingresos_mes_actual' => 0
            ];
    }

    /**
     * Construye todas las series del tablero BI para un único centro.
     *
     * No existe una opción de inventario general en este método: citas,
     * ingresos y carga médica se filtran siempre por $centroSaludId para
     * impedir que el tablero mezcle ubicaciones.
     */
    public static function getMetricasBI(int $centroSaludId): array
    {
        $citasPorEstado = parent::obtenerRegistros(
            "SELECT ec.nombre_estado AS estado, COUNT(c.id) AS total
             FROM estado_cita ec
             LEFT JOIN cita c
                ON ec.id = c.estado_id
               AND c.centro_salud_id = :estado_centro_id
             GROUP BY ec.id
             ORDER BY ec.id",
            ['estado_centro_id' => $centroSaludId]
        );
        $citasPorMes = parent::obtenerRegistros(
            "SELECT DATE_FORMAT(fecha_hora, '%Y-%m') AS mes, COUNT(*) AS total
             FROM cita
             WHERE centro_salud_id = :mes_centro_id
             GROUP BY DATE_FORMAT(fecha_hora, '%Y-%m')
             ORDER BY mes",
            ['mes_centro_id' => $centroSaludId]
        );
        $ingresos = parent::obtenerRegistros(
            "SELECT DATE_FORMAT(pf.fecha_pago, '%Y-%m') AS mes,
                    SUM(pf.total) AS total
             FROM pago_factura pf
             INNER JOIN cita c ON c.id = pf.cita_id
             WHERE c.centro_salud_id = :pago_centro_id
             GROUP BY DATE_FORMAT(pf.fecha_pago, '%Y-%m')
             ORDER BY mes",
            ['pago_centro_id' => $centroSaludId]
        );
        return [
            'citasPorEstado' => $citasPorEstado,
            'citasPorMes' => $citasPorMes,
            'ingresos' => $ingresos,
            'cargaMedicos' => self::getCargaMedicos($centroSaludId),
            'resumen' => self::getResumenBI($centroSaludId)
        ];
    }

    /**
     * Reune los conjuntos de datos de los reportes imprimibles del BI.
     *
     * El periodo se aplica a la fecha programada de las citas y a la fecha
     * efectiva de los pagos, segun corresponda. Todas las consultas exigen
     * un centro de salud para impedir mezclar operaciones entre sedes.
     */
    public static function getReporteBI(
        int $centroSaludId,
        string $fechaDesde,
        string $fechaHasta
    ): array {
        return [
            'resumen' => self::getResumenReporteBI(
                $centroSaludId,
                $fechaDesde,
                $fechaHasta
            ),
            'citasPorEstado' => self::getCitasPorEstadoReporteBI(
                $centroSaludId,
                $fechaDesde,
                $fechaHasta
            ),
            'cargaMedicos' => self::getCargaMedicosReporteBI(
                $centroSaludId,
                $fechaDesde,
                $fechaHasta
            ),
            'citas' => self::getCitasDetalleReporteBI(
                $centroSaludId,
                $fechaDesde,
                $fechaHasta
            ),
            'pagos' => self::getPagosDetalleReporteBI(
                $centroSaludId,
                $fechaDesde,
                $fechaHasta
            )
        ];
    }

    /**
     * Calcula los indicadores de cabecera para un centro y periodo.
     *
     * Las cifras de citas usan fecha_hora. Las cifras financieras usan
     * fecha_pago, de modo que el reporte refleje el ingreso cuando realmente
     * fue recibido aunque la cita pertenezca a otra fecha.
     */
    public static function getResumenReporteBI(
        int $centroSaludId,
        string $fechaDesde,
        string $fechaHasta
    ): array {
        $sql = "SELECT
                    COUNT(c.id) AS total_citas,
                    COALESCE(
                        SUM(CASE WHEN c.estado_id = 3 THEN 1 ELSE 0 END),
                        0
                    ) AS citas_completadas,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN c.estado_id IN (4, 5) THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS citas_canceladas,
                    COUNT(DISTINCT c.paciente_id) AS pacientes,
                    COUNT(DISTINCT c.medico_id) AS medicos,
                    (
                        SELECT COUNT(*)
                        FROM pago_factura pf
                        INNER JOIN cita cp ON cp.id = pf.cita_id
                        WHERE cp.centro_salud_id = :pagos_centro_id
                          AND pf.fecha_pago >= :pagos_desde
                          AND pf.fecha_pago < DATE_ADD(
                              :pagos_hasta,
                              INTERVAL 1 DAY
                          )
                    ) AS total_pagos,
                    (
                        SELECT COALESCE(SUM(pf.total), 0)
                        FROM pago_factura pf
                        INNER JOIN cita cp ON cp.id = pf.cita_id
                        WHERE cp.centro_salud_id = :ingresos_centro_id
                          AND pf.fecha_pago >= :ingresos_desde
                          AND pf.fecha_pago < DATE_ADD(
                              :ingresos_hasta,
                              INTERVAL 1 DAY
                          )
                    ) AS ingresos,
                    (
                        SELECT COALESCE(AVG(pf.total), 0)
                        FROM pago_factura pf
                        INNER JOIN cita cp ON cp.id = pf.cita_id
                        WHERE cp.centro_salud_id = :promedio_centro_id
                          AND pf.fecha_pago >= :promedio_desde
                          AND pf.fecha_pago < DATE_ADD(
                              :promedio_hasta,
                              INTERVAL 1 DAY
                          )
                    ) AS promedio_pago
                FROM cita c
                WHERE c.centro_salud_id = :citas_centro_id
                  AND c.fecha_hora >= :citas_desde
                  AND c.fecha_hora < DATE_ADD(
                      :citas_hasta,
                      INTERVAL 1 DAY
                  )";

        $row = parent::obtenerUnRegistro($sql, [
            'pagos_centro_id' => $centroSaludId,
            'pagos_desde' => $fechaDesde,
            'pagos_hasta' => $fechaHasta,
            'ingresos_centro_id' => $centroSaludId,
            'ingresos_desde' => $fechaDesde,
            'ingresos_hasta' => $fechaHasta,
            'promedio_centro_id' => $centroSaludId,
            'promedio_desde' => $fechaDesde,
            'promedio_hasta' => $fechaHasta,
            'citas_centro_id' => $centroSaludId,
            'citas_desde' => $fechaDesde,
            'citas_hasta' => $fechaHasta
        ]);

        return is_array($row) ? $row : [];
    }

    /**
     * Agrupa por estado las citas programadas dentro del periodo reportado.
     *
     * Se parte del catalogo de estados para que un estado sin citas tambien
     * aparezca con cero y el documento mantenga una estructura comparable.
     */
    public static function getCitasPorEstadoReporteBI(
        int $centroSaludId,
        string $fechaDesde,
        string $fechaHasta
    ): array {
        $sql = "SELECT ec.nombre_estado AS estado, COUNT(c.id) AS total
                FROM estado_cita ec
                LEFT JOIN cita c
                    ON c.estado_id = ec.id
                   AND c.centro_salud_id = :centro_id
                   AND c.fecha_hora >= :fecha_desde
                   AND c.fecha_hora < DATE_ADD(
                       :fecha_hasta,
                       INTERVAL 1 DAY
                   )
                GROUP BY ec.id, ec.nombre_estado
                ORDER BY ec.id";

        return parent::obtenerRegistros($sql, [
            'centro_id' => $centroSaludId,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta
        ]);
    }

    /**
     * Mide la carga de cada medico asignado al centro durante el periodo.
     *
     * COUNT DISTINCT evita duplicar citas si existen varias asignaciones
     * historicas del mismo medico. Los medicos activos sin citas permanecen
     * visibles con total cero para apoyar decisiones de capacidad.
     */
    public static function getCargaMedicosReporteBI(
        int $centroSaludId,
        string $fechaDesde,
        string $fechaHasta
    ): array {
        $sql = "SELECT
                    CONCAT(m.nombres, ' ', m.apellidos) AS medico,
                    e.nombre_especialidad AS especialidad,
                    COUNT(DISTINCT c.id) AS total_citas,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN c.estado_id = 3 THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS completadas
                FROM medico_centro_salud mcs
                INNER JOIN medico m ON m.id = mcs.medico_id
                INNER JOIN especialidad e ON e.id = m.especialidad_id
                LEFT JOIN cita c
                    ON c.medico_id = m.id
                   AND c.centro_salud_id = :citas_centro_id
                   AND c.fecha_hora >= :fecha_desde
                   AND c.fecha_hora < DATE_ADD(
                       :fecha_hasta,
                       INTERVAL 1 DAY
                   )
                WHERE mcs.centro_salud_id = :asignacion_centro_id
                  AND mcs.estado = 'ACT'
                GROUP BY m.id, m.nombres, m.apellidos,
                         e.nombre_especialidad
                ORDER BY total_citas DESC, medico ASC";

        return parent::obtenerRegistros($sql, [
            'citas_centro_id' => $centroSaludId,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'asignacion_centro_id' => $centroSaludId
        ]);
    }

    /**
     * Obtiene el detalle clinico-operativo para el reporte de citas.
     *
     * Solo expone datos necesarios para control administrativo; no incluye
     * diagnosticos, notas ni otra informacion sensible del expediente.
     */
    public static function getCitasDetalleReporteBI(
        int $centroSaludId,
        string $fechaDesde,
        string $fechaHasta
    ): array {
        $sql = "SELECT
                    c.id,
                    c.fecha_hora,
                    CONCAT(p.nombres, ' ', p.apellidos) AS paciente,
                    CONCAT(m.nombres, ' ', m.apellidos) AS medico,
                    e.nombre_especialidad AS especialidad,
                    ec.nombre_estado AS estado,
                    COALESCE(c.consultorio, '') AS consultorio
                FROM cita c
                INNER JOIN paciente p ON p.id = c.paciente_id
                INNER JOIN medico m ON m.id = c.medico_id
                INNER JOIN especialidad e ON e.id = m.especialidad_id
                INNER JOIN estado_cita ec ON ec.id = c.estado_id
                WHERE c.centro_salud_id = :centro_id
                  AND c.fecha_hora >= :fecha_desde
                  AND c.fecha_hora < DATE_ADD(
                      :fecha_hasta,
                      INTERVAL 1 DAY
                  )
                ORDER BY c.fecha_hora DESC, c.id DESC";

        return parent::obtenerRegistros($sql, [
            'centro_id' => $centroSaludId,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta
        ]);
    }

    /**
     * Obtiene los cobros recibidos por el centro dentro del periodo.
     *
     * El centro se deriva de la cita relacionada y el filtro cronologico usa
     * fecha_pago. Asi cada ingreso se atribuye a la sede y fecha correctas.
     */
    public static function getPagosDetalleReporteBI(
        int $centroSaludId,
        string $fechaDesde,
        string $fechaHasta
    ): array {
        $sql = "SELECT
                    pf.id,
                    pf.cita_id,
                    pf.fecha_pago,
                    pf.total,
                    pf.metodo_pago,
                    pf.id_transaccion_api,
                    CONCAT(p.nombres, ' ', p.apellidos) AS paciente,
                    CONCAT(m.nombres, ' ', m.apellidos) AS medico
                FROM pago_factura pf
                INNER JOIN cita c ON c.id = pf.cita_id
                INNER JOIN paciente p ON p.id = c.paciente_id
                INNER JOIN medico m ON m.id = c.medico_id
                WHERE c.centro_salud_id = :centro_id
                  AND pf.fecha_pago >= :fecha_desde
                  AND pf.fecha_pago < DATE_ADD(
                      :fecha_hasta,
                      INTERVAL 1 DAY
                  )
                ORDER BY pf.fecha_pago DESC, pf.id DESC";

        return parent::obtenerRegistros($sql, [
            'centro_id' => $centroSaludId,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta
        ]);
    }
}
