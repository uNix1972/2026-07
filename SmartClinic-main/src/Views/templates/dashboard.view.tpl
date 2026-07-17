<style>
  .dashboard-shell {
    width: 100%;
    max-width: 1000px;
    margin: 0 auto;
    padding: 1.5rem 1.5rem;
    display: grid;
    gap: 1.25rem;
    min-width: 0;
    box-sizing: border-box;
  }
  .hero-card {
    background: linear-gradient(135deg, #0b4bb8 0%, #0f6fe2 100%);
    border-radius: 16px;
    color: #fff;
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1.5rem;
    align-items: center;
    overflow: hidden;
    min-width: 0;
  }
  @media (max-width: 980px) {
    .hero-card {
      grid-template-columns: 1fr;
      text-align: center;
    }
    .hero-card > div:last-child {
      order: -1;
      margin-bottom: 1rem;
    }
    .hero-actions {
      justify-content: center;
    }
  }
  @media (max-width: 640px) {
    .hero-card {
      padding: 1.25rem;
    }
  }
  .hero-card h1 {
    font-size: 2.4rem;
    margin-bottom: 0.75rem;
    letter-spacing: -0.03em;
  }
  .hero-card p {
    font-size: 1rem;
    line-height: 1.75;
    color: rgba(255,255,255,0.92);
    max-width: 620px;
  }
  .hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.85rem;
    margin-top: 1.35rem;
  }
  .hero-actions a {
    background: rgba(255,255,255,0.17);
    color: #fff;
    padding: 0.85rem 1.2rem;
    border-radius: 999px;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 700;
    transition: transform 0.2s ease, background 0.2s ease;
  }
  .hero-actions a:hover {
    background: rgba(255,255,255,0.26);
    transform: translateY(-2px);
  }
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.875rem;
  }
  .stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.25rem;
    box-shadow: 0 8px 24px rgba(3,59,159,0.08);
    min-width: 0;
  }
  .stat-card .label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 0.9rem;
  }
  .label-icon {
    font-size: 1rem;
    line-height: 1;
    flex-shrink: 0;
  }
  .stat-card .value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #0b4bb8;
    line-height: 1;
  }
  .stat-card .hint {
    margin-top: 0.75rem;
    color: #64748b;
    font-size: 0.95rem;
    line-height: 1.6;
  }
  .summary-card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }
  .summary-card .value {
    margin-bottom: 0.75rem;
  }
  .summary-link {
    font-size: 0.8rem;
    font-weight: 600;
    color: #0b4bb8;
    text-decoration: none;
    padding: 0.5rem 0.75rem;
    border-radius: var(--sc-radius-sm);
    background: var(--sc-blue-50);
    border: 1px solid var(--sc-blue-200);
    transition: all var(--sc-transition);
    width: 100%;
    text-align: center;
  }
  .summary-link:hover {
    background: var(--sc-blue-100);
    border-color: var(--sc-blue-700);
    color: var(--sc-blue-900);
  }
  .section-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 1rem;
    margin-bottom: 1rem;
  }
  .section-head h2 {
    font-size: 1.45rem;
    color: #0f172a;
  }
  .section-head a {
    text-decoration: none;
    color: #0b4bb8;
    font-weight: 700;
  }
  .card-grid {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 1rem;
  }
  .card-panel {
    background: #fff;
    border-radius: 16px;
    padding: 1.25rem;
    box-shadow: 0 8px 24px rgba(3,59,159,0.06);
    min-width: 0;
  }
  @media (max-width: 980px) {
    .card-grid {
      grid-template-columns: 1fr;
    }
  }
  .card-panel h3 {
    font-size: 1rem;
    margin-bottom: 0.9rem;
    color: #0b4bb8;
  }
  .card-panel p {
    color: #475569;
    line-height: 1.75;
    margin-bottom: 1.2rem;
  }
  .action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.85rem;
  }
  .btn-action {
    border-radius: 999px;
    padding: 0.95rem 1.25rem;
    border: none;
    text-decoration: none;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease, opacity 0.2s ease;
  }
  .btn-action.primary {
    background: #0b4bb8;
    color: #fff;
  }
  .btn-action.secondary {
    background: #f8fafc;
    color: #0f172a;
    border: 1px solid #e2e8f0;
  }
  .btn-action:hover {
    transform: translateY(-1px);
  }
  .table-simple {
    width: 100%;
    border-collapse: collapse;
    min-width: 0;
    table-layout: fixed;
  }
  .table-simple th,
  .table-simple td {
    padding: 0.75rem 0.5rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
  }
  .table-simple th {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.05em;
  }
  .table-simple td {
    color: #334155;
    font-size: 0.875rem;
  }
  .badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.8rem;
    border-radius: 999px;
    background: #eff6ff;
    color: #0b4bb8;
    font-size: 0.8rem;
    font-weight: 700;
  }

  /* ===== CALENDARIO ===== */
  .calendar-card {
    background: #fff;
    border-radius: var(--sc-radius-lg);
    padding: 1rem;
    box-shadow: var(--sc-shadow-sm);
    border: 1px solid var(--sc-blue-200);
  }
  .calendar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    gap: 0.5rem;
  }
  .calendar-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--sc-blue-900);
  }
  .calendar-nav {
    display: flex;
    gap: 0.35rem;
  }
  .calendar-nav-btn {
    width: 30px;
    height: 30px;
    border-radius: var(--sc-radius-sm);
    border: 1px solid var(--sc-blue-200);
    background: #fff;
    color: var(--sc-blue-700);
    font-size: 0.95rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all var(--sc-transition);
  }
  .calendar-nav-btn:hover {
    background: var(--sc-blue-50);
    border-color: var(--sc-blue-700);
    color: var(--sc-blue-900);
  }
  .calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    min-width: 0;
  }
  .calendar-day-name {
    text-align: center;
    font-size: 0.55rem;
    font-weight: 700;
    color: var(--sc-gray-500);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 0.25rem 0;
    white-space: nowrap;
  }
  .calendar-day {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding: 0.2rem 0.1rem;
    border-radius: var(--sc-radius-sm);
    font-size: 0.6rem;
    color: var(--sc-gray-900);
    background: transparent;
    cursor: pointer;
    transition: all var(--sc-transition);
    position: relative;
    min-height: 48px;
    text-decoration: none;
    min-width: 0;
    overflow: hidden;
  }
  .calendar-day:hover {
    background: var(--sc-blue-50);
    text-decoration: none;
  }
  .calendar-day:focus {
    outline: 2px solid var(--sc-blue-500);
    outline-offset: 2px;
  }
  .calendar-day.other-month {
    color: var(--sc-gray-300);
    background: transparent;
  }
  .calendar-day.other-month:hover {
    background: var(--sc-gray-100);
  }
  .calendar-day.today {
    background: var(--sc-blue-900);
    color: #fff;
    font-weight: 700;
  }
  .calendar-day.today:hover {
    background: var(--sc-blue-700);
  }
  .calendar-day.has-events {
    font-weight: 600;
  }
  .calendar-day.other-month {
    color: var(--sc-gray-300);
    background: transparent;
  }
  .calendar-day.other-month:hover {
    background: var(--sc-gray-100);
  }
  .calendar-day.today {
    background: var(--sc-blue-900);
    color: #fff;
    font-weight: 700;
  }
  .calendar-day.has-events {
    font-weight: 600;
  }
  .calendar-day-number {
    font-weight: 600;
    line-height: 1;
  }
  .calendar-events {
    display: flex;
    flex-direction: column;
    gap: 1px;
    margin-top: 2px;
    width: 100%;
  }
  .calendar-event {
    font-size: 0.55rem;
    padding: 1px 4px;
    border-radius: 3px;
    background: var(--sc-blue-50);
    color: var(--sc-blue-900);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 500;
    border: 1px solid var(--sc-blue-200);
  }
  .calendar-event.estado-2 { background: #f0fdf4; color: var(--sc-success); border-color: #bbf7d0; } /* Confirmada */
  .calendar-event.estado-3 { background: #f0fdf4; color: var(--sc-success); border-color: #bbf7d0; } /* Completada */
  .calendar-event.estado-4 { background: #fff0f0; color: var(--sc-danger); border-color: #fecaca; } /* Cancelada */
  .calendar-event.estado-5 { background: #fff0f0; color: var(--sc-danger); border-color: #fecaca; } /* No Asistió */
  .calendar-more {
    font-size: 0.5rem;
    color: var(--sc-blue-700);
    text-align: center;
    margin-top: 1px;
  }
  .calendar-legend {
    display: flex;
    gap: 0.75rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--sc-gray-100);
    flex-wrap: wrap;
  }
  .legend-item {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.65rem;
    color: var(--sc-gray-700);
  }
  .legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }
  .legend-dot.pending { background: var(--sc-blue-200); }
  .legend-dot.confirmed { background: var(--sc-success); }
  .legend-dot.completed { background: var(--sc-success); }
  .legend-dot.cancelled { background: var(--sc-danger); }
  .legend-dot.no-show { background: var(--sc-danger); }

  @media (max-width: 980px) {
    .calendar-day {
      min-height: 52px;
      font-size: 0.55rem;
      padding: 0.15rem 0.05rem;
    }
    .calendar-event {
      font-size: 0.45rem;
      padding: 1px 2px;
    }
    .calendar-day-name {
      font-size: 0.5rem;
      padding: 0.2rem 0;
    }
  }
  @media (max-width: 640px) {
    .calendar-day {
      min-height: 44px;
      font-size: 0.5rem;
      padding: 0.1rem 0.05rem;
    }
    .calendar-event {
      font-size: 0.4rem;
      padding: 0.5px 2px;
    }
    .calendar-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.4rem;
    }
    .calendar-nav {
      align-self: flex-end;
    }
    .calendar-legend {
      justify-content: center;
    }
    .calendar-nav-btn {
      width: 28px;
      height: 28px;
      font-size: 0.8rem;
    }
  }

  @media (max-width: 980px) {
    .hero-card {
      grid-template-columns: 1fr;
      text-align: left;
    }
    .hero-actions {
      justify-content: flex-start;
    }
    .stats-grid {
      grid-template-columns: repeat(1, minmax(0, 1fr));
    }
    .card-grid {
      grid-template-columns: 1fr;
    }
  }
  @media (max-width: 640px) {
    .dashboard-shell { padding: 1rem 0.75rem; gap: 1rem; }
    .hero-card { padding: 1.25rem; border-radius: 16px; grid-template-columns: 1fr; }
    .hero-card h1 { font-size: 1.75rem; }
    .hero-card p { font-size: 0.875rem; }
    .hero-actions { gap: 0.5rem; }
    .hero-actions a { padding: 0.7rem 1rem; font-size: 0.875rem; }
    .stats-grid { grid-template-columns: 1fr; gap: 0.75rem; }
    .stat-card { padding: 1.25rem; border-radius: 16px; }
    .stat-card .value { font-size: 1.75rem; }
    .section-head { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
    .card-grid { grid-template-columns: 1fr; }
    .stats-grid { grid-template-columns: 1fr; }
    .action-buttons { flex-direction: column; align-items: stretch; }
    .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%; max-width: 100%; }
    .table-simple { min-width: auto; width: 100%; table-layout: fixed; }
    .table-simple th, .table-simple td { padding: 0.5rem 0.375rem; font-size: 0.75rem; }
    .card-panel { padding: 1rem; border-radius: 16px; }
    .card-panel h2 { font-size: 1rem; }
    .calendar-day-name { font-size: 0.5rem; padding: 0.2rem 0; }
    .calendar-day { min-height: 44px; font-size: 0.5rem; padding: 0.1rem 0.05rem; }
    .calendar-event { font-size: 0.4rem; padding: 0.5px 2px; }
    .calendar-nav-btn { width: 28px; height: 28px; font-size: 0.75rem; }
    .calendar-title { font-size: 0.8rem; }
    .legend-item { font-size: 0.5rem; }
    .legend-dot { width: 6px; height: 6px; }
    .hero-card { padding: 1.25rem; border-radius: 16px; }
    .hero-card h1 { font-size: 1.5rem; }
    .hero-card p { font-size: 0.8rem; }
    .hero-actions a { padding: 0.6rem 0.875rem; font-size: 0.8rem; }
  @media (max-width: 480px) {
    .dashboard-shell {
      padding: 1rem 0.75rem;
      gap: 1rem;
    }
    .hero-card {
      padding: 1.25rem;
      border-radius: 16px;
    }
    .hero-card h1 {
      font-size: 1.75rem;
    }
    .hero-card p {
      font-size: 0.875rem;
    }
    .hero-actions {
      gap: 0.5rem;
    }
    .hero-actions a {
      padding: 0.7rem 1rem;
      font-size: 0.875rem;
    }
    .stats-grid {
      gap: 0.75rem;
    }
    .stat-card {
      padding: 1.25rem;
      border-radius: 16px;
    }
    .stat-card .value {
      font-size: 1.75rem;
    }
    .stat-card .label {
      font-size: 0.7rem;
    }
    .summary-link {
      font-size: 0.75rem;
      padding: 0.5rem 0.5rem;
    }
    .section-head h2 {
      font-size: 1.25rem;
    }
    .card-panel {
      padding: 1rem;
      border-radius: 16px;
    }
    .card-panel h2 {
      font-size: 1.1rem;
    }
    .table-simple th,
    .table-simple td {
      padding: 0.65rem 0.5rem;
      font-size: 0.85rem;
    }
    .calendar-day-name {
      font-size: 0.55rem;
      padding: 0.25rem 0;
    }
    .calendar-day {
      min-height: 48px;
      font-size: 0.55rem;
      padding: 0.15rem 0.1rem;
    }
    .calendar-event {
      font-size: 0.5rem;
      padding: 1px 3px;
    }
    .calendar-nav-btn {
      width: 28px;
      height: 28px;
      font-size: 0.85rem;
    }
    .calendar-title {
      font-size: 0.85rem;
    }
    .legend-item {
      font-size: 0.55rem;
    }
    .legend-dot {
      width: 6px;
      height: 6px;
    }
    .hero-card {
      padding: 1.25rem;
    }
    .hero-card h1 {
      font-size: 1.75rem;
    }
    .hero-card p {
      font-size: 0.875rem;
    }
    .hero-actions a {
      padding: 0.7rem 1rem;
      font-size: 0.875rem;
    }
    .hero-card > div:last-child {
      max-width: 280px;
    }
  }
  @media (max-width: 480px) {
    .hero-card > div:last-child {
      display: none;
    }
  }
</style>

<div class="dashboard-shell">
  <section class="hero-card">
    <div>
      <span class="badge-pill">Bienvenido</span>
      <h1>Tu espacio de citas médicas</h1>
      <p>Hola, {{userName}}. Aquí puedes ver los médicos disponibles, organizar tu próxima cita y acceder a tu información de paciente de forma rápida y ordenada.</p>
      <div class="hero-actions">
        <a href="index.php?page=MedicosController&action=index">Ver médicos disponibles</a>
        <a href="index.php?page=CitasController&action=agendar">Agendar cita</a>
        {{if canManagePacientes}}
        <a href="index.php?page=PacientesController&action=index">Mis pacientes</a>
        {{endif canManagePacientes}}
        <a href="index.php?page=Security_Perfil">Mi perfil</a>
      </div>
    </div>
    <div style="display:grid;place-items:center;">
      <div style="width:100%;max-width:340px;background:rgba(255,255,255,0.12);border-radius:20px;padding:1.6rem;text-align:center;">
        <div style="font-size:4rem;line-height:1;">👩‍⚕️</div>
        <p style="margin-top:1rem;color:rgba(255,255,255,0.92);font-size:1rem;line-height:1.6;">Explora médicos disponibles o comienza a registrar tú próxima consulta.</p>
      </div>
    </div>
  </section>

  <div class="stats-grid">
    <div class="stat-card summary-card">
      <span class="label"><span class="label-icon">👥</span>Pacientes</span>
      <div class="value">{{total_pacientes}}</div>
      <a href="index.php?page=PacientesController&action=index" class="summary-link">Ver todos los pacientes</a>
    </div>
    <div class="stat-card summary-card">
      <span class="label"><span class="label-icon">📅</span>Citas hoy</span>
      <div class="value">{{citas_hoy}}</div>
      <a href="index.php?page=CitasController&action=index" class="summary-link">Ver agenda</a>
    </div>
    <div class="stat-card summary-card">
      <span class="label"><span class="label-icon">⏳</span>Citas pendientes</span>
      <div class="value">{{citas_pendientes}}</div>
      <a href="index.php?page=CitasController&action=index" class="summary-link">Administrar citas</a>
    </div>
  </div>

  <!-- Calendario de citas -->
  <section class="card-panel calendar-card" style="grid-column: 1 / -1;" id="calendar-section" data-mes="{{cal_mes}}" data-anio="{{cal_anio}}">
    <div class="calendar-header" id="calendar-header-partial">
      <h2 class="calendar-title" id="calendar-title">{{cal_nombre_mes}} {{cal_anio}}</h2>
      <div class="calendar-nav">
        <button type="button" class="calendar-nav-btn" data-action="prev" data-mes="{{prev_mes}}" data-anio="{{prev_anio}}" title="Mes anterior">‹</button>
        <button type="button" class="calendar-nav-btn" data-action="next" data-mes="{{next_mes}}" data-anio="{{next_anio}}" title="Mes siguiente">›</button>
      </div>
    </div>
    <div class="calendar-grid" id="calendar-grid">
      <div class="calendar-day-name">Dom</div>
      <div class="calendar-day-name">Lun</div>
      <div class="calendar-day-name">Mar</div>
      <div class="calendar-day-name">Mié</div>
      <div class="calendar-day-name">Jue</div>
      <div class="calendar-day-name">Vie</div>
      <div class="calendar-day-name">Sáb</div>
      {{foreach cal_semanas}}
      {{foreach dias}}
      <a href="index.php?page=HomeController&action=dayView&fecha={{fecha}}" class="calendar-day {{if other_month}}other-month{{endif other_month}} {{if today}}today{{endif today}} {{if has_events}}has-events{{endif has_events}}">
        <span class="calendar-day-number">{{dia}}</span>
        <div class="calendar-events">
          {{foreach eventos}}
          <span class="calendar-event estado-{{estado_id}}">{{hora}} {{paciente}}</span>
          {{endfor eventos}}
          {{if more_events}}
          <span class="calendar-more">+{{more_count}} más</span>
          {{endif more_events}}
        </div>
      </a>
      {{endfor dias}}
      {{endfor cal_semanas}}
    </div>
    <div class="calendar-legend">
      <span class="legend-item"><span class="legend-dot pending"></span>Pendiente</span>
      <span class="legend-item"><span class="legend-dot confirmed"></span>Confirmada</span>
      <span class="legend-item"><span class="legend-dot completed"></span>Completada</span>
      <span class="legend-item"><span class="legend-dot cancelled"></span>Cancelada</span>
      <span class="legend-item"><span class="legend-dot no-show"></span>No Asistió</span>
    </div>
  </section>

  <div class="card-grid">
    <div class="card-panel">
      <div class="section-head">
        <h2>Pacientes recientes</h2>
        <a href="index.php?page=PacientesController&action=index">Ver todos</a>
      </div>
      {{if pacientes}}
      <div class="table-responsive">
        <table class="table-simple">
          <thead>
            <tr>
              <th>Paciente</th>
              <th>Identidad</th>
              <th>Teléfono</th>
            </tr>
          </thead>
          <tbody>
            {{foreach pacientes}}
            <tr>
              <td>{{nombres}} {{apellidos}}</td>
              <td>{{identidad}}</td>
              <td>{{telefono}}</td>
            </tr>
            {{endfor pacientes}}
          </tbody>
        </table>
      </div>
      {{endif pacientes}}
      {{ifnot pacientes}}
      <p>No hay pacientes registrados aún.</p>
      {{endifnot pacientes}}
    </div>

    <div class="card-panel">
      <div class="section-head">
        <h2>Últimos médicos</h2>
        <a href="index.php?page=MedicosController&action=index">Todos los médicos</a>
      </div>
      {{if medicos}}
      <div class="table-responsive">
      <table class="table-simple">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Especialidad</th>
            <th>Teléfono</th>
          </tr>
        </thead>
        <tbody>
          {{foreach medicos}}
          <tr>
            <td>{{nombres}} {{apellidos}}</td>
            <td>{{nombre_especialidad}}</td>
            <td>{{telefono}}</td>
          </tr>
          {{endfor medicos}}
        </tbody>
      </table>
      </div>
      {{endif medicos}}
      {{ifnot medicos}}
      <p>No hay médicos registrados aún.</p>
      {{endifnot medicos}}
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var calendarSection = document.getElementById('calendar-section');
    var calendarGrid = document.getElementById('calendar-grid');
    var calendarTitle = document.getElementById('calendar-title');
    var calendarHeader = document.getElementById('calendar-header-partial');

    if (!calendarSection || !calendarGrid || !calendarTitle) return;

    function loadCalendar(mes, anio) {
      calendarGrid.style.opacity = '0.5';
      calendarGrid.style.pointerEvents = 'none';

      fetch('index.php?page=HomeController&action=calendarPartial&mes=' + mes + '&anio=' + anio, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (response) { return response.text(); })
        .then(function (html) {
          var parser = new DOMParser();
          var doc = parser.parseFromString(html, 'text/html');
          var newGrid = doc.getElementById('calendar-grid');
          var newTitle = doc.getElementById('calendar-title');
          var newHeader = doc.getElementById('calendar-header-partial');
          var newSection = doc.getElementById('calendar-section');

          if (newGrid) {
            calendarGrid.innerHTML = newGrid.innerHTML;
          }
          if (newTitle) {
            calendarTitle.textContent = newTitle.textContent;
          }
          if (newHeader && calendarHeader) {
            calendarHeader.innerHTML = newHeader.innerHTML;
          }
          if (newSection) {
            calendarSection.dataset.mes = newSection.dataset.mes;
            calendarSection.dataset.anio = newSection.dataset.anio;
          }

          calendarGrid.style.opacity = '1';
          calendarGrid.style.pointerEvents = 'auto';
        })
        .catch(function (err) {
          console.error('Error cargando calendario:', err);
          calendarGrid.style.opacity = '1';
          calendarGrid.style.pointerEvents = 'auto';
        });
    }

    calendarSection.addEventListener('click', function (e) {
      var btn = e.target.closest('.calendar-nav-btn');
      if (!btn) return;
      var mes = parseInt(btn.dataset.mes, 10);
      var anio = parseInt(btn.dataset.anio, 10);
      if (!isNaN(mes) && !isNaN(anio)) {
        loadCalendar(mes, anio);
      }
    });
  });
</script>
