<?php

namespace Dao;

class ClinicaAvanzada extends Table
{
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
                       cs.nombre AS centro_nombre, mcs.consultorio,
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
     */
    public static function getSalaEspera(int $medicoId): array
    {
        $sql = "SELECT c.*, p.nombres AS paciente_nombres, p.apellidos AS paciente_apellidos,
                       p.telefono AS paciente_telefono, ec.nombre_estado,
                       cs.nombre AS centro_nombre, mcs.consultorio
                FROM cita c
                INNER JOIN paciente p ON c.paciente_id = p.id
                INNER JOIN estado_cita ec ON c.estado_id = ec.id
                INNER JOIN centro_salud cs ON c.centro_salud_id = cs.id
                INNER JOIN medico_centro_salud mcs
                    ON mcs.medico_id = c.medico_id
                   AND mcs.centro_salud_id = c.centro_salud_id
                WHERE c.medico_id = :medico_id
                  AND c.estado_id IN (2, 6, 7)
                  AND DATE(c.fecha_hora) = CURDATE()
                ORDER BY c.fecha_hora ASC";
        return parent::obtenerRegistros($sql, ['medico_id' => $medicoId]);
    }

    public static function actualizarEstadoCita(int $citaId, int $estadoId): void
    {
        parent::executeNonQuery(
            "UPDATE cita SET estado_id = :estado_id WHERE id = :id",
            ['estado_id' => $estadoId, 'id' => $citaId]
        );
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
                       c.centro_salud_id, ec.nombre_estado,
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
                       c.centro_salud_id, ec.nombre_estado,
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

    public static function crearNotificacion(string $tipo, string $mensaje, ?int $usuarioDestino = null): int
    {
        parent::executeNonQuery(
            "INSERT INTO notificaciones (tipo, mensaje, usuario_destino_id) VALUES (:tipo, :mensaje, :usuario_destino_id)",
            ['tipo' => $tipo, 'mensaje' => $mensaje, 'usuario_destino_id' => $usuarioDestino]
        );
        return intval(parent::getLastInsertId());
    }

    public static function getNotificaciones(int $usuarioId = 0): array
    {
        $sql = "SELECT * FROM notificaciones
                WHERE usuario_destino_id IS NULL OR usuario_destino_id = :usuario_id
                ORDER BY fecha_creacion DESC
                LIMIT 50";
        return parent::obtenerRegistros($sql, ['usuario_id' => $usuarioId]);
    }

    public static function marcarNotificacionLeida(int $id): void
    {
        parent::executeNonQuery("UPDATE notificaciones SET leida = 1 WHERE id = :id", ['id' => $id]);
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

    public static function getCargaMedicos(): array
    {
        $sql = "SELECT CONCAT(m.nombres, ' ', m.apellidos) AS medico, COUNT(c.id) AS total_citas
                FROM medico m
                LEFT JOIN cita c ON m.id = c.medico_id
                GROUP BY m.id
                ORDER BY total_citas DESC, medico ASC";
        return parent::obtenerRegistros($sql, []);
    }

    public static function getMetricasBI(): array
    {
        $citasPorEstado = parent::obtenerRegistros(
            "SELECT ec.nombre_estado AS estado, COUNT(c.id) AS total
             FROM estado_cita ec
             LEFT JOIN cita c ON ec.id = c.estado_id
             GROUP BY ec.id
             ORDER BY ec.id",
            []
        );
        $citasPorMes = parent::obtenerRegistros(
            "SELECT DATE_FORMAT(fecha_hora, '%Y-%m') AS mes, COUNT(*) AS total
             FROM cita GROUP BY DATE_FORMAT(fecha_hora, '%Y-%m') ORDER BY mes",
            []
        );
        $ingresos = parent::obtenerRegistros(
            "SELECT DATE_FORMAT(fecha_pago, '%Y-%m') AS mes, SUM(total) AS total
             FROM pago_factura GROUP BY DATE_FORMAT(fecha_pago, '%Y-%m') ORDER BY mes",
            []
        );
        return [
            'citasPorEstado' => $citasPorEstado,
            'citasPorMes' => $citasPorMes,
            'ingresos' => $ingresos,
            'cargaMedicos' => self::getCargaMedicos(),
        ];
    }
}
