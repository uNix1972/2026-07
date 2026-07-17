<?php

namespace Utilities;

class Validators
{
    public const MAX_STRING_LENGTH = 255;
    public const MAX_TEXT_LENGTH = 65535;
    public const MAX_INT_VALUE = 2147483647;

    static public function IsEmpty($valor): bool
    {
        return preg_match("/^\s*$/", $valor) && true;
    }

    static public function IsValidEmail($valor): bool
    {
        if (!is_string($valor) || strlen($valor) > 254) {
            return false;
        }
        return (bool)filter_var($valor, FILTER_VALIDATE_EMAIL);
    }

    static public function IsValidPassword($valor): bool
    {
        if (!is_string($valor) || strlen($valor) > 72) {
            return false;
        }
        return preg_match("/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[^\w\d\s:])([^\s]){8,32}$/", $valor) && true;
    }

    static public function sanitizeString(string $valor, int $maxLength = self::MAX_STRING_LENGTH): string
    {
        $valor = trim($valor);
        $valor = self::safeSubstring($valor, $maxLength);
        $valor = htmlspecialchars($valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $valor;
    }

    static public function sanitizeText(string $valor, int $maxLength = self::MAX_TEXT_LENGTH): string
    {
        $valor = trim($valor);
        $valor = self::safeSubstring($valor, $maxLength);
        $valor = htmlspecialchars($valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $valor;
    }

    static public function sanitizeInt($valor, int $min = 0, int $max = self::MAX_INT_VALUE): ?int
    {
        if (!is_numeric($valor)) {
            return null;
        }
        $int = (int)$valor;
        if ($int < $min || $int > $max) {
            return null;
        }
        return $int;
    }

    static public function sanitizeFloat($valor, float $min = 0.0, float $max = 99999999.99): ?float
    {
        if (!is_numeric($valor)) {
            return null;
        }
        $float = (float)$valor;
        if ($float < $min || $float > $max) {
            return null;
        }
        return round($float, 2);
    }

    static public function sanitizeDate(string $valor): ?string
    {
        $valor = trim($valor);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return null;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $valor);
        return $d && $d->format('Y-m-d') === $valor ? $valor : null;
    }

    static public function sanitizeTime(string $valor): ?string
    {
        $valor = trim($valor);
        if (!preg_match('/^\d{2}:\d{2}$/', $valor)) {
            return null;
        }
        $parts = explode(':', $valor);
        $hour = (int)$parts[0];
        $min = (int)$parts[1];
        if ($hour < 0 || $hour > 23 || $min < 0 || $min > 59) {
            return null;
        }
        return sprintf('%02d:%02d', $hour, $min);
    }

    static public function sanitizeEmail(string $valor): ?string
    {
        $valor = trim($valor);
        $valor = function_exists('mb_strtolower') ? mb_strtolower($valor, 'UTF-8') : strtolower($valor);
        if (strlen($valor) > 254 || !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        return $valor;
    }

    static public function sanitizeAlphaNum(string $valor, int $maxLength = self::MAX_STRING_LENGTH): string
    {
        $valor = trim($valor);
        $valor = preg_replace('/[^a-zA-Z0-9_\-]/', '', $valor);
        return self::safeSubstring($valor, $maxLength);
    }

    static public function sanitizeId($valor): ?int
    {
        return self::sanitizeInt($valor, 1);
    }

    static public function validateCsrfToken(string $token, string $sessionToken): bool
    {
        if (empty($token) || empty($sessionToken)) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }

    static public function generateCsrfToken(): string
    {
        return bin2hex(random_bytes(32));
    }


    static private function safeSubstring(string $valor, int $maxLength): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($valor, 0, $maxLength, 'UTF-8');
        }

        return substr($valor, 0, $maxLength);
    }
    private function __construct()
    {
    }
    private function __clone()
    {
    }
}