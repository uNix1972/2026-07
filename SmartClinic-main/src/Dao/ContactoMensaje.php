<?php

namespace Dao;

/**
 * Acceso a datos de los mensajes enviados desde el formulario publico.
 *
 * Centraliza la persistencia y el seguimiento administrativo para evitar
 * archivos JSON sin control transaccional ni visibilidad dentro del sistema.
 */
class ContactoMensaje extends Table
{
    /**
     * Registra un mensaje nuevo con estado pendiente.
     */
    public static function insert(
        string $nombre,
        string $email,
        string $asunto,
        string $mensaje,
        ?string $ipOrigen
    ): int {
        parent::executeNonQuery(
            "INSERT INTO contacto_mensaje
                (nombre, email, asunto, mensaje, ip_origen)
             VALUES
                (:nombre, :email, :asunto, :mensaje, :ip_origen)",
            [
                "nombre" => $nombre,
                "email" => $email,
                "asunto" => $asunto,
                "mensaje" => $mensaje,
                "ip_origen" => $ipOrigen
            ]
        );

        return (int) parent::getLastInsertId();
    }

    /**
     * Obtiene el buzon administrativo con filtros opcionales.
     */
    public static function getAll(
        string $estado = "",
        string $search = ""
    ): array {
        $sql = "SELECT cm.*, u.username AS gestionado_por
                FROM contacto_mensaje cm
                LEFT JOIN usuario u
                    ON u.usercod = cm.usuario_gestion_id
                WHERE 1 = 1";
        $params = [];

        if ($estado !== "") {
            $sql .= " AND cm.estado = :estado";
            $params["estado"] = $estado;
        }

        if ($search !== "") {
            $like = "%" . $search . "%";
            $sql .= " AND (
                cm.nombre LIKE :search_nombre
                OR cm.email LIKE :search_email
                OR cm.asunto LIKE :search_asunto
                OR cm.mensaje LIKE :search_mensaje
            )";
            $params["search_nombre"] = $like;
            $params["search_email"] = $like;
            $params["search_asunto"] = $like;
            $params["search_mensaje"] = $like;
        }

        $sql .= " ORDER BY
                    CASE cm.estado
                        WHEN 'PEN' THEN 1
                        WHEN 'LEI' THEN 2
                        ELSE 3
                    END,
                    cm.fecha_creacion DESC";

        return parent::obtenerRegistros($sql, $params);
    }

    /**
     * Busca un mensaje por su identificador para validar su existencia.
     */
    public static function getById(int $id): ?array
    {
        $row = parent::obtenerUnRegistro(
            "SELECT *
             FROM contacto_mensaje
             WHERE id = :id
             LIMIT 1",
            ["id" => $id]
        );

        return is_array($row) ? $row : null;
    }

    /**
     * Resume el total de mensajes por estado para el encabezado del buzon.
     */
    public static function getCounts(): array
    {
        $row = parent::obtenerUnRegistro(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(estado = 'PEN'), 0) AS pendientes,
                COALESCE(SUM(estado = 'LEI'), 0) AS leidos,
                COALESCE(SUM(estado = 'RES'), 0) AS resueltos
             FROM contacto_mensaje",
            []
        );

        return is_array($row)
            ? $row
            : [
                "total" => 0,
                "pendientes" => 0,
                "leidos" => 0,
                "resueltos" => 0
            ];
    }

    /**
     * Actualiza el seguimiento conservando las fechas de lectura y solucion.
     */
    public static function setStatus(
        int $id,
        string $estado,
        int $usuarioGestionId
    ): bool {
        if (!in_array($estado, ["PEN", "LEI", "RES"], true)) {
            throw new \InvalidArgumentException(
                "Estado de mensaje de contacto no valido."
            );
        }

        return parent::executeNonQuery(
            "UPDATE contacto_mensaje
             SET estado = :estado,
                 fecha_lectura = CASE
                    WHEN :estado_lectura = 'PEN' THEN NULL
                    ELSE COALESCE(fecha_lectura, CURRENT_TIMESTAMP)
                 END,
                 fecha_resolucion = CASE
                    WHEN :estado_resolucion = 'RES'
                        THEN CURRENT_TIMESTAMP
                    ELSE NULL
                 END,
                 usuario_gestion_id = :usuario_gestion_id
             WHERE id = :id",
            [
                "estado" => $estado,
                "estado_lectura" => $estado,
                "estado_resolucion" => $estado,
                "usuario_gestion_id" => $usuarioGestionId,
                "id" => $id
            ]
        );
    }
}

