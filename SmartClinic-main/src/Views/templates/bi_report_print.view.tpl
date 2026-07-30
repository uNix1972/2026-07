<div class="container section-pad bi-report-page">
  <div class="bi-report-actions">
    <a class="btn btn--outline"
      href="index.php?page=BIController&centro_salud_id={{centroSaludId}}">
      <i class="fas fa-arrow-left" aria-hidden="true"></i>
      Volver al BI
    </a>
    <button type="button" class="btn btn--primary"
      onclick="window.print()">
      <i class="fas fa-print" aria-hidden="true"></i>
      Imprimir
    </button>
  </div>

  <article class="bi-report-document">
    <header class="bi-report-header">
      <div class="bi-report-brand">
        <span>SMARTCLINIC</span>
        <strong>{{reportTitle}}</strong>
      </div>
      <div class="bi-report-folio">
        <span>Generado</span>
        <strong>{{generadoEn}}</strong>
      </div>
    </header>

    <section class="bi-report-meta">
      <div>
        <span>Centro de salud</span>
        <strong>{{centroNombre}}</strong>
        <small>{{centroCodigo}} · {{centroCiudad}}</small>
      </div>
      <div>
        <span>Periodo</span>
        <strong>{{periodoDesde}} al {{periodoHasta}}</strong>
        <small>Preparado por {{generadoPor}}</small>
      </div>
    </section>

    <section class="bi-report-kpis" aria-label="Indicadores del periodo">
      <div>
        <span>Citas</span>
        <strong>{{totalCitas}}</strong>
      </div>
      <div>
        <span>Completadas</span>
        <strong>{{citasCompletadas}}</strong>
      </div>
      <div>
        <span>Canceladas / ausentes</span>
        <strong>{{citasCanceladas}}</strong>
      </div>
      <div>
        <span>Pacientes</span>
        <strong>{{totalPacientes}}</strong>
      </div>
      <div>
        <span>Ingresos</span>
        <strong>L {{ingresos}}</strong>
      </div>
      <div>
        <span>Pagos</span>
        <strong>{{totalPagos}}</strong>
      </div>
    </section>

    {{if reporteEjecutivo}}
    <section class="bi-report-section">
      <h2>Distribución de citas por estado</h2>
      <div class="bi-report-table-wrap">
        <table class="bi-report-table">
          <thead>
            <tr>
              <th>Estado</th>
              <th class="is-number">Cantidad</th>
            </tr>
          </thead>
          <tbody>
            {{foreach citasPorEstado}}
            <tr>
              <td>{{estado}}</td>
              <td class="is-number">{{total}}</td>
            </tr>
            {{endfor citasPorEstado}}
          </tbody>
        </table>
      </div>
    </section>

    <section class="bi-report-section">
      <h2>Productividad médica</h2>
      <div class="bi-report-table-wrap">
        <table class="bi-report-table">
          <thead>
            <tr>
              <th>Médico</th>
              <th>Especialidad</th>
              <th class="is-number">Citas</th>
              <th class="is-number">Completadas</th>
            </tr>
          </thead>
          <tbody>
            {{foreach cargaMedicos}}
            <tr>
              <td>{{medico}}</td>
              <td>{{especialidad}}</td>
              <td class="is-number">{{total_citas}}</td>
              <td class="is-number">{{completadas}}</td>
            </tr>
            {{endfor cargaMedicos}}
          </tbody>
        </table>
      </div>
    </section>
    {{endif reporteEjecutivo}}

    {{if reporteCitas}}
    <section class="bi-report-section">
      <h2>Detalle de citas del periodo</h2>
      {{ifnot sinCitas}}
      <div class="bi-report-table-wrap">
        <table class="bi-report-table bi-report-table--compact">
          <thead>
            <tr>
              <th>Cita</th>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Paciente</th>
              <th>Médico</th>
              <th>Especialidad</th>
              <th>Consultorio</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            {{foreach citas}}
            <tr>
              <td>#{{id}}</td>
              <td>{{fecha}}</td>
              <td>{{hora}}</td>
              <td>{{paciente}}</td>
              <td>{{medico}}</td>
              <td>{{especialidad}}</td>
              <td>{{consultorio}}</td>
              <td>{{estado}}</td>
            </tr>
            {{endfor citas}}
          </tbody>
        </table>
      </div>
      {{endifnot sinCitas}}
      {{if sinCitas}}
      <p class="bi-report-empty">
        No hay citas para el centro y periodo seleccionados.
      </p>
      {{endif sinCitas}}
    </section>
    {{endif reporteCitas}}

    {{if reporteFinanciero}}
    <section class="bi-report-financial-summary">
      <div>
        <span>Total recibido</span>
        <strong>L {{ingresos}}</strong>
      </div>
      <div>
        <span>Transacciones</span>
        <strong>{{totalPagos}}</strong>
      </div>
      <div>
        <span>Pago promedio</span>
        <strong>L {{promedioPago}}</strong>
      </div>
    </section>

    <section class="bi-report-section">
      <h2>Detalle de ingresos y pagos</h2>
      {{ifnot sinPagos}}
      <div class="bi-report-table-wrap">
        <table class="bi-report-table bi-report-table--compact">
          <thead>
            <tr>
              <th>Pago</th>
              <th>Cita</th>
              <th>Fecha</th>
              <th>Paciente</th>
              <th>Médico</th>
              <th>Método</th>
              <th>Transacción</th>
              <th class="is-number">Total</th>
            </tr>
          </thead>
          <tbody>
            {{foreach pagos}}
            <tr>
              <td>#{{id}}</td>
              <td>#{{cita_id}}</td>
              <td>{{fecha}}</td>
              <td>{{paciente}}</td>
              <td>{{medico}}</td>
              <td>{{metodo_pago}}</td>
              <td>{{id_transaccion_api}}</td>
              <td class="is-number">L {{total_formateado}}</td>
            </tr>
            {{endfor pagos}}
          </tbody>
        </table>
      </div>
      {{endifnot sinPagos}}
      {{if sinPagos}}
      <p class="bi-report-empty">
        No hay pagos para el centro y periodo seleccionados.
      </p>
      {{endif sinPagos}}
    </section>
    {{endif reporteFinanciero}}

    <footer class="bi-report-signatures">
      <div>
        <span>Elaborado por</span>
        <strong>{{generadoPor}}</strong>
      </div>
      <div>
        <span>Revisado por</span>
        <strong>&nbsp;</strong>
      </div>
      <div>
        <span>Fecha de revisión</span>
        <strong>&nbsp;</strong>
      </div>
    </footer>
  </article>
</div>
