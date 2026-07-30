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
$_SERVER['SCRIPT_NAME'] = '/index.php';
\Utilities\Site::configure();

check(
    \Utilities\Context::getContextByKey('BASE_DIR') === '',
    'ruta base vacia funciona cuando la aplicacion esta en la raiz'
);
$_SERVER['SCRIPT_NAME'] =
    '/SmartClinic_Final/SmartClinic-main/index.php';
\Utilities\Site::configure();
check(
    \Utilities\Context::getContextByKey('BASE_DIR')
        === '/SmartClinic_Final/SmartClinic-main',
    'ruta base se detecta cuando la aplicacion esta en una subcarpeta'
);
$_SERVER['SCRIPT_NAME'] = '/index.php';
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
check(
    is_file(__DIR__ . '/../src/Controllers/ContactoMensajesController.php'),
    'buzon administrativo de contacto presente'
);
check(
    is_file(__DIR__ . '/../src/Dao/ContactoMensaje.php'),
    'DAO comentado de mensajes de contacto presente'
);
$contactController = file_get_contents(
    __DIR__ . '/../src/Controllers/Contacto.php'
);
$contactAdminController = file_get_contents(
    __DIR__ . '/../src/Controllers/ContactoMensajesController.php'
);
$contactDao = file_get_contents(
    __DIR__ . '/../src/Dao/ContactoMensaje.php'
);
$contactView = file_get_contents(
    __DIR__ . '/../src/Views/templates/contacto_mensajes.view.tpl'
);
check(
    strpos($contactController, 'ContactoMensaje::insert') !== false
        && strpos($contactController, 'file_put_contents') === false,
    'contacto publico persiste en MariaDB y no en JSON'
);
check(
    strpos($contactDao, 'INSERT INTO contacto_mensaje') !== false
        && strpos($contactDao, 'getCounts()') !== false
        && strpos($contactDao, 'setStatus(') !== false,
    'DAO de contacto cubre alta, resumen y seguimiento'
);
check(
    strpos($contactAdminController, 'Security::validateCsrfPost') !== false
        && strpos($contactAdminController, 'AuditLogger::log') !== false
        && strpos(
            $contactAdminController,
            '$mensaje["csrf_token"] = $csrfToken'
        ) !== false
        && strpos($contactView, 'contact-inbox__table') !== false
        && strpos($contactView, 'contact-inbox__cell-stack') !== false
        && strpos($contactView, 'IP:') === false,
    'buzon de contacto protege y audita los cambios de estado'
);

$biController = file_get_contents(
    __DIR__ . '/../src/Controllers/BIController.php'
);
$advancedClinicDao = file_get_contents(
    __DIR__ . '/../src/Dao/ClinicaAvanzada.php'
);
$biView = file_get_contents(
    __DIR__ . '/../src/Views/templates/bi_dashboard.view.tpl'
);
$biPrintView = file_get_contents(
    __DIR__ . '/../src/Views/templates/bi_report_print.view.tpl'
);
$biCss = file_get_contents(__DIR__ . '/../public/css/main.css');
check(
    strpos($biController, "Validators::sanitizeId") !== false
        && strpos($biController, "DaoCentroSalud::getAll") !== false
        && strpos($biController, "getMetricasBI(\$centroSaludId)")
            !== false,
    'controlador BI exige un centro de salud valido'
);
check(
    strpos(
        $advancedClinicDao,
        'getMetricasBI(int $centroSaludId)'
    ) !== false
        && substr_count(
            $advancedClinicDao,
            'c.centro_salud_id = :'
        ) >= 3,
    'metricas BI filtran citas e ingresos por centro'
);
check(
    strpos($advancedClinicDao, 'getResumenBI(int $centroSaludId)')
        !== false
        && strpos(
            $advancedClinicDao,
            'INNER JOIN cita c ON c.id = pf.cita_id'
        ) !== false,
    'resumen e ingresos BI se vinculan al centro de cada cita'
);
check(
    strpos($advancedClinicDao, 'AS citas_mes_actual') !== false
        && strpos($advancedClinicDao, 'AS ingresos_mes_actual') !== false
        && strpos($biController, "'citasMesActual'") !== false
        && strpos($biController, "'ingresosMesActual'") !== false,
    'resumen BI calcula citas e ingresos del mes por centro'
);
check(
    strpos($biView, 'Citas del mes') !== false
        && strpos($biView, '{{citasMesActual}}') !== false
        && strpos($biView, 'Ingresos del mes') !== false
        && strpos($biView, '{{ingresosMesActual}}') !== false,
    'tablero BI muestra los dos indicadores mensuales'
);
check(
    strpos($biView, 'name="centro_salud_id"') !== false
        && strpos($biView, '{{foreach centros}}') !== false
        && strpos($biView, 'Todos los centros') === false,
    'tablero BI obliga a seleccionar un unico centro'
);
check(
    strpos($biCss, '.bi-center-filter') !== false
        && strpos($biCss, '.bi-summary-grid') !== false
        && strpos($biCss, '.bi-bar__track') !== false,
    'tablero BI tiene estilos de selector, resumen y graficas'
);
check(
    strpos($biController, 'addPieMetadata(') !== false
        && strpos($biController, "'piePercentage'") !== false
        && strpos($biController, "'pieOffset'") !== false,
    'controlador BI prepara porcentajes y segmentos de pastel'
);
check(
    substr_count($biView, 'class="bi-pie"') === 2
        && strpos(
            $biView,
            'Distribución porcentual de citas por estado'
        ) !== false
        && strpos(
            $biView,
            'Distribución porcentual de citas por médico'
        ) !== false
        && strpos($biCss, '.bi-pie__segment') !== false,
    'tablero BI muestra graficas de pastel accesibles'
);
check(
    strpos($biView, 'Reportes imprimibles') !== false
        && strpos($biView, 'name="report_type"') !== false
        && strpos($biView, 'name="centro_salud_id"') !== false
        && strpos($biView, 'target="_blank"') !== false,
    'tablero BI conserva sus indicadores y agrega generador de reportes'
);
check(
    strpos($biController, "(\$_GET['action'] ?? '') === 'report'")
        !== false
        && strpos($biController, 'renderPrintableReport(') !== false
        && strpos($biController, 'Clinica::getReporteBI(') !== false
        && strpos($biController, "Validators::sanitizeDate") !== false,
    'controlador BI valida y genera reportes por centro y periodo'
);
check(
    strpos($advancedClinicDao, 'getReporteBI(') !== false
        && strpos($advancedClinicDao, 'getCitasDetalleReporteBI(')
            !== false
        && strpos($advancedClinicDao, 'getPagosDetalleReporteBI(')
            !== false
        && strpos($advancedClinicDao, 'pf.fecha_pago >= :fecha_desde')
            !== false,
    'DAO BI documenta reportes operativos y financieros filtrados'
);
check(
    strpos($biPrintView, '{{if reporteEjecutivo}}') !== false
        && strpos($biPrintView, '{{if reporteCitas}}') !== false
        && strpos($biPrintView, '{{if reporteFinanciero}}') !== false
        && strpos($biPrintView, 'onclick="window.print()"') !== false
        && strpos($biPrintView, 'Elaborado por') !== false,
    'vista BI ofrece tres documentos imprimibles con trazabilidad'
);
check(
    strpos($biCss, '.bi-report-document') !== false
        && strpos($biCss, '@media print') !== false
        && strpos($biCss, 'size: A4 landscape') !== false,
    'reportes BI tienen formato profesional para pantalla e impresion'
);
$sql = file_get_contents(__DIR__ . '/../docs/smartclinic_1p.sql');
$seedCenterCodes = ['SC-NORTE', 'SC-CENTRAL', 'SC-LITORAL'];
$seedProviders = [
    'MediSupply Honduras',
    'Distribuidora Farmaceutica Central',
    'Insumos Clinicos del Norte',
    'Laboratorios VitalCare',
    'Equipo Medico Hondureno',
];
$seedProducts = [
    'Guantes de nitrilo',
    'Mascarilla quirurgica',
    'Jeringa desechable 5 ml',
    'Alcohol etilico 70% 1 L',
    'Gasa esteril 10 x 10 cm',
    'Venda elastica 10 cm',
    'Solucion salina 500 ml',
    'Termometro digital',
    'Tensiometro aneroide',
    'Oximetro de pulso',
    'Bajalenguas de madera',
    'Algodon absorbente 500 g',
    'Cateter intravenoso 22G',
    'Tubo de muestra tapa roja',
    'Curita adhesiva',
    'Desinfectante de superficies 1 L',
    'Papel para camilla 50 m',
    'Bata desechable',
    'Gorro quirurgico',
    'Gel antibacterial 500 ml',
];
foreach ($seedCenterCodes as $centerCode) {
    check(
        strpos($sql, "'{$centerCode}'") !== false,
        "centro de demostracion {$centerCode} documentado"
    );
}
foreach ($seedProviders as $providerName) {
    check(
        strpos($sql, "'{$providerName}'") !== false,
        "proveedor de demostracion {$providerName} documentado"
    );
}
check(
    count(array_filter(
        $seedProducts,
        static fn ($productName) =>
            strpos($sql, "'{$productName}'") !== false
    )) === 20,
    'veinte productos de demostracion documentados'
);
$purchaseCreateTemplate = file_get_contents(
    __DIR__ . '/../src/Views/templates/compra_create.view.tpl'
);
$purchaseEditTemplate = file_get_contents(
    __DIR__ . '/../src/Views/templates/compra_edit.view.tpl'
);
$mainCss = file_get_contents(__DIR__ . '/../public/css/main.css');
check(
    strpos($purchaseCreateTemplate, 'purchase-lines-editor') !== false
        && strpos($purchaseEditTemplate, 'purchase-lines-editor') !== false,
    'editor visual de productos compartido por alta y edicion de compras'
);
check(
    strpos($mainCss, '.purchase-linea') !== false
        && strpos($mainCss, '.btn-remove-linea') !== false,
    'estilos responsivos del detalle de compra presentes'
);
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
$centerDao = file_get_contents(__DIR__ . '/../src/Dao/CentroSalud.php');
$centerController = file_get_contents(
    __DIR__ . '/../src/Controllers/CentrosSaludController.php'
);
$centerView = file_get_contents(
    __DIR__ . '/../src/Views/templates/centros_salud.view.tpl'
);
check(
    strpos($centerDao, 'getFutureActiveAppointmentSummary') !== false
        && strpos($centerDao, 'fecha_hora >= CURRENT_TIMESTAMP') !== false
        && strpos($centerDao, 'estado_id NOT IN (3, 4, 5)') !== false,
    'DAO protege centros con citas futuras activas'
);
check(
    strpos($centerController, 'citas futuras activas') !== false
        && strpos($centerController, 'DaoCentroSalud::setStatus')
            > strpos(
                $centerController,
                'DaoCentroSalud::getFutureActiveAppointmentSummary'
            ),
    'desactivacion de centro valida citas antes de cambiar estado'
);
check(
    strpos($centerView, '{{if statusError}}') !== false,
    'catalogo de centros muestra bloqueo por citas futuras'
);
check(
    strpos($centerView, 'form="center-status-{{id}}"') !== false
        && strpos($centerView, 'id="center-status-{{id}}"') !== false,
    'acciones de estado usan formularios POST externos a la tabla'
);
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
check(
    strpos($medicoCentroDao, 'getFutureActiveAppointmentSummary') !== false
        && strpos($medicoCentroDao, 'estado_id NOT IN (3, 4, 5)')
            !== false,
    'DAO protege asignaciones medico-centro con citas futuras'
);

$medicosDao = file_get_contents(__DIR__ . '/../src/Dao/Medicos.php');
check(strpos($medicosDao, 'insertMedicoConCentros') !== false, 'alta transaccional de médico y centros presente');
check(strpos($medicosDao, 'updateMedicoConCentros') !== false, 'edición transaccional de médico y centros presente');
check(
    strpos($medicosDao, 'moveFutureActiveAppointmentsToConsultorio')
        !== false
        && strpos($medicosDao, 'getActivosByMedicoForUpdate') !== false,
    'cambio de consultorio mueve citas futuras dentro de la transaccion'
);
$medicosController = file_get_contents(
    __DIR__ . '/../src/Controllers/MedicosController.php'
);
check(
    strpos(
        $medicosController,
        'validateFutureAppointmentAssignmentRemovals'
    ) !== false
        && strpos($medicosController, 'Reasigne o cancele estas citas')
            !== false
        && strpos($medicosController, '$roomChanged') === false
        && strpos($medicosController, 'cambiar el consultorio de') === false,
    'edicion medica bloquea retiros pero permite cambiar consultorio'
);
check(
    strpos($medicosController, 'notifyConsultorioMoves') !== false
        && strpos($medicosController, 'medicos_consultorio_notice') !== false,
    'edicion medica notifica y resume movimientos de consultorio'
);

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
check(
    strpos($sql, 'cita_consultorio_column_exists') !== false
        && strpos($sql, 'MODIFY COLUMN consultorio VARCHAR(30) NOT NULL')
            !== false,
    'migracion preserva consultorio historico de citas'
);
check(
    strpos($citasDao, '(paciente_id, medico_id, centro_salud_id, consultorio,')
        !== false
        && strpos($citasDao, 'c.consultorio') !== false
        && strpos($citasDao, 'mcs.consultorio') === false,
    'DAO de citas persiste y consulta su propio consultorio'
);
check(
    strpos($citasDao, 'moveFutureActiveAppointmentsToConsultorio')
        !== false
        && strpos($citasDao, 'estado_id NOT IN (3, 4, 5)') !== false
        && strpos($citasDao, 'FOR UPDATE') !== false,
    'DAO mueve solo citas futuras activas al nuevo consultorio'
);
check(strpos($citasController, 'availableCenters') !== false, 'citas expone centros activos por médico');
check(strpos($citasController, 'getActivoByMedicoCentro') !== false, 'citas valida la asignación médico-centro');
check(strpos($citaCreate, 'name="centro_salud_id"') !== false, 'alta de cita solicita centro de salud');
check(strpos($citaEdit, 'name="centro_salud_id"') !== false, 'edición de cita solicita centro de salud');
check(strpos($pacientePortal, 'name="centro_salud_id"') !== false, 'portal del paciente solicita centro de salud');
check(strpos($messageNotifier, 'Centro de salud: %s') !== false, 'mensaje de cita incluye centro de salud');
check(
    strpos($messageNotifier, 'sendAppointmentRoomChanged') !== false
        && strpos($messageNotifier, 'Consultorio anterior: %s') !== false
        && strpos($messageNotifier, 'Nuevo consultorio: %s') !== false,
    'notificacion de movimiento incluye consultorio anterior y nuevo'
);
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
$centerInventoryDao = file_get_contents(__DIR__ . '/../src/Dao/InventarioCentro.php');
$movementDao = file_get_contents(__DIR__ . '/../src/Dao/MovimientoInventario.php');
$purchaseDao = file_get_contents(__DIR__ . '/../src/Dao/FacturaCompra.php');
$purchaseController = file_get_contents(__DIR__ . '/../src/Controllers/ComprasController.php');
$adjustmentView = file_get_contents(__DIR__ . '/../src/Views/templates/inventario_ajustar.view.tpl');
$inventoryView = file_get_contents(__DIR__ . '/../src/Views/templates/inventario.view.tpl');
$kardexView = file_get_contents(__DIR__ . '/../src/Views/templates/inventario_kardex.view.tpl');
$purchaseCreateView = file_get_contents(__DIR__ . '/../src/Views/templates/compra_create.view.tpl');
$purchaseEditView = file_get_contents(__DIR__ . '/../src/Views/templates/compra_edit.view.tpl');
check(strpos($inventoryController, 'DaoCentroSalud::getActivos') !== false, 'ajustes cargan centros de salud activos');
check(strpos($inventoryController, 'registerWithStockChange') !== false, 'ajuste y stock se registran atómicamente');
check(strpos($sql, '-- cambios para separar el inventario por centro de salud') !== false, 'migración de inventario por centro documentada');
check(strpos($sql, 'CREATE TABLE IF NOT EXISTS inventario_centro') !== false, 'tabla de saldos por centro documentada');
check(strpos($sql, 'fk_factura_compra_centro_salud') !== false, 'compras exigen centro de salud');
check(strpos($sql, '[MIGRACION_CENTRO] Saldo inicial conciliado') !== false, 'saldo legado queda conciliado en el kárdex');
check(is_file(__DIR__ . '/../src/Dao/InventarioCentro.php'), 'DAO comentado de inventario por centro presente');
check(strpos($centerInventoryDao, 'FOR UPDATE') !== false, 'DAO bloquea el saldo por centro antes de modificarlo');
check(strpos($centerInventoryDao, 'nuevoSaldo < 0') !== false, 'DAO impide stock negativo por centro');
check(strpos($adjustmentDao, 'InventarioCentro::ajustarStock') !== false, 'ajustes modifican el saldo autoritativo del centro');
check(strpos($adjustmentDao, 'centro_salud_id') !== false, 'DAO de ajustes persiste el centro de salud');
check(strpos($movementDao, 'cs.nombre') !== false, 'movimientos devuelven el centro del ajuste');
check(strpos($movementDao, 'fc.centro_salud_id') !== false, 'movimientos de compra conservan su centro');
check(strpos($movementDao, "'Inventario general'") === false, 'ya no existen compras de inventario general');
check(strpos($purchaseDao, 'InventarioCentro::ajustarStock') !== false, 'compras actualizan el saldo por centro');
check(strpos($purchaseController, 'centro_salud_id') !== false, 'controlador de compras valida centro de destino');
check(strpos($adjustmentView, 'name="centro_salud_id"') !== false, 'formulario de ajuste exige centro de salud');
check(strpos($inventoryView, 'id="centro_inventario"') !== false, 'inventario se consulta por centro de salud');
check(strpos($inventoryView, '{{centro_nombre}}') !== false, 'movimientos recientes muestran centro de salud');
check(strpos($kardexView, '{{centro_nombre}}') !== false, 'kárdex muestra centro de salud');
check(strpos($purchaseCreateView, 'name="centro_salud_id"') !== false, 'alta de compra exige centro de destino');
check(strpos($purchaseEditView, 'name="centro_salud_id"') !== false, 'edición de compra exige centro de destino');

$navConfig = file_get_contents(__DIR__ . '/../nav.config.json');
check(
    strpos($navConfig, 'ReportesController') === false
        && strpos($navConfig, 'Menu_Reportes') === false,
    'menu independiente de reportes retirado en favor del BI'
);
check(strpos($navConfig, 'AuditController') !== false, 'menú de bitácora registrado');
check(strpos($navConfig, 'DoctoresController') !== false, 'menú portal doctor registrado');
check(strpos($navConfig, 'PacientePortalController') !== false, 'menú portal paciente registrado');
check(strpos($navConfig, 'PagosController') !== false, 'menú pagos registrado');
check(strpos($navConfig, 'NotificacionesController') !== false, 'menú notificaciones registrado');
check(strpos($navConfig, 'BIController') !== false, 'menú BI registrado');
check(strpos($navConfig, 'Menu_CentrosSalud') !== false, 'menú centros de salud registrado');

check(
    strpos($navConfig, 'Menu_ContactoMensajes') !== false,
    'menu de mensajes de contacto registrado'
);
check(
    strpos(
        $sql,
        '-- cambios para agregar el flujo persistente de contacto'
    ) !== false
        && strpos(
            $sql,
            'CREATE TABLE IF NOT EXISTS contacto_mensaje'
        ) !== false
        && strpos(
            $sql,
            'Controllers\\\\ContactoMensajesController'
        ) !== false
        && strpos($sql, 'Menu_ContactoMensajes') !== false,
    'tabla y permisos del flujo de contacto documentados'
);

$privateLayout = file_get_contents(__DIR__ . '/../src/Views/templates/privatelayout.view.tpl');
$privateNavigation = file_get_contents(__DIR__ . '/../public/js/private-navigation.js');
check(strpos($privateLayout, 'skip-link') !== false, 'mejora de accesibilidad skip-link presente');
check(strpos($privateLayout, 'smartclinic-confirm.js') !== false, 'layout carga confirmaciones globales');
check(
    strpos($privateLayout, 'sidebar-scroll') !== false
        && strpos($privateLayout, 'sidebar-footer') !== false,
    'menu privado mantiene opciones desplazables y cierre de sesion visible'
);
check(
    strpos($privateLayout, 'menu-backdrop') !== false
        && strpos($privateLayout, 'private-navigation.js') !== false,
    'menu privado carga fondo interactivo y comportamiento responsivo'
);
check(
    strpos($privateNavigation, "aria-current") !== false
        && strpos($privateNavigation, "event.key !== 'Escape'") !== false
        && strpos($privateNavigation, 'scrollIntoView') !== false,
    'menu privado identifica la pagina activa y permite cerrar con Escape'
);


echo PHP_EOL . "Validaciones rápidas finalizadas correctamente." . PHP_EOL;
