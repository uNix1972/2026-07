<?php
namespace Dao\Security;

if (version_compare(phpversion(), '7.4.0', '<')) {
    define('PASSWORD_ALGORITHM', 1);  //BCRYPT
} else {
    define('PASSWORD_ALGORITHM', '2y');  //BCRYPT
}
/*
usercod     bigint(10) AI PK
useremail   varchar(80)
username    varchar(80)
userpswd    varchar(128)
userfching  datetime
userpswdest char(3)
userpswdexp datetime
userest     char(3)
useractcod  varchar(128)
userpswdchg varchar(128)
usertipo    char(3)
*/

use Exception;

class Security extends \Dao\Table
{
    // =============================
    // GETUSUARIOS
    // =============================
    static public function getUsuarios($filter = "", $page = -1, $items = 0)
    {
        // Lista usuarios con opciaIn baesica de filtro/paginacion
        $sqlstr = "";
        if ($filter == "" && $page == -1 && $items == 0) {
            $sqlstr = "SELECT * FROM usuario;";
        } else {
            //TODO: Terminar consultas FACET
            if ($page == -1 and $items == 0) {
                $sqlstr = sprintf("SELECT * FROM usuario %s;", $filter);
            } else {
                $offset = ($page - 1 * $items);
                $sqlstr = sprintf(
                    "SELECT * FROM usuario %s limit %d, %d;",
                    $filter,
                    $offset,
                    $items
                );
            }
        }
        return self::obtenerRegistros($sqlstr, array());
    }

    // =============================
    // NEWUSUARIO
    // =============================
    static public function newUsuario($email, $password)
    {
        // Crea usuario nuevo con validaciaIn y hash de contrasena
        if (!\Utilities\Validators::IsValidEmail($email)) {
            throw new Exception("Correo no es válido");
        }
        if (!\Utilities\Validators::IsValidPassword($password)) {
            throw new Exception("Contraseña debe ser almenos 8 caracteres, 1 número, 1 mayúscula, 1 símbolo especial");
        }

        $newUser = self::_usuarioStruct();
        //Tratamiento de la ContraseaNa
        $hashedPassword = self::_hashPassword($password);

        unset($newUser["usercod"]);
        unset($newUser["userfching"]);
        unset($newUser["userpswdchg"]);

        $newUser["useremail"]    = $email;
        $newUser["username"]     = "John Doe";
        $newUser["userpswd"]     = $hashedPassword;
        $newUser["userpswdest"]  = Estados::ACTIVO;
        $newUser["userpswdexp"]  = date('Y-m-d', time() + 7776000);  //(3*30*24*60*60) (m d h mi s)
        $newUser["userest"]      = Estados::ACTIVO;
        $newUser["useractcod"]   = hash("sha256", $email . time());
        $newUser["usertipo"]     = UsuarioTipo::PUBLICO;

        $sqlIns = "INSERT INTO `usuario` (`useremail`, `username`, `userpswd`,
            `userfching`, `userpswdest`, `userpswdexp`, `userest`, `useractcod`,
            `userpswdchg`, `usertipo`)
            VALUES
            ( :useremail, :username, :userpswd,
            now(), :userpswdest, :userpswdexp, :userest, :useractcod,
            now(), :usertipo);";

        return self::executeNonQuery($sqlIns, $newUser);
    }

    // =============================
    // GETUSUARIOBYEMAIL
    // =============================
    static public function getUsuarioByEmail($email)
    {
        // Busca usuario por correo para autenticaciaIn
        $sqlstr = "SELECT * from `usuario` where `useremail` = :useremail ;";
        $params = array("useremail" => $email);

        return self::obtenerUnRegistro($sqlstr, $params);
    }

    // =============================
    // GETUSUARIOBYID
    // =============================
    static public function getUsuarioById($usercod)
    {
        // Busca usuario por ID
        $sqlstr = "SELECT * from `usuario` where `usercod` = :usercod ;";
        $params = array("usercod" => $usercod);

        return self::obtenerUnRegistro($sqlstr, $params);
    }

    // =============================
    // SETACTIVESESSIONTOKEN
    // =============================
    static public function setActiveSessionToken($usercod, $token)
    {
        // Guarda token de sesion activa para politica de sesion unica
        $sqlstr = "UPDATE `usuario` SET `useractcod` = :useractcod WHERE `usercod` = :usercod;";
        $params = array(
            "useractcod" => $token,
            "usercod" => $usercod
        );

        return self::executeNonQuery($sqlstr, $params);
    }

    // =============================
    // GETACTIVESESSIONTOKEN
    // =============================
    static public function getActiveSessionToken($usercod)
    {
        // Retorna token de sesion activa del usuario
        $sqlstr = "SELECT `useractcod` FROM `usuario` WHERE `usercod` = :usercod LIMIT 1;";
        $params = array("usercod" => $usercod);
        $record = self::obtenerUnRegistro($sqlstr, $params);
        return $record["useractcod"] ?? "";
    }

    // =============================
    // CLEARACTIVESESSIONTOKEN
    // =============================
    static public function clearActiveSessionToken($usercod)
    {
        // Limpia token activo al cerrar sesion
        return self::setActiveSessionToken($usercod, "");
    }

    // =============================
    // UPDATEUSUARIONOMBRE
    // =============================
    static public function updateUsuarioNombre($usercod, $username)
    {
        // Actualiza nombre visible del usuario
        $sqlstr = "UPDATE `usuario` SET `username` = :username WHERE `usercod` = :usercod;";
        $params = array(
            "username" => $username,
            "usercod" => $usercod
        );

        return self::executeNonQuery($sqlstr, $params);
    }

    // =============================
    // _SALTPASSWORD
    // =============================
    static private function _saltPassword($password)
    {
        // Aplica sal HMAC antes del hash final
        return hash_hmac(
            "sha256",
            $password,
            \Utilities\Context::getContextByKey("PWD_HASH")
        );
    }

    // =============================
    // _HASHPASSWORD
    // =============================
    static private function _hashPassword($password)
    {
        // Aplica hash bcrypt sobre contrasena salteada
        return password_hash(self::_saltPassword($password), PASSWORD_ALGORITHM);
    }

    // =============================
    // HASHPASSWORDPUBLIC
    // =============================
    static public function hashPasswordPublic($password)
    {
        // Expone hash de contrasena para otros maIdulos
        return self::_hashPassword($password);
    }

    // =============================
    // VERIFYPASSWORD
    // =============================
    static public function verifyPassword($raw_password, $hash_password)
    {
        // Compara contrasena plana contra hash almacenado
        return password_verify(
            self::_saltPassword($raw_password),
            $hash_password
        );
    }

    // =============================
    // _USUARIOSTRUCT
    // =============================
    static private function _usuarioStruct()
    {
        // Plantilla base de campos de usuario
        return array(
            "usercod"     => "",
            "useremail"   => "",
            "username"    => "",
            "userpswd"    => "",
            "userfching"  => "",
            "userpswdest" => "",
            "userpswdexp" => "",
            "userest"     => "",
            "useractcod"  => "",
            "userpswdchg" => "",
            "usertipo"    => "",
        );
    }

    // =============================
    // GETFEATURE
    // =============================
    static public function getFeature($fncod)
    {
        // Verifica existencia de una funcion/permiso
        $sqlstr = "SELECT * from funciones where funcionNombre=:funcionNombre;";
        $featuresList = self::obtenerRegistros($sqlstr, array("funcionNombre" => $fncod));
        return count($featuresList) > 0;
    }

    // =============================
    // ADDNEWFEATURE
    // =============================
    static public function addNewFeature($fncod, $fndsc, $fnest, $fntyp)
    {
        // Registra una funcion nueva en catalogo de permisos
        $sqlins = "INSERT INTO `funciones` (`funcionNombre`, `funcionDescripcion`, `funcionStatus`)
        VALUES (:funcionNombre, :funcionDescripcion, :funcionStatus);";

        return self::executeNonQuery(
            $sqlins,
            array(
                "funcionNombre" => $fncod,
                "funcionDescripcion" => $fndsc,
                "funcionStatus" => $fnest
            )
        );
    }

    // =============================
    // GETFEATUREBYUSUARIO
    // =============================
    static public function getFeatureByUsuario($userCod, $fncod)
    {
        // Determina si un usuario tiene permiso especifico
        $sqlstr = "SELECT f.funcionId
            FROM funciones f
            INNER JOIN funciones_roles fr ON fr.funcionId = f.funcionId
            INNER JOIN roles_usuarios ru ON ru.rolId = fr.rolId
            WHERE f.funcionNombre = :funcionNombre
              AND f.funcionStatus = 'ACT'
              AND fr.frStatus = 'ACT'
              AND ru.ruStatus = 'ACT'
              AND ru.usuarioId = :usuarioId
            LIMIT 1;";
        $resultados = self::obtenerRegistros(
            $sqlstr,
            array(
                "usuarioId" => $userCod,
                "funcionNombre" => $fncod
            )
        );
        return count($resultados) > 0;
    }

    // =============================
    // GETROL
    // =============================
    static public function getRol($rolescod)
    {
        // Verifica existencia de rol por ID o nombre
        $params = array();
        if (is_numeric($rolescod)) {
            $sqlstr = "SELECT * from roles where rolId=:rolId;";
            $params = array("rolId" => $rolescod);
        } else {
            $sqlstr = "SELECT * from roles where rolNombre=:rolNombre;";
            $params = array("rolNombre" => $rolescod);
        }
        $rolesList = self::obtenerRegistros($sqlstr, $params);
        return count($rolesList) > 0;
    }

    // =============================
    // ADDNEWROL
    // =============================
    static public function addNewRol($rolescod, $rolesdsc, $rolesest)
    {
        // Registra rol nuevo por codigo numerico o nombre
        if (is_numeric($rolescod)) {
            $sqlins = "INSERT INTO `roles` (`rolId`, `rolNombre`, `rolDescripcion`, `rolStatus`)
            VALUES (:rolId, :rolNombre, :rolDescripcion, :rolStatus);";
            return self::executeNonQuery(
                $sqlins,
                array(
                    "rolId" => $rolescod,
                    "rolNombre" => "ROL_" . $rolescod,
                    "rolDescripcion" => $rolesdsc,
                    "rolStatus" => $rolesest
                )
            );
        }

        $sqlins = "INSERT INTO `roles` (`rolNombre`, `rolDescripcion`, `rolStatus`)
        VALUES (:rolNombre, :rolDescripcion, :rolStatus);";

        return self::executeNonQuery(
            $sqlins,
            array(
                "rolNombre" => $rolescod,
                "rolDescripcion" => $rolesdsc,
                "rolStatus" => $rolesest
            )
        );
    }

    // =============================
    // ISUSUARIOINROL
    // =============================
    static public function isUsuarioInRol($userCod, $rolescod)
    {
        // Determina si un usuario pertenece a un rol
        $sqlstr = "SELECT r.rolId
            FROM roles r
            INNER JOIN roles_usuarios ru ON ru.rolId = r.rolId
            WHERE r.rolStatus = 'ACT'
              AND ru.ruStatus = 'ACT'
              AND ru.usuarioId = :usuarioId
              AND r.rolId = :rolId
            LIMIT 1;";
        $resultados = self::obtenerRegistros(
            $sqlstr,
            array(
                "usuarioId" => $userCod,
                "rolId" => $rolescod
            )
        );
        return count($resultados) > 0;
    }

    // =============================
    // GETROLESBYUSUARIO
    // =============================
    static public function getRolesByUsuario($userCod)
    {
        // Devuelve roles activos asociados al usuario
        $sqlstr = "SELECT r.*
            FROM roles r
            INNER JOIN roles_usuarios ru ON ru.rolId = r.rolId
            WHERE r.rolStatus = 'ACT'
              AND ru.ruStatus = 'ACT'
              AND ru.usuarioId = :usuarioId;";
        $resultados = self::obtenerRegistros(
            $sqlstr,
            array(
                "usuarioId" => $userCod
            )
        );
        return $resultados;
    }

    // =============================
    // REMOVEROLFROMUSER
    // =============================
    static public function removeRolFromUser($userCod, $rolescod)
    {
        // Desactiva un rol puntual para el usuario
        $sqldel = "UPDATE roles_usuarios set ruStatus='INA'
        where rolId=:rolId and usuarioId=:usuarioId;";
        return self::executeNonQuery(
            $sqldel,
            array("rolId" => $rolescod, "usuarioId" => $userCod)
        );
    }

    // =============================
    // REMOVEFEATUREFROMROL
    // =============================
    static public function removeFeatureFromRol($fncod, $rolescod)
    {
        // Desactiva una funcion asociada a un rol
        $sqldel = "UPDATE funciones_roles fr
            INNER JOIN funciones f ON f.funcionId = fr.funcionId
            SET fr.frStatus='INA'
            WHERE f.funcionNombre=:funcionNombre AND fr.rolId=:rolId;";
        return self::executeNonQuery(
            $sqldel,
            array("funcionNombre" => $fncod, "rolId" => $rolescod)
        );
    }

    // ─── NUEVOS METODOS ──────────────────────────────────────────────────────────

    /**
     * Desactiva todos los roles activos de un usuario
     * Se llama antes de asignar el nuevo rol para evitar duplicados activos
     */
    // =============================
    // REMOVEALLROLESFROMUSER
    // =============================
    static public function removeAllRolesFromUser($userCod)
    {
        // Desactiva roles activos para reasignaciaIn limpia
        $sql = "UPDATE roles_usuarios SET ruStatus = 'INA' WHERE usuarioId = :usuarioId";
        return self::executeNonQuery($sql, array("usuarioId" => $userCod));
    }

    /**
     * Asigna un rol a un usuario
     * Si ya existe el registro lo reactiva; si no, inserta uno nuevo
     */
    // =============================
    // ASSIGNROLTOUSER
    // =============================
    static public function assignRolToUser($userCod, $rolescod)
    {
        // Asigna rol; reactiva si ya existaAa registro previo
        $sqlCheck = "SELECT * FROM roles_usuarios WHERE usuarioId = :usuarioId AND rolId = :rolId";
        $exists   = self::obtenerRegistros(
            $sqlCheck,
            array("usuarioId" => $userCod, "rolId" => $rolescod)
        );

        if (count($exists) > 0) {
            // Ya existe: solo reactivar
            $sql = "UPDATE roles_usuarios SET ruStatus = 'ACT', ruFechaInicio = now(), ruFechaFin = '2099-12-31 23:59:59'
                    WHERE usuarioId = :usuarioId AND rolId = :rolId";
            return self::executeNonQuery(
                $sql,
                array("usuarioId" => $userCod, "rolId" => $rolescod)
            );
        } else {
            // No existe: insertar nuevo
            $sql = "INSERT INTO roles_usuarios (usuarioId, rolId, ruStatus, ruFechaInicio, ruFechaFin)
                    VALUES (:usuarioId, :rolId, 'ACT', now(), '2099-12-31 23:59:59')";
            return self::executeNonQuery(
                $sql,
                array("usuarioId" => $userCod, "rolId" => $rolescod)
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────

    // =============================
    // GETUNASSIGNEDFEATURES
    // =============================
    static public function getUnAssignedFeatures($rolescod)
    {
        // Pendiente: funciones no asignadas al rol
        // TODO: implementar
    }

    // =============================
    // GETUNASSIGNEDROLES
    // =============================
    static public function getUnAssignedRoles($userCod)
    {
        // Pendiente: roles no asignados al usuario
        // TODO: implementar
    }

    // =============================
    // __CONSTRUCT
    // =============================
    private function __construct()
    {
        // Evita instanciacion de clase estatica
    }

    // =============================
    // __CLONE
    // =============================
    private function __clone()
    {
        // Evita clonacion de clase estatica
    }
}