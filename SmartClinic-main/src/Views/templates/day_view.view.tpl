<style>
  .day-view-shell {
    max-width: 900px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
  }
  .day-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
  }
  .day-title {
    font-size: 2rem;
    font-weight: 800;
    color: var(--sc-blue-900);
  }
  .day-subtitle {
    color: var(--sc-gray-500);
    font-size: 1.1rem;
  }
  .day-actions {
    display: flex;
    gap: 0.75rem;
  }
  .btn-day {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: var(--sc-radius-md);
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all var(--sc-transition);
  }
  .btn-day.primary {
    background: var(--sc-blue-900);
    color: #fff;
    border: 2px solid var(--sc-blue-900);
  }
  .btn-day.primary:hover {
    background: var(--sc-blue-700);
    border-color: var(--sc-blue-700);
  }
  .btn-day.secondary {
    background: #fff;
    color: var(--sc-blue-700);
    border: 2px solid var(--sc-blue-200);
  }
  .btn-day.secondary:hover {
    background: var(--sc-blue-50);
    border-color: var(--sc-blue-700);
  }
  .day-stats {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: var(--sc-blue-50);
    border-radius: var(--sc-radius-lg);
    border: 1px solid var(--sc-blue-200);
  }
  .day-stat {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
  .day-stat-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--sc-gray-500);
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .day-stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--sc-blue-900);
  }
  .appointments-list {
    background: #fff;
    border-radius: var(--sc-radius-xl);
    box-shadow: var(--sc-shadow-md);
    border: 1px solid var(--sc-blue-200);
    overflow: hidden;
  }
  .appointment-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--sc-gray-100);
    transition: background var(--sc-transition);
  }
  .appointment-item:last-child {
    border-bottom: none;
  }
  .appointment-item:hover {
    background: var(--sc-blue-50);
  }
  .appointment-time {
    min-width: 80px;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--sc-blue-900);
  }
  .appointment-info {
    flex: 1;
    min-width: 0;
  }
  .appointment-patient {
    font-weight: 600;
    color: var(--sc-gray-900);
    margin-bottom: 0.25rem;
  }
  .appointment-doctor {
    font-size: 0.9rem;
    color: var(--sc-gray-500);
  }
  .appointment-specialty {
    font-size: 0.8rem;
    color: var(--sc-blue-700);
    font-weight: 500;
  }
  .appointment-status {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }
  .appointment-status.estado-1 {
    background: var(--sc-blue-50);
    color: var(--sc-blue-900);
    border: 1px solid var(--sc-blue-200);
  }
  .appointment-status.estado-2 {
    background: #f0fdf4;
    color: var(--sc-success);
    border: 1px solid #bbf7d0;
  }
  .appointment-status.estado-3 {
    background: #f0fdf4;
    color: var(--sc-success);
    border: 1px solid #bbf7d0;
  }
  .appointment-status.estado-4 {
    background: #fff0f0;
    color: var(--sc-danger);
    border: 1px solid #fecaca;
  }
  .appointment-status.estado-5 {
    background: #fff0f0;
    color: var(--sc-danger);
    border: 1px solid #fecaca;
  }
  .empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--sc-gray-500);
  }
  .empty-state-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
  }
  .empty-state-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--sc-gray-700);
    margin-bottom: 0.5rem;
  }
  .empty-state-text {
    font-size: 1rem;
    line-height: 1.6;
  }
  @media (max-width: 640px) {
    .day-view-shell {
      padding: 1.5rem 1rem;
    }
    .day-title {
      font-size: 1.5rem;
    }
    .day-header {
      flex-direction: column;
      align-items: flex-start;
    }
    .day-actions {
      width: 100%;
    }
    .btn-day {
      flex: 1;
      justify-content: center;
    }
    .day-stats {
      flex-direction: column;
      gap: 1rem;
    }
    .appointment-item {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
    }
    .appointment-time {
      min-width: auto;
    }
  }
</style>

<div class="day-view-shell">
  <div class="day-header">
    <div>
      <h1 class="day-title">{{dia_semana}}, {{fecha_formateada}}</h1>
      <p class="day-subtitle">Citas programadas para este día</p>
    </div>
    <div class="day-actions">
      <a href="index.php?page=CitasController&action=agendar&fecha={{fecha}}" class="btn-day primary">+ Agendar cita</a>
      <a href="index.php?page=HomeController" class="btn-day secondary">← Volver al dashboard</a>
    </div>
  </div>

  <div class="day-stats">
    <div class="day-stat">
      <span class="day-stat-label">Total citas</span>
      <span class="day-stat-value">{{total_citas}}</span>
    </div>
    <div class="day-stat">
      <span class="day-stat-label">Pendientes</span>
      <span class="day-stat-value">{{citas_pendientes}}</span>
    </div>
    <div class="day-stat">
      <span class="day-stat-label">Completadas</span>
      <span class="day-stat-value">{{citas_completadas}}</span>
    </div>
    <div class="day-stat">
      <span class="day-stat-label">Canceladas</span>
      <span class="day-stat-value">{{citas_canceladas}}</span>
    </div>
  </div>

  <div class="appointments-list">
    {{if has_citas}}
      {{foreach citas}}
      <div class="appointment-item">
        <span class="appointment-time">{{hora}}</span>
        <div class="appointment-info">
          <div class="appointment-patient">{{paciente}}</div>
          <div class="appointment-doctor">{{medico}}</div>
          <div class="appointment-specialty">{{especialidad}}</div>
        </div>
        <span class="appointment-status estado-{{estado_id}}">{{estado}}</span>
      </div>
      {{endfor citas}}
    {{endif has_citas}}
    {{ifnot has_citas}}
      <div class="empty-state">
        <div class="empty-state-icon">📅</div>
        <h3 class="empty-state-title">No hay citas para este día</h3>
        <p class="empty-state-text">No se han programado citas para {{fecha_formateada}}.</p>
      </div>
    {{endifnot has_citas}}
  </div>
</div>