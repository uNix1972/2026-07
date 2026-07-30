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
- The PHP development container reaches MariaDB through the Docker service
  hostname `db`. Host-side database tools reach the published port at
  `127.0.0.1`.
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
  `SmartClinic Center` and a deterministic consultorio derived from their ID
  through an idempotent `INSERT IGNORE`.
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
- Listing, search, status filtering, creation, and editing share the
  `centros_salud` workspace. The form stays on the left and the directory stays
  on the right; legacy `create` and `edit` GET routes redirect into this page.
- Existing center types outside the current creation catalog remain selectable
  during editing so saving another field never overwrites historical type data.
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
- A health center cannot be deactivated while it has future appointments in a
  non-terminal state. The blocked action is shown to the administrator and
  recorded in the audit log with the count and next appointment.
- Center status buttons reference POST forms rendered outside the catalog
  table. This avoids invalid table/form parsing and keeps CSRF plus confirmation
  behavior attached to the intended center.
- A doctor's center assignment cannot be removed while that doctor has future
  active appointments at the center. Reassign or cancel those appointments
  before removing the assignment.
- Changing only the consultorio of an existing doctor-center assignment is
  allowed even when the doctor has future active appointments at that center.
- Every appointment stores its own `cita.consultorio` snapshot. Completed,
  cancelled, no-show, and past appointments therefore retain the room recorded
  for their original attention even after the doctor moves.
- A consultorio change updates that snapshot only for future appointments in
  non-terminal states at the same doctor-center assignment. The assignment and
  affected appointments are committed in one transaction.
- After commit, every affected patient is notified with the previous and new
  consultorio. Delivery failures do not roll back the room change; each result
  is audited and the administrator receives an on-screen summary.
- Appointment states 3, 4, and 5 are terminal for these protections:
  completed, cancelled, and no-show. They preserve history but do not block
  operational center or assignment changes.
- The first doctor-center phase intentionally left appointments unchanged.
  The later appointment-center phase below is now the active behavior.
- `cita.centro_salud_id` is mandatory after the appointment-center migration.
- `fk_cita_medico_centro` references the unique pair
  `(medico_id, centro_salud_id)` in `medico_centro_salud`. Raw SQL therefore
  cannot assign an appointment to a center where the doctor has never been
  assigned.
- Controllers additionally require the doctor-center relation and center to
  be active when creating or editing an appointment.
- Existing appointments are backfilled to the active `SmartClinic Center`
  assignment and its consultorio during migration.
- Doctor availability remains global across centers: the same doctor cannot
  attend overlapping appointments in two locations. Different doctors may
  attend at the same time in the same center only when they have different
  consultorios.
- Every active consultorio is unique inside its health center. The database
  enforces this through generated column `consultorio_activo` and unique index
  `uq_centro_consultorio_activo`; inactive assignments expose `NULL` and do not
  reserve a room. Doctor creation and editing validate the same rule before
  writing.
- Existing SmartClinic Center assignments were migrated deterministically:
  doctors 1 through 5 use consultorios `01` through `05`.
- Patient conflicts remain global across doctors and centers.
- Appointment overlap uses strict 30-minute boundaries. A slot at `08:00`
  blocks an overlapping start but permits the consecutive `08:30` slot.
- Staff create/edit forms and the patient portal load occupied times using
  both doctor and patient. Final validation returns a specific doctor,
  patient, or combined conflict message instead of the old generic warning.
- Staff scheduling and patient self-service both require a center.
- Appointment lists, doctor portal, patient portal, calendar day view, reports,
  CSV export, audit metadata, and WhatsApp confirmations expose the selected
  center and consultorio.
- `MessageNotifier` receives the appointment location explicitly. It no longer
  uses a hardcoded default consultorio for new appointment messages.
- The appointment creation form displays the selected patient's phone directly
  below the patient selector.
- The staff appointment list is ordered by `fecha_hora` descending, with the
  newest or furthest-future appointment first.
- The appointment is submitted through a final confirmation dialog asking
  `¿Está seguro que los datos son correctos?`. Immediate WhatsApp notification
  is opt-in inside that dialog; its checkbox is no longer displayed directly
  on the appointment form. Saving without selecting it does not send a message.
- Manual appointment notifications use
  `CitasController&action=notify`, require POST, CSRF, and appointment-management
  authorization, and are recorded in the audit log.
- Manual notification is available only for future, non-final appointments
  whose patient has a phone number. A malformed number or disabled WhatsApp
  integration returns a visible failure result without changing the appointment.
- Both immediate and manual sends rebuild the message from the saved
  appointment, including its doctor, center, consultorio, date, and time.
- The BI dashboard always operates with one selected health center. There is
  no combined "all centers" option because every appointment, payment,
  doctor-load series, and summary must share the same location context.
- `Dao\ClinicaAvanzada::getMetricasBI()` and `getResumenBI()` require a
  `centro_salud_id`. Revenue is filtered by joining each payment to its
  appointment, while doctor load starts from the doctor-center assignment.
- BI defaults to the active `SMARTCLINIC` center when no valid selector value
  is provided. Inactive centers remain selectable for historical analysis.
- The BI summary includes appointments scheduled in the current calendar
  month and revenue received in that month. Both indicators use date ranges
  and the mandatory selected health center.
- The appointment-status and doctor-workload panels combine proportional
  SVG pie charts with exact-value bars. Pie metadata is calculated server-side
  by `BIController`, so the dashboard does not require a chart dependency.
- Public contact submissions are stored in `contacto_mensaje`, never in the
  legacy `data/contact_messages.json` file. New rows start as `PEN`; the
  administrative inbox can move them through `LEI` and `RES`.
- `Dao\ContactoMensaje` owns contact insertion, filtered inbox queries,
  summary counts, and status transitions. Status changes record the managing
  user and are also written to the audit log.
- `Controllers\ContactoMensajesController` and `Menu_ContactoMensajes` are
  assigned to the administrator role. The public `Contacto` controller does
  not require a private permission but must validate CSRF and confirm the
  database insert before returning success.
- `inventario_centro` stores the authoritative stock for each
  `(producto_id, centro_salud_id)` pair. A missing pair represents zero stock
  until the first movement creates it.
- `producto.stock_actual` remains as a compatibility total. Every purchase or
  manual adjustment updates the center balance and this aggregate in the same
  transaction; operational availability must never be validated against the
  aggregate.
- Manual inventory adjustments require an active `centro_salud_id`.
  `Dao\AjusteInventario::registerWithStockChange()` delegates the locked center
  balance change to `Dao\InventarioCentro` and rolls back both balances plus
  the adjustment record on failure.
- `factura_compra.centro_salud_id` is mandatory. Purchase creation and editing
  select a destination center, and editing first reverses the old detail from
  its original center before applying the replacement detail.
- During migration, any legacy difference between the movement ledger and
  `producto.stock_actual` is recorded once as
  `[MIGRACION_CENTRO] Saldo inicial conciliado` in `ajuste_inventario`.
  This preserves the total while giving the Kárdex an auditable opening entry.
- A purchase cannot be edited when reversing its previous quantities would
  make the original center negative. This prevents moving stock that has
  already been consumed.
- `Dao\MovimientoInventario` combines center-tagged adjustments and purchases.
  Kárdex running balances and historical inventory are therefore calculated
  independently for the selected health center.
- The inventory page always operates in one active health center at a time.
  Product minimum stock remains a catalog-level threshold shared by centers.
- The canonical SQL includes repeatable demonstration data for three additional
  health centers, five providers, and twenty products. New products start with
  zero stock, and `inventario_centro` receives a zero-balance row for every
  product and health-center combination.
- Purchase create and edit use the same responsive product-line editor. Its
  price label changes between unit and box purchases, and the last remaining
  line cannot be removed.
- The original `ReportesController` remains a lightweight operational summary
  with CSV export. Printable management documents belong to `BIController`
  because BI already requires the health-center context used by those reports.
- `ReportesController` remains reachable by direct URL for compatibility, but
  it is not listed in `nav.config.json`; users access current printable reports
  from the BI menu instead.
- The BI dashboard must retain its current cards, charts, and center selector.
  Its report generator is an additive section with three outputs: executive
  summary, appointment detail, and financial collections.
- Every printable BI report requires one valid `centro_salud_id` and a date
  range. Appointment metrics use `cita.fecha_hora`; collection metrics use
  `pago_factura.fecha_pago` joined through the appointment to its center.
- `Dao\ClinicaAvanzada::getReporteBI()` owns printable-report data. The report
  controller validates type and dates, prepares display values, and escapes
  variable text before rendering `bi_report_print.view.tpl`.
- Printable BI documents include report identity, center, period, generation
  time and user, KPI totals, report-specific tables, and signature lines.
  Print CSS uses A4 landscape, repeats table headers, and hides application
  navigation and report controls.

## Existing Operational Notes

- Correct shared-Apache URL:
  `http://localhost:8080/SmartClinic_Final/SmartClinic-main/index.php`.
- When `BASE_DIR` is empty, `Utilities\Site::configure()` derives the web path
  from `SCRIPT_NAME`. Assets therefore work both under the shared-Apache
  subfolder above and at `/` in the project's standalone Docker setup.
- The project is PHP MVC with vanilla JavaScript; it does not use React.
- The authenticated navigation uses a fixed header and a slide-out drawer. Its
  menu body scrolls independently so every authorized option remains reachable
  on short screens, while the logout action stays fixed at the bottom.
- `public/js/private-navigation.js` marks the current controller link with
  `aria-current`, synchronizes the drawer's expanded state, and supports closing
  the menu with Escape. Menu permissions and ordering still come exclusively
  from `nav.config.json` and `Utilities\Nav`.
- Xdebug connection warnings do not prevent PHP execution.
- `parameters.env` contains deployment defaults. Actual hosting-environment
  variables take precedence over those committed values.
- `parameters.local.env` is an ignored, machine-specific override. The shared
  development container uses it to select `DB_SERVER=db` and the local
  database credentials, so pulling deployment configuration does not break
  local pages.
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
