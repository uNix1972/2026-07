<?php

namespace Utilities;

use Dao\Security\Security as DaoSecurity;

class Security
{
    // =============================
    // __CONSTRUCT
    // =============================
    private function __construct()
    {
        // Evita instanciacion directa de la utilidad
    }

    // =============================
    // __CLONE
    // =============================
    private function __clone()
    {
        // Evita clonacion para mantener uso estatico
    }

    // =============================
    // UPDATEUSERNAME
    // =============================
    public static function updateUserName(int $userId, string $newName): void
    {
        DaoSecurity::updateUsuarioNombre($userId, $newName);
        if (isset($_SESSION['login'])) {
            $_SESSION['login']['userName'] = $newName;
        }
        $_SESSION['userName'] = $newName;
    }

    // =============================
    // LOGOUT
    // =============================
    public static function logout()
    {
        // Cierra sesion local y token activo en base de datos
        if (isset($_SESSION['login']['userId'])) {
            DaoSecurity::clearActiveSessionToken(intval($_SESSION['login']['userId']));
        }

        unset($_SESSION['login']);
        unset($_SESSION['userName']);
        unset($_SESSION['userEmail']);
        unset($_SESSION['sessionToken']);
        Nav::invalidateNavData();
    }

    // =============================
    // LOGIN
    // =============================
    public static function login($userId, $userName, $userEmail)
    {
        // Inicia sesion y registra token unico para sesion simultanea
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // El menú privado se guarda en sesión. Si una cuenta inicia sesión
        // sobre una sesión anterior, debe reconstruirse con los permisos del
        // usuario nuevo y no reutilizar la navegación de la cuenta previa.
        Nav::invalidateNavData();

        $sessionToken = bin2hex(random_bytes(32));
        DaoSecurity::setActiveSessionToken($userId, $sessionToken);

        $_SESSION['login'] = [
            'isLogged' => true,
            'userId' => $userId,
            'userName' => $userName,
            'userEmail' => $userEmail,
        ];
        $_SESSION['userName'] = $userName;
        $_SESSION['userEmail'] = $userEmail;
        $_SESSION['sessionToken'] = $sessionToken;
    }

    // =============================
    // ISLOGGED
    // =============================
    public static function isLogged(): bool
    {
        // Valida sesion local y su token vigente contra base de datos
        if (!(isset($_SESSION['login']) && $_SESSION['login']['isLogged'])) {
            return false;
        }

        $userId = intval($_SESSION['login']['userId'] ?? 0);
        $sessionToken = strval($_SESSION['sessionToken'] ?? '');
        if ($userId <= 0 || $sessionToken === '') {
            self::logout();

            return false;
        }

        $activeToken = strval(DaoSecurity::getActiveSessionToken($userId));
        if ($activeToken === '' || !hash_equals($activeToken, $sessionToken)) {
            self::logout();

            return false;
        }

        return true;
    }

    // =============================
    // GETUSER
    // =============================
    public static function getUser()
    {
        // Retorna la estructura completa del usuario en sesion
        if (isset($_SESSION['login'])) {
            return $_SESSION['login'];
        }

        return false;
    }

    // =============================
    // GETUSERID
    // =============================
    public static function getUserId()
    {
        // Retorna solo el ID del usuario autenticado
        if (isset($_SESSION['login'])) {
            return $_SESSION['login']['userId'];
        }

        return 0;
    }

    // =============================
    // ISAUTHORIZED
    // =============================
    public static function isAuthorized($userId, $function, $type = 'FNC'): bool
    {
        // El administrador del sistema tiene acceso completo (userId 1)
        if (intval($userId) === 1) {
            return true;
        }
        // También se considera admin si pertenece al rol 1
        if (self::isInRol($userId, 1)) {
            return true;
        }

        // Verifica si el usuario tiene permiso para funcion/controlador
        if (Context::getContextByKey('DEVELOPMENT') == '1') {
            $functionInDb = DaoSecurity::getFeature($function);
            if (!$functionInDb) {
                DaoSecurity::addNewFeature($function, $function, 'ACT', $type);
            }
        }

        return DaoSecurity::getFeatureByUsuario($userId, $function);
    }

    // =============================
    // ISINROL
    // =============================
    public static function isInRol($userId, $rol): bool
    {
        // Verifica membresia del usuario en un rol concreto
        if (Context::getContextByKey('DEVELOPMENT') == '1') {
            $rolInDb = DaoSecurity::getRol($rol);
            if (!$rolInDb) {
                DaoSecurity::addNewRol($rol, $rol, 'ACT');
            }
        }

        return DaoSecurity::isUsuarioInRol($userId, $rol);
    }

    // =============================
    // CSRF PROTECTION
    // =============================
    public static function getCsrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = \Utilities\Validators::generateCsrfToken();
        }

        return (string)$_SESSION['_csrf_token'];
    }

    public static function isValidCsrfToken($token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $sessionToken = (string)($_SESSION['_csrf_token'] ?? '');
        $token = is_string($token) ? $token : '';

        return \Utilities\Validators::validateCsrfToken($token, $sessionToken);
    }

    public static function validateCsrfPost(): bool
    {
        return self::isValidCsrfToken($_POST['csrf_token'] ?? '');
    }

}
