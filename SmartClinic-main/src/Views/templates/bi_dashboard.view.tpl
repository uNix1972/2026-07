<div class="container section-pad bi-page">
  <header class="bi-header">
    <div>
      <h2>Inteligencia de negocio</h2>
      <p>Indicadores operativos y financieros por centro de salud.</p>
    </div>
    <a class="btn btn--outline" href="index.php?page=ReportesController">Reportes</a>
  </header>

  {{if sinCentros}}
  <div class="form-alert error" style="display:block;">
    No hay centros de salud disponibles para consultar indicadores.
  </div>
  {{endif sinCentros}}

  {{ifnot sinCentros}}
  <section class="bi-context">
    <form method="GET" action="index.php" class="bi-center-filter">
      <input type="hidden" name="page" value="BIController">
      <label for="bi_centro_salud_id">Centro de salud</label>
      <div class="bi-center-filter__controls">
        <select id="bi_centro_salud_id" name="centro_salud_id" required onchange="this.form.submit()">
          {{foreach centros}}
          <option value="{{id}}" {{if selected}}selected{{endif selected}}>{{nombre_opcion}}</option>
          {{endfor centros}}
        </select>
        <button type="submit" class="btn btn--primary">Aplicar</button>
      </div>
    </form>

    <div class="bi-center-identity">
      <span>{{centroCodigo}}</span>
      <strong>{{centroNombre}}</strong>
      <small>{{centroCiudad}} · {{centroEstadoTexto}}</small>
    </div>
  </section>

  <section class="bi-summary-grid" aria-label="Resumen del centro">
    <article class="bi-summary-card bi-summary-card--appointments">
      <span>Total de citas</span>
      <strong>{{totalCitas}}</strong>
      <small>Histórico del centro</small>
    </article>
    <article class="bi-summary-card bi-summary-card--appointments-month">
      <span>Citas del mes</span>
      <strong>{{citasMesActual}}</strong>
      <small>Programadas en el mes actual</small>
    </article>
    <article class="bi-summary-card bi-summary-card--future">
      <span>Citas futuras activas</span>
      <strong>{{citasFuturas}}</strong>
      <small>Pendientes de atención</small>
    </article>
    <article class="bi-summary-card bi-summary-card--doctors">
      <span>Médicos asignados</span>
      <strong>{{medicosAsignados}}</strong>
      <small>Asignaciones activas</small>
    </article>
    <article class="bi-summary-card bi-summary-card--revenue">
      <span>Ingresos acumulados</span>
      <strong>L {{ingresosTotal}}</strong>
      <small>Pagos vinculados a citas</small>
    </article>
    <article class="bi-summary-card bi-summary-card--revenue-month">
      <span>Ingresos del mes</span>
      <strong>L {{ingresosMesActual}}</strong>
      <small>Pagos recibidos en el mes actual</small>
    </article>
  </section>

  <div class="bi-grid">
    <section class="bi-panel">
      <div class="bi-panel__heading">
        <div>
          <span>Distribución</span>
          <h3>Citas por estado</h3>
        </div>
        <small>{{centroNombre}}</small>
      </div>
      <div class="bi-chart-comparison">
        <figure class="bi-pie-figure">
          <div class="bi-pie">
            <svg viewBox="0 0 120 120" role="img" aria-label="Distribución porcentual de citas por estado">
              <circle class="bi-pie__base" cx="60" cy="60" r="50" pathLength="100"></circle>
              {{foreach citasPorEstado}}
              <circle class="bi-pie__segment {{pieColorClass}}" cx="60" cy="60" r="50"
                pathLength="100" stroke-dasharray="{{piePercentage}} {{pieRemainder}}"
                stroke-dashoffset="{{pieOffset}}" transform="rotate(-90 60 60)"></circle>
              {{endfor citasPorEstado}}
            </svg>
            <div class="bi-pie__center">
              <strong>{{totalCitas}}</strong>
              <span>Citas</span>
            </div>
          </div>
          <figcaption>Participación por estado</figcaption>
        </figure>
        <div class="bi-bars">
          {{foreach citasPorEstado}}
          <div class="bi-bar">
            <div class="bi-bar__value">
              <span class="bi-bar__label">
                <i class="bi-legend-dot {{pieColorClass}}" aria-hidden="true"></i>
                {{estado}} <small>{{piePercentLabel}}%</small>
              </span>
              <strong>{{total}}</strong>
            </div>
            <div class="bi-bar__track"><span class="{{pieColorClass}}" style="width:{{porcentaje}}%;"></span></div>
          </div>
          {{endfor citasPorEstado}}
        </div>
      </div>
    </section>

    <section class="bi-panel">
      <div class="bi-panel__heading">
        <div>
          <span>Capacidad</span>
          <h3>Carga por médico</h3>
        </div>
        <small>Total de citas</small>
      </div>
      {{if cargaMedicos}}
      <div class="bi-chart-comparison">
        <figure class="bi-pie-figure">
          <div class="bi-pie">
            <svg viewBox="0 0 120 120" role="img" aria-label="Distribución porcentual de citas por médico">
              <circle class="bi-pie__base" cx="60" cy="60" r="50" pathLength="100"></circle>
              {{foreach cargaMedicos}}
              <circle class="bi-pie__segment {{pieColorClass}}" cx="60" cy="60" r="50"
                pathLength="100" stroke-dasharray="{{piePercentage}} {{pieRemainder}}"
                stroke-dashoffset="{{pieOffset}}" transform="rotate(-90 60 60)"></circle>
              {{endfor cargaMedicos}}
            </svg>
            <div class="bi-pie__center">
              <strong>{{totalCargaMedicos}}</strong>
              <span>Citas</span>
            </div>
          </div>
          <figcaption>Participación por médico</figcaption>
        </figure>
        <div class="bi-bars bi-bars--doctors">
          {{foreach cargaMedicos}}
          <div class="bi-bar">
            <div class="bi-bar__value">
              <span class="bi-bar__label">
                <i class="bi-legend-dot {{pieColorClass}}" aria-hidden="true"></i>
                {{medico}} <small>{{piePercentLabel}}%</small>
              </span>
              <strong>{{total_citas}}</strong>
            </div>
            <div class="bi-bar__track"><span class="{{pieColorClass}}" style="width:{{porcentaje}}%;"></span></div>
          </div>
          {{endfor cargaMedicos}}
        </div>
      </div>
      {{endif cargaMedicos}}
      {{ifnot cargaMedicos}}
      <p class="bi-empty">No hay médicos asignados ni citas históricas en este centro.</p>
      {{endifnot cargaMedicos}}
    </section>
  </div>

  <div class="bi-grid bi-grid--secondary">
    <section class="bi-panel">
      <div class="bi-panel__heading">
        <div>
          <span>Tendencia</span>
          <h3>Citas por mes</h3>
        </div>
      </div>
      {{if citasPorMes}}
      <div class="bi-list">
        {{foreach citasPorMes}}
        <div><span>{{mes}}</span><strong>{{total}} citas</strong></div>
        {{endfor citasPorMes}}
      </div>
      {{endif citasPorMes}}
      {{ifnot citasPorMes}}
      <p class="bi-empty">Sin citas registradas para este centro.</p>
      {{endifnot citasPorMes}}
    </section>

    <section class="bi-panel">
      <div class="bi-panel__heading">
        <div>
          <span>Facturación</span>
          <h3>Ingresos por mes</h3>
        </div>
      </div>
      {{if ingresos}}
      <div class="bi-list">
        {{foreach ingresos}}
        <div><span>{{mes}}</span><strong>L {{total}}</strong></div>
        {{endfor ingresos}}
      </div>
      {{endif ingresos}}
      {{ifnot ingresos}}
      <p class="bi-empty">Sin pagos registrados para este centro.</p>
      {{endifnot ingresos}}
    </section>
  </div>
  {{endifnot sinCentros}}
</div>
