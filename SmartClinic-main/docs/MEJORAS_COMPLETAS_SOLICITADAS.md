# SmartClinic - Mejoras completas solicitadas

Este paquete integra las mejoras priorizadas en la revisión de pruebas y agrega módulos académicos funcionales para cubrir la evolución propuesta hacia V2.0 y V3.0.

## Mejoras prioritarias de la revisión

- Enrutamiento compatible con Docker/Linux sin romper la capitalización de controladores.
- Disponibilidad de citas corregida: las citas confirmadas bloquean horario y solo se excluyen estados no activos.
- Protección CSRF integrada en formularios POST críticos.
- Modo producción configurado para no mostrar stack trace al usuario final.
- Documentación de instalación, Docker, Dev Container, SQL y permisos de `data`.

## Mejoras de calidad y usabilidad

- Bitácora básica de auditoría en `data/audit_log.json`.
- Reportes operativos con exportación CSV.
- Datos semilla controlados para pacientes, médicos, citas y estados.
- Confirmaciones antes de cancelar/eliminar acciones críticas.
- Contacto con procesamiento real en backend.
- Accesibilidad básica: skip-link, foco visible, labels y textos alternativos.
- Scripts de respaldo y restauración de base de datos.
- Pruebas rápidas en `tests/run_quality_checks.sh`.

## Elementos no considerados que ahora quedan cubiertos

- Bitácora de auditoría.
- Recuperación de contraseña por token temporal.
- Reportes administrativos exportables.
- Accesibilidad básica documentada y aplicada.
- Respaldo y recuperación de base de datos.
- Notificaciones internas tipo push en pantalla.

## Módulos V2.0 agregados como implementación académica

- Portal de doctores.
- Agenda diaria del doctor.
- Sala de espera.
- Cambio de estados: Confirmada, En Espera, En Atención y Completada.
- Historial clínico básico.
- Recetas u órdenes médicas.
- Consulta de historial por paciente.

## Módulos V3.0 agregados como implementación académica

- Portal de paciente.
- Autoservicio para agendar cita.
- Pago simulado con recibo digital.
- Registro de pagos y detalle de factura.
- Notificaciones automáticas internas al confirmar pagos o crear citas.
- BI básico con citas por estado, citas por mes, carga por médico e ingresos.

## Rutas principales

- Dashboard: `http://localhost:8080/index.php?page=HomeController`
- Reportes: `http://localhost:8080/index.php?page=ReportesController`
- Bitácora: `http://localhost:8080/index.php?page=AuditController`
- Portal Doctor: `http://localhost:8080/index.php?page=DoctoresController`
- Portal Paciente: `http://localhost:8080/index.php?page=PacientePortalController`
- Pagos: `http://localhost:8080/index.php?page=PagosController`
- Notificaciones: `http://localhost:8080/index.php?page=NotificacionesController`
- BI: `http://localhost:8080/index.php?page=BIController`
- Recuperación de contraseña: `http://localhost:8080/index.php?page=PasswordRecoveryController`

## Credenciales de prueba

- Administrador: `admin@smartclinic.com` / `SmartClinic#2026`
- Doctor demo: `doctor@smartclinic.com` / `Doctor#2026`
- Paciente demo: `paciente@smartclinic.com` / `Paciente#2026`

## Verificación

Ejecutar:

```bash
bash tests/run_quality_checks.sh
```

Si todo está correcto, el script muestra validaciones `OK` para rutas, CSRF, Docker, reportes, bitácora, doctor, paciente, pagos, notificaciones, recuperación de contraseña y BI.
