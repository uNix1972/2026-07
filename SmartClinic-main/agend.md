# SmartClinic Project Agenda

This file is the working knowledge ledger for `SmartClinic_Final/SmartClinic-main`.
It records project conventions, important decisions, and operational details that
must be considered before future changes.

## Project Structure

- Entry point: `index.php`.
- Autoloading: PSR-4 from `src/`, configured by `composer.json` and supported by
  the local `autoload.php`.
- Controllers: `src/Controllers`.
- DAOs: `src/Dao`.
- Templates: `src/Views/templates`.
- Shared utilities: `src/Utilities`.
- Main database script: `docs/smartclinic_1p.sql`.
- Navigation configuration: `nav.config.json`.
- Quick checks: `tests/run_quality_checks.sh`.
- Generated audit records: `data/audit_log.json`.

## Routing

- Routes use `index.php?page=ControllerName&action=ActionName`.
- `Utilities\Site::getPageRequest()` resolves controller names and namespaces.
- A controller named `CentrosSaludController` lives at
  `src/Controllers/CentrosSaludController.php`.
- Private controllers extend `Controllers\PrivateController`.

## Authentication and Authorization

Security follows this relationship:

```text
usuario
  -> roles_usuarios
  -> roles
  -> funciones_roles
  -> funciones
```

Known roles:

- Role 1: Administrador.
- Role 2: Recepción.
- Role 3: Médico.
- Role 4: Paciente.

Important permission rule:

- Menu permissions use names such as `Menu_Inventario` or `Menu_CentrosSalud`.
- `PrivateController` authorizes controllers using the full PHP class name from
  `get_class()`. New private controller permissions must therefore use the exact
  value, for example `Controllers\CentrosSaludController`.
- Administrator user ID 1 and members of role 1 bypass ordinary permission
  checks, but permission rows must still be added for a complete RBAC model.
- `DEVELOPMENT=0` means missing functions are not automatically registered.
- Navigation data is cached in the session when development mode is disabled.
  Log out and log back in after adding a menu permission.

## Database Practices

- The active database is `smartclinic_db`.
- The development container reaches MariaDB at `127.0.0.1`.
- Docker initialization scripts only run when the database volume is created.
  Updating `docs/smartclinic_1p.sql` does not update an existing database.
- Do not re-import the complete SQL file into an existing database. It contains
  regular inserts and non-idempotent `ALTER TABLE` statements.
- Add every new table to `docs/smartclinic_1p.sql` and also apply only the new
  block as an incremental migration to the live database.
- Create a complete database dump before every schema change.
- Foreign-key parent records must exist before child rows are inserted. For
  example, a `funciones` row must exist before its `funciones_roles` row.

## Current Schema Milestones

- Functions 1 through 31 cover the original and enhanced clinical modules.
- Functions 32 through 35 cover Inventory and Purchases.
- Function-role assignments 66 through 69 grant Inventory and Purchases to
  administrator role 1.
- Functions 36 and 37 cover the Centros de Salud controller and menu.
- Function-role assignments 70 and 71 grant Centros de Salud to role 1.
- `medico_centro_salud` models the many-to-many relationship between doctors
  and health centers. Its unique key is `(medico_id, centro_salud_id)`.
- The relationship stores `consultorio` because that value belongs to a
  doctor-center assignment, not to either record independently.
- The seed creates the active `SmartClinic Center` record with code
  `SMARTCLINIC`.
- Doctors present when the relationship block is executed receive
  `SmartClinic Center`, consultorio `01`, through an idempotent `INSERT IGNORE`.
- Inventory tables were added to an existing database as an incremental block:
  `producto`, `proveedor`, `ajuste_inventario`, `factura_compra`, and
  `factura_compra_detalle`.

## DAO Rule

- Every new DAO file must be commented.
- The class comment must explain its responsibility and lifecycle decisions.
- Every public method must explain what it reads or changes.
- Non-obvious SQL, placeholder choices, false/null behavior, and transaction
  boundaries must be documented.
- Controllers must not contain SQL.
- DAOs must use prepared statements through `Dao\Table`.

## Forms and Security

- Every POST form must include `csrf_token`.
- Controllers must call `Utilities\Security::validateCsrfPost()` before a
  mutation.
- IDs must use `Validators::sanitizeId()`.
- Text must be sanitized and constrained to the database column length.
- State-changing operations must use POST, never GET.
- Important actions must be recorded through `AuditLogger`.

## Catalog Conventions

- Catalogs support listing, creation, editing, and status changes.
- Referenced catalog records are deactivated with `ACT`/`INA`; they are not
  physically deleted.
- Database uniqueness constraints are authoritative and controllers should
  provide an understandable duplicate validation message.
- Search is read-only and uses GET parameters.

## Centros de Salud Catalog

- Table: `centro_salud`.
- DAO: `Dao\CentroSalud`.
- Controller: `Controllers\CentrosSaludController`.
- Menu permission: `Menu_CentrosSalud`.
- Initial access: administrator role 1.
- Fields: code, name, type, address, city, phone, email, status, and timestamps.
- Doctors can work at multiple active centers through `medico_centro_salud`.
- Creating or editing a doctor requires at least one active center and a
  consultorio of no more than 30 characters for every selected center.
- Doctor creation/editing and assignment replacement run in one transaction.
- Existing doctor records remain valid while the incremental migration is
  pending; the migration assigns their default center and consultorio.
- Assignment rows are inactivated and reactivated instead of being deleted.
- `Dao\MedicoCentroSalud` owns relationship queries and is fully commented.
- `Dao\Medicos` coordinates transactional doctor writes and is fully commented.
- This doctor integration does not change appointments, scheduling,
  availability, or notifications.
- Appointments should eventually store `centro_salud_id`.
- Multi-center inventory is a separate future phase because the current
  `producto.stock_actual` is global.

## Existing Operational Notes

- Correct shared-Apache URL:
  `http://localhost:8080/SmartClinic_Final/SmartClinic-main/index.php`.
- The project is PHP MVC with vanilla JavaScript; it does not use React.
- Xdebug connection warnings do not prevent PHP execution.
- `parameters.env` contains local environment configuration and must not be
  overwritten casually.
- API tokens and passwords must not be copied into this agenda.

## Rollback Convention

- Backups must live outside the Git repository under
  `D:\SmartClinic_Backups\SmartClinic_Final\<change>_<timestamp>\`.
- Do not create future rollback copies inside the project's
  `rollback_backups` directory because untracked backup files add noise to
  GitHub and local Git status.
- Copy every existing file before editing it.
- Save a complete `smartclinic_db.sql` dump before a schema migration.
- New files are listed in the change handoff so rollback can remove them after
  restoring the copied files and database dump.

## Verification Checklist

```bash
bash tests/run_quality_checks.sh
php -l src/Dao/NEW_DAO.php
php -l src/Controllers/NEW_CONTROLLER.php
```

For schema work:

```bash
mariadb -h 127.0.0.1 -u root smartclinic_db
```

Verify tables, foreign keys, function rows, role assignments, DAO queries, and
the rendered page before considering the change complete.
