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
check(strpos($citasDao, 'getAvailabilityConflicts') !== false, 'disponibilidad devuelve el detalle del conflicto');
check(strpos($citasDao, 'c.fecha_hora > DATE_SUB') !== false, 'inicio del solapamiento usa límite estricto');
check(strpos($citasDao, 'c.fecha_hora < DATE_ADD') !== false, 'fin del solapamiento usa límite estricto');
check(strpos($citasDao, 'BETWEEN DATE_SUB') === false, 'citas consecutivas de 30 minutos ya no se bloquean');
check(strpos($citasDao, 'paciente_id = NULLIF(:paciente_id, 0)') !== false, 'horarios disponibles consideran al paciente');

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
check(strpos($sql, 'CREATE TABLE IF NOT EXISTS signos_vitales') !== false, 'tabla signos vitales documentada');
check(is_file(__DIR__ . '/../scripts/migrate_expediente_clinico.sql'), 'migración de expediente presente');
$expedienteMigration = file_get_contents(
    __DIR__ . '/../scripts/migrate_expediente_clinico.sql'
);
check(
    strpos($sql, "Controllers\\\\DoctoresController") !== false,
    'permiso del portal médico documentado'
);
check(
    strpos($sql, "Controllers\\\\PacientePortalController") !== false,
    'permiso del portal paciente documentado'
);
check(
    strpos($expedienteMigration, "Menu_Doctor") !== false
        && strpos($expedienteMigration, "Menu_PacientePortal") !== false,
    'migración existente incorpora permisos y menús clínicos'
);
check(is_file(__DIR__ . '/../src/Utilities/SimplePdf.php'), 'generador PDF clínico presente');
check(is_file(__DIR__ . '/../src/Utilities/ClinicalPdf.php'), 'PDF clínico visual presente');
check(is_file(__DIR__ . '/../public/css/clinical-record.css'), 'estilos del expediente presentes');
check(is_file(__DIR__ . '/../src/Views/templates/expediente_clinico.view.tpl'), 'vista de expediente por cita presente');
$pdfPreview = (new \Utilities\ClinicalPdf())->build(
    [
        'id' => 18,
        'fecha_hora' => '2026-07-27 09:30:00',
        'nombre_estado' => 'Finalizada',
        'paciente_nombres' => 'Paciente',
        'paciente_apellidos' => 'Prueba',
        'identidad' => '0801-2000-00001',
        'medico_nombres' => 'Médico',
        'medico_apellidos' => 'Prueba',
        'nombre_especialidad' => 'Medicina General',
        'centro_nombre' => 'SmartClinic Center',
        'temperatura' => '36.7',
        'presion_sistolica' => '118',
        'presion_diastolica' => '76',
        'frecuencia_cardiaca' => '72',
        'frecuencia_respiratoria' => '16',
        'saturacion_oxigeno' => '98',
        'peso' => '68.5',
        'talla' => '170',
        'signos_notas' => 'Paciente estable.',
        'motivo_consulta' => 'Control general',
        'diagnostico' => 'Paciente clínicamente estable',
        'tratamiento' => 'Continuar hábitos saludables',
        'observaciones' => 'Control en seis meses',
    ],
    [['medicamento' => 'Indicaciones generales', 'indicaciones' => 'Hidratación adecuada']]
);
check(str_starts_with($pdfPreview, '%PDF-1.4'), 'PDF clínico genera un documento válido');
check(strlen($pdfPreview) > 2500, 'PDF clínico contiene el expediente completo');
check(strpos($sql, 'CREATE TABLE IF NOT EXISTS pago_factura') !== false, 'tabla pagos documentada');
check(strpos($sql, 'CREATE TABLE IF NOT EXISTS notificaciones') !== false, 'tabla notificaciones documentada');
check(strpos($sql, 'CREATE TABLE IF NOT EXISTS password_reset_tokens') !== false, 'tabla recuperación de contraseña documentada');
check(strpos($sql, '-- cambios para agregar el catálogo de centros de salud') !== false, 'bloque de cambios de centros de salud documentado');
check(strpos($sql, 'CREATE TABLE IF NOT EXISTS centro_salud') !== false, 'tabla centros de salud documentada');
check(strpos($sql, "Controllers\\\\CentrosSaludController") !== false, 'permiso del controlador de centros de salud documentado');
check(is_file(__DIR__ . '/../src/Dao/CentroSalud.php'), 'DAO comentado de centros de salud presente');
check(is_file(__DIR__ . '/../src/Controllers/CentrosSaludController.php'), 'controlador de centros de salud presente');
check(is_file(__DIR__ . '/../src/Views/templates/centros_salud.view.tpl'), 'vista del catálogo de centros de salud presente');
check(strpos($sql, 'CREATE TABLE IF NOT EXISTS medico_centro_salud') !== false, 'relación médico-centro documentada');
check(strpos($sql, "'SmartClinic Center'") !== false, 'centro de salud predeterminado documentado');
check(strpos($sql, "LPAD(m.id, 2, '0')") !== false, 'consultorios iniciales únicos por médico documentados');
check(strpos($sql, '-- cambios para evitar consultorios duplicados en un centro de salud') !== false, 'migración de consultorios únicos documentada');
check(strpos($sql, 'consultorio_activo') !== false, 'columna activa para unicidad de consultorio documentada');
check(strpos($sql, 'uq_centro_consultorio_activo') !== false, 'índice único de consultorio activo documentado');
check(strpos($sql, '-- cambios para aplicar centros de salud a las citas') !== false, 'migración de centros en citas documentada');
check(strpos($sql, 'ADD COLUMN IF NOT EXISTS centro_salud_id') !== false, 'columna centro de salud de citas documentada');
check(strpos($sql, 'fk_cita_medico_centro') !== false, 'integridad médico-centro de citas documentada');
check(strpos($sql, '-- cambios para aplicar centros de salud a los ajustes de inventario') !== false, 'migración de centros en ajustes documentada');
check(strpos($sql, 'fk_ajuste_inventario_centro_salud') !== false, 'integridad centro-ajuste documentada');
check(is_file(__DIR__ . '/../src/Dao/MedicoCentroSalud.php'), 'DAO comentado de relación médico-centro presente');
$medicoCentroDao = file_get_contents(__DIR__ . '/../src/Dao/MedicoCentroSalud.php');
check(strpos($medicoCentroDao, 'findActiveConsultorioConflict') !== false, 'DAO valida consultorio activo único por centro');

$medicosDao = file_get_contents(__DIR__ . '/../src/Dao/Medicos.php');
check(strpos($medicosDao, 'insertMedicoConCentros') !== false, 'alta transaccional de médico y centros presente');
check(strpos($medicosDao, 'updateMedicoConCentros') !== false, 'edición transaccional de médico y centros presente');

$medicoCreate = file_get_contents(__DIR__ . '/../src/Views/templates/medico_create.view.tpl');
$medicoEdit = file_get_contents(__DIR__ . '/../src/Views/templates/medico_edit.view.tpl');
check(strpos($medicoCreate, 'name="centro_ids[]"') !== false, 'alta de médico permite seleccionar centros');
check(strpos($medicoEdit, 'name="centro_ids[]"') !== false, 'edición de médico permite seleccionar centros');

$citasController = file_get_contents(__DIR__ . '/../src/Controllers/CitasController.php');
$citaCreate = file_get_contents(__DIR__ . '/../src/Views/templates/cita_agendar.view.tpl');
$citaEdit = file_get_contents(__DIR__ . '/../src/Views/templates/cita_edit.view.tpl');
$pacientePortal = file_get_contents(__DIR__ . '/../src/Views/templates/paciente_portal.view.tpl');
$messageNotifier = file_get_contents(__DIR__ . '/../src/Utilities/MessageNotifier.php');
check(strpos($citasDao, 'centro_salud_id') !== false, 'DAO de citas persiste el centro de salud');
check(strpos($citasDao, 'centro_nombre') !== false, 'DAO de citas devuelve la ubicación');
check(strpos($citasController, 'availableCenters') !== false, 'citas expone centros activos por médico');
check(strpos($citasController, 'getActivoByMedicoCentro') !== false, 'citas valida la asignación médico-centro');
check(strpos($citaCreate, 'name="centro_salud_id"') !== false, 'alta de cita solicita centro de salud');
check(strpos($citaEdit, 'name="centro_salud_id"') !== false, 'edición de cita solicita centro de salud');
check(strpos($pacientePortal, 'name="centro_salud_id"') !== false, 'portal del paciente solicita centro de salud');
check(strpos($messageNotifier, 'Centro de salud: %s') !== false, 'mensaje de cita incluye centro de salud');
check(strpos($citasDao, 'paciente_telefono') !== false, 'DAO de citas devuelve teléfono del paciente');
check(strpos($citasController, "case 'notify':") !== false, 'citas expone notificación manual');
check(strpos($citasController, "\$_POST['notify_patient']") !== false, 'alta de cita respeta notificación inmediata opcional');
check(strpos($citasController, "strtotime(\$b['fecha_hora'] ?? '') <=> strtotime(\$a['fecha_hora'] ?? '')") !== false, 'lista de citas ordenada por fecha y hora descendente');
check(strpos($citasController, 'buildAvailabilityConflictMessage') !== false, 'citas explica si el conflicto es del médico o paciente');
check(strpos($citaCreate, 'data-telefono="{{telefono}}"') !== false, 'alta muestra teléfono del paciente seleccionado');
check(strpos($citaCreate, '&paciente_id=') !== false, 'alta recarga horarios con el paciente seleccionado');
check(strpos($citaEdit, '&paciente_id=') !== false, 'edición recarga horarios con el paciente seleccionado');
check(strpos($citaCreate, 'name="notify_patient"') !== false, 'alta pregunta por notificación inmediata');
check(strpos($citaCreate, 'id="appointment_confirmation"') !== false, 'alta confirma que los datos de la cita sean correctos');
check(strpos($citaCreate, 'id="notify_patient_choice"') !== false, 'confirmación de alta contiene la opción de notificar');
check(strpos($citaCreate, 'name="notify_patient" value="1"') === false, 'opción de notificar ya no aparece directamente en el formulario');

$citasView = file_get_contents(__DIR__ . '/../src/Views/templates/citas.view.tpl');
check(strpos($citasView, 'action=notify') !== false, 'lista de citas permite notificar al paciente');
check(strpos($pacientePortal, 'id="portal_paciente_id"') !== false, 'portal del paciente consulta disponibilidad propia');
check(strpos($pacientePortal, '<select id="hora"') !== false, 'portal del paciente muestra solo horas disponibles');

$inventoryController = file_get_contents(__DIR__ . '/../src/Controllers/InventarioController.php');
$adjustmentDao = file_get_contents(__DIR__ . '/../src/Dao/AjusteInventario.php');
$movementDao = file_get_contents(__DIR__ . '/../src/Dao/MovimientoInventario.php');
$adjustmentView = file_get_contents(__DIR__ . '/../src/Views/templates/inventario_ajustar.view.tpl');
$inventoryView = file_get_contents(__DIR__ . '/../src/Views/templates/inventario.view.tpl');
$kardexView = file_get_contents(__DIR__ . '/../src/Views/templates/inventario_kardex.view.tpl');
check(strpos($inventoryController, 'DaoCentroSalud::getActivos') !== false, 'ajustes cargan centros de salud activos');
check(strpos($inventoryController, 'registerWithStockChange') !== false, 'ajuste y stock se registran atómicamente');
check(strpos($adjustmentDao, 'SELECT id, stock_actual') !== false, 'DAO de ajustes bloquea el producto antes de modificar stock');
check(strpos($adjustmentDao, 'centro_salud_id') !== false, 'DAO de ajustes persiste el centro de salud');
check(strpos($movementDao, 'cs.nombre') !== false, 'movimientos devuelven el centro del ajuste');
check(strpos($movementDao, "'Inventario general'") !== false, 'compras globales tienen ubicación descriptiva');
check(strpos($adjustmentView, 'name="centro_salud_id"') !== false, 'formulario de ajuste exige centro de salud');
check(strpos($inventoryView, '{{centro_nombre}}') !== false, 'movimientos recientes muestran centro de salud');
check(strpos($kardexView, '{{centro_nombre}}') !== false, 'kárdex muestra centro de salud');

$navConfig = file_get_contents(__DIR__ . '/../nav.config.json');
check(strpos($navConfig, 'ReportesController') !== false, 'menú de reportes registrado');
check(strpos($navConfig, 'AuditController') !== false, 'menú de bitácora registrado');
check(strpos($navConfig, 'DoctoresController') !== false, 'menú portal doctor registrado');
check(strpos($navConfig, 'PacientePortalController') !== false, 'menú portal paciente registrado');
check(strpos($navConfig, 'PagosController') !== false, 'menú pagos registrado');
check(strpos($navConfig, 'NotificacionesController') !== false, 'menú notificaciones registrado');
check(strpos($navConfig, 'BIController') !== false, 'menú BI registrado');
check(strpos($navConfig, 'Menu_CentrosSalud') !== false, 'menú centros de salud registrado');

$privateLayout = file_get_contents(__DIR__ . '/../src/Views/templates/privatelayout.view.tpl');
check(strpos($privateLayout, 'skip-link') !== false, 'mejora de accesibilidad skip-link presente');
check(strpos($privateLayout, 'smartclinic-confirm.js') !== false, 'layout carga confirmaciones globales');


echo PHP_EOL . "Validaciones rápidas finalizadas correctamente." . PHP_EOL;
