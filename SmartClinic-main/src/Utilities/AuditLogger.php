<?php

namespace Utilities;

/**
 * Bitacora ligera para entornos academicos/locales.
 * Guarda acciones importantes en data/audit_log.json sin requerir cambios en la base de datos.
 */
class AuditLogger
{
    private const LOG_FILE = __DIR__ . '/../../data/audit_log.json';
    private const MAX_RECORDS = 300;

    public static function log(string $action, string $module, string $detail = '', array $metadata = []): void
    {
        try {
            $records = self::readAll();
            $user = \Utilities\Security::getUser();
            $records[] = [
                'fecha' => date('Y-m-d H:i:s'),
                'usuario_id' => is_array($user) ? intval($user['userId'] ?? 0) : 0,
                'usuario' => is_array($user) ? strval($user['userName'] ?? 'Sistema') : 'Sistema',
                'modulo' => self::clean($module, 80),
                'accion' => self::clean($action, 80),
                'detalle' => self::clean($detail, 220),
                'ip' => self::clientIp(),
                'metadata' => $metadata,
            ];

            if (count($records) > self::MAX_RECORDS) {
                $records = array_slice($records, -self::MAX_RECORDS);
            }

            self::writeAll($records);
        } catch (\Throwable $ex) {
            error_log('AuditLogger error: ' . $ex->getMessage());
        }
    }

    public static function readAll(): array
    {
        $file = self::LOG_FILE;
        if (!is_file($file)) {
            return [];
        }

        $content = file_get_contents($file);
        if ($content === false || trim($content) === '') {
            return [];
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    public static function readLatest(int $limit = 50): array
    {
        $records = self::readAll();
        $records = array_reverse($records);
        return array_slice($records, 0, max(1, $limit));
    }

    private static function writeAll(array $records): void
    {
        $file = self::LOG_FILE;
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $file,
            json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private static function clean(string $value, int $max): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max, 'UTF-8');
        }
        return substr($value, 0, $max);
    }

    private static function clientIp(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $parts = explode(',', (string)$_SERVER[$key]);
                return self::clean($parts[0], 60);
            }
        }
        return 'unknown';
    }
}
