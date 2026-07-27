# SmartClinic v1.3 - Entrega lista para Docker

Sistema web MVC para gestión de citas médicas, pacientes, médicos, usuarios, roles y panel operativo.

## Integrantes originales del proyecto

- Anyelo Favian Rivera Galindo - 1501200402099
- Carlos Gustavo Luna Acosta - 0301200400911
- Jose Ramon Hernandez Espinal - 0801200306613 - Coordinador
- Maria del Carmen Aguilar Martel - 0801200707818
- Leibo Moisés Raibstein Aguiluz - 0801200104787

## Tecnologías

- PHP 8.2
- MySQL 8.0
- HTML5, CSS3 y JavaScript
- Arquitectura MVC propia
- Docker y Dev Container para Visual Studio Code

## Mejoras incorporadas en esta versión

| Área | Mejora aplicada |
|---|---|
| Docker | Se agregó `Dockerfile`, `docker-compose.yml` y configuración `.devcontainer` lista para VS Code. |
| Autoload | Se agregó `autoload.php` local para que el proyecto funcione aunque no exista la carpeta `vendor`. |
| Enrutamiento | Se corrigió el problema de mayúsculas y minúsculas en controladores como `CitasController`, `PacientesController`, `MedicosController` y `HomeController`. |
| Citas | Se corrigió la disponibilidad de agenda para que las citas confirmadas bloqueen horario. |
| Seguridad | Se integró protección CSRF en formularios POST de login, perfil, pacientes, médicos, citas, usuarios, roles y funciones. |
| Eliminaciones | Las acciones de eliminar/cancelar pasan de enlaces GET a formularios POST con token CSRF. |
| Contacto | El formulario de contacto ahora registra mensajes en backend en `data/contact_messages.json`. |
| Producción | `DEVELOPMENT=0` evita mostrar trazas técnicas al usuario final. |
| Calidad | Se agregaron pruebas rápidas de validadores y enrutamiento en la carpeta `tests`. |
| Bitácora | Se agregó registro básico de acciones en `data/audit_log.json` y módulo `AuditController`. |
| Reportes | Se agregó módulo `ReportesController` con resumen de pacientes, médicos, citas, estados y filtro por fechas. |
| Confirmaciones | Se agregó `public/js/smartclinic-confirm.js` para confirmar acciones críticas antes de ejecutar cambios. |
| Accesibilidad | Se añadieron mejoras básicas de foco, salto al contenido principal, texto alternativo y etiquetas asociadas. |
| Respaldo | Se agregaron guías y scripts para respaldar/restaurar la base de datos en Docker. |

## Ejecutar con Docker

Desde la carpeta raíz del proyecto:

```bash
docker compose up -d --build
```

Abrir la aplicación:

```text
http://localhost:8080
```

Abrir directamente el login:

```text
http://localhost:8080/index.php?page=Sec_Login
```

Credenciales de prueba incluidas en la base de datos:

```text
Correo: admin@smartclinic.com
Contraseña: SmartClinic#2026
```

La base de datos se importa automáticamente desde:

```text
docs/smartclinic_1p.sql
```

Si se necesita reiniciar la base de datos desde cero:

```bash
docker compose down -v
docker compose up -d --build
```

## Ejecutar con Dev Container en VS Code

1. Abrir la carpeta del proyecto en Visual Studio Code.
2. Seleccionar `Reopen in Container`.
3. Esperar a que se construyan los servicios `app` y `db`.
4. Abrir `http://localhost:8080`.


## Módulos nuevos de control

Después de iniciar sesión como administrador:

```text
http://localhost:8080/index.php?page=ReportesController
http://localhost:8080/index.php?page=AuditController
```

El módulo de Reportes muestra totales operativos y citas por estado. El módulo de Bitácora muestra las acciones registradas en `data/audit_log.json`.

## Respaldo y restauración de base de datos

Crear respaldo:

```bash
bash scripts/backup_database.sh
```

Restaurar respaldo:

```bash
bash scripts/restore_database.sh backups/NOMBRE_DEL_RESPALDO.sql
```

## Ejecutar validaciones rápidas

Dentro del contenedor o con PHP instalado localmente:

```bash
bash tests/run_quality_checks.sh
```

La revisión ejecuta:

- Validación de sintaxis en archivos PHP.
- Pruebas básicas de `Validators`.
- Pruebas de normalización de rutas del router.
- Verificación de consultas de disponibilidad de citas.

## Estructura principal

```text
SmartClinic/
├── .devcontainer/          # Configuración para VS Code + Docker
├── data/                   # Archivos generados por la app
├── docs/                   # Script SQL de base de datos
├── public/                 # CSS, JS, imágenes y capturas
├── src/
│   ├── Controllers/        # Controladores MVC
│   ├── Dao/                # Acceso a datos
│   ├── Utilities/          # Seguridad, validadores, contexto y router
│   └── Views/              # Plantillas
├── scripts/                # Scripts de respaldo y restauración
├── tests/                  # Validaciones rápidas
├── autoload.php            # Autoload local de respaldo
├── Dockerfile
├── docker-compose.yml
├── index.php
└── parameters.env
```

## Notas importantes

- El archivo `parameters.env` está configurado para Docker con `DB_SERVER=db`.
- Para ejecución local sin Docker, copiar `parameters.local.env.example` sobre `parameters.env` y ajustar usuario/contraseña de MySQL.
- La carpeta `data` debe tener permisos de escritura para guardar intentos de login y mensajes de contacto.

## Mejoras completas agregadas

Este paquete incluye también las mejoras solicitadas en la revisión: portal de doctores, portal de paciente, historial clínico, recetas, pago simulado, notificaciones internas, recuperación de contraseña, reportes exportables, BI básico, bitácora, respaldo/restauración y pruebas rápidas.

## Expediente clínico por cita

El portal médico permite registrar signos vitales, documentar la consulta,
consultar los pacientes atendidos y descargar el expediente de cada cita en
PDF. El portal del paciente muestra sus consultas finalizadas y permite
descargar el mismo documento, siempre validando que la cita pertenezca al
usuario autenticado.

Las instalaciones nuevas crean la tabla `signos_vitales` desde
`docs/smartclinic_1p.sql`. Para una base de datos existente ejecute una vez:

```bash
docker compose exec -T db mysql -uroot smartclinic_db < scripts/migrate_expediente_clinico.sql
```

Credenciales adicionales:

```text
doctor@smartclinic.com / Doctor#2026
paciente@smartclinic.com / Paciente#2026
```

Documento de detalle:

```text
docs/MEJORAS_COMPLETAS_SOLICITADAS.md
```
