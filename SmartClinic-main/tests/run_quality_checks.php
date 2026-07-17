<?php
session_start();
require __DIR__ . '/../autoload.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FALLÓ: " . $message . PHP_EOL);
        exit(1);
    }
    echo "OK: " . $message . PHP_EOL;
}

$_SERVER['REQUEST_URI'] = '/index.php';
\Utilities\Site::configure();

check(\Utilities\Validators::IsValidEmail('recepcion@smartclinic.com'), 'email válido');
check(!\Utilities\Validators::IsValidEmail('correo-sin-arroba'), 'email inválido rechazado');
check(\Utilities\Validators::IsValidPassword('SmartClinic#2026'), 'contraseña fuerte válida');
check(!\Utilities\Validators::IsValidPassword('123456'), 'contraseña débil rechazada');
check(\Utilities\Validators::sanitizeDate('2026-06-30') === '2026-06-30', 'fecha válida');
check(\Utilities\Validators::sanitizeDate('30/06/2026') === null, 'fecha con formato inválido rechazada');
check(\Utilities\Validators::sanitizeTime('07:30') === '07:30', 'hora válida');
check(\Utilities\Validators::sanitizeTime('25:00') === null, 'hora inválida rechazada');

$routes = [
    'CitasController' => 'Controllers\\CitasController',
    'citascontroller' => 'Controllers\\CitasController',
    'PacientesController' => 'Controllers\\PacientesController',
    'MedicosController' => 'Controllers\\MedicosController',
    'Home' => 'Controllers\\HomeController',
    'Sec_Login' => 'Controllers\\Sec\\Login',
    'Security_User' => 'Controllers\\Security\\User',
];
foreach ($routes as $page => $expected) {
    $_GET = ['page' => $page];
    $_SERVER['REQUEST_URI'] = '/index.php?page=' . urlencode($page);
    check(\Utilities\Site::getPageRequest() === $expected, 'ruta ' . $page . ' resuelve a ' . $expected);
}

$token = \Utilities\Security::getCsrfToken();
$_POST['csrf_token'] = $token;
check(\Utilities\Security::validateCsrfPost(), 'CSRF válido aceptado');
$_POST['csrf_token'] = 'token-invalido';
check(!\Utilities\Security::validateCsrfPost(), 'CSRF inválido rechazado');

$citasDao = file_get_contents(__DIR__ . '/../src/Dao/Citas.php');
check(strpos($citasDao, 'estado_id != 2') === false, 'la disponibilidad ya no excluye citas confirmadas');
check(strpos($citasDao, 'estado_id NOT IN (4, 5)') !== false, 'la disponibilidad excluye solo estados no activos');

check(is_file(__DIR__ . '/../Dockerfile'), 'Dockerfile presente');
check(is_file(__DIR__ . '/../docker-compose.yml'), 'docker-compose.yml presente');
check(is_file(__DIR__ . '/../.devcontainer/devcontainer.json'), 'Dev Container presente');

check(is_file(__DIR__ . '/../src/Utilities/AuditLogger.php'), 'bitácora AuditLogger presente');
check(is_file(__DIR__ . '/../src/Controllers/AuditController.php'), 'controlador de bitácora presente');
check(is_file(__DIR__ . '/../src/Controllers/ReportesController.php'), 'controlador de reportes presente');
check(is_file(__DIR__ . '/../public/js/smartclinic-confirm.js'), 'confirmaciones de acciones críticas presentes');
check(is_file(__DIR__ . '/../docs/RESPALDO_BASE_DATOS.md'), 'documentación de respaldo de base de datos presente');
check(is_file(__DIR__ . '/../scripts/backup_database.sh'), 'script de respaldo presente');
check(is_file(__DIR__ . '/../scripts/restore_database.sh'), 'script de restauración presente');

check(is_file(__DIR__ . '/../src/Controllers/DoctoresController.php'), 'portal de doctores presente');
check(is_file(__DIR__ . '/../src/Controllers/PacientePortalController.php'), 'portal de paciente presente');
check(is_file(__DIR__ . '/../src/Controllers/PagosController.php'), 'módulo de pagos presente');
check(is_file(__DIR__ . '/../src/Controllers/NotificacionesController.php'), 'notificaciones internas presentes');
check(is_file(__DIR__ . '/../src/Controllers/PasswordRecoveryController.php'), 'recuperación de contraseña presente');
check(is_file(__DIR__ . '/../src/Controllers/BIController.php'), 'módulo BI presente');
check(is_file(__DIR__ . '/../src/Dao/ClinicaAvanzada.php'), 'DAO clínico avanzado presente');
$sql = file_get_contents(__DIR__ . '/../docs/smartclinic_1p.sql');
check(strpos($sql, 'CREATE TABLE IF NOT EXISTS historial_medico') !== false, 'tabla historial médico documentada');
check(strpos($sql, 'CREATE TABLE IF NOT EXISTS receta_medica') !== false, 'tabla recetas documentada');
check(strpos($sql, 'CREATE TABLE IF NOT EXISTS pago_factura') !== false, 'tabla pagos documentada');
check(strpos($sql, 'CREATE TABLE IF NOT EXISTS notificaciones') !== false, 'tabla notificaciones documentada');
check(strpos($sql, 'CREATE TABLE IF NOT EXISTS password_reset_tokens') !== false, 'tabla recuperación de contraseña documentada');


$navConfig = file_get_contents(__DIR__ . '/../nav.config.json');
check(strpos($navConfig, 'ReportesController') !== false, 'menú de reportes registrado');
check(strpos($navConfig, 'AuditController') !== false, 'menú de bitácora registrado');
check(strpos($navConfig, 'DoctoresController') !== false, 'menú portal doctor registrado');
check(strpos($navConfig, 'PacientePortalController') !== false, 'menú portal paciente registrado');
check(strpos($navConfig, 'PagosController') !== false, 'menú pagos registrado');
check(strpos($navConfig, 'NotificacionesController') !== false, 'menú notificaciones registrado');
check(strpos($navConfig, 'BIController') !== false, 'menú BI registrado');

$privateLayout = file_get_contents(__DIR__ . '/../src/Views/templates/privatelayout.view.tpl');
check(strpos($privateLayout, 'skip-link') !== false, 'mejora de accesibilidad skip-link presente');
check(strpos($privateLayout, 'smartclinic-confirm.js') !== false, 'layout carga confirmaciones globales');


echo PHP_EOL . "Validaciones rápidas finalizadas correctamente." . PHP_EOL;
