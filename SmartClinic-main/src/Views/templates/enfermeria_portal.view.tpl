<main class="nursing-page">
  <header class="nursing-heading">
    <div>
      <span class="nursing-eyebrow">Operación clínica diaria</span>
      <h1>Portal de Enfermería</h1>
      <p>Cola de pacientes del {{fecha_hoy}} en sus centros de salud asignados.</p>
    </div>
    <div class="nursing-identity" aria-label="Enfermera autenticada">
      <span class="nursing-identity__mark" aria-hidden="true">EN</span>
      <div>
        <strong>{{enfermera_nombres}} {{enfermera_apellidos}}</strong>
        <span>Colegiatura {{enfermera_colegiatura}}</span>
      </div>
    </div>
  </header>

  {{if mensajeExito}}
  <div class="nursing-alert is-success" role="status">{{mensaje}}</div>
  {{endif mensajeExito}}
  {{if mensajeError}}
  <div class="nursing-alert is-error" role="alert">{{mensaje}}</div>
  {{endif mensajeError}}

  {{if hayCentros}}
  <section class="nursing-metrics" aria-label="Resumen de la cola filtrada">
    <article class="nursing-metric">
      <span class="nursing-metric__label">Pacientes mostrados</span>
      <strong>{{totalResultados}}</strong>
      <span>Citas de hoy</span>
    </article>
    <article class="nursing-metric nursing-metric--confirmed">
      <span class="nursing-metric__label">Confirmadas</span>
      <strong>{{totalConfirmadas}}</strong>
      <span>Próximas a llegar</span>
    </article>
    <article class="nursing-metric nursing-metric--waiting">
      <span class="nursing-metric__label">En espera</span>
      <strong>{{totalEnEspera}}</strong>
      <span>Ya se encuentran en el centro</span>
    </article>
    <article class="nursing-metric nursing-metric--preclinic-pending">
      <span class="nursing-metric__label">Preclínica pendiente</span>
      <strong>{{totalPreclinicaPendiente}}</strong>
      <span>Pacientes en espera sin signos</span>
    </article>
    <article class="nursing-metric nursing-metric--ready">
      <span class="nursing-metric__label">Preclínica completada</span>
      <strong>{{totalPreclinicaCompletada}}</strong>
      <span>Pacientes listos para consulta</span>
    </article>
    <article class="nursing-metric nursing-metric--in-care">
      <span class="nursing-metric__label">En atención</span>
      <strong>{{totalEnAtencion}}</strong>
      <span>Consulta médica iniciada</span>
    </article>
  </section>

  <section class="nursing-filter-band" aria-labelledby="queue-filter-title">
    <div class="nursing-section-title">
      <div>
        <span>Vista operativa</span>
        <h2 id="queue-filter-title">Cola de pacientes de hoy</h2>
      </div>
      <span class="nursing-result-count">{{totalResultados}} resultados</span>
    </div>

    <form method="GET" action="index.php" class="nursing-filters">
      <input type="hidden" name="page" value="EnfermeriaPortalController">
      <label>
        <span>Centro de salud</span>
        <select name="centro_id">
          <option value="">Todos mis centros</option>
          {{foreach centros}}
          <option value="{{id}}" {{if selected}}selected{{endif selected}}>{{nombre}}</option>
          {{endfor centros}}
        </select>
      </label>
      <label>
        <span>Área asignada</span>
        <select name="area">
          <option value="">Todas las áreas</option>
          {{foreach areas}}
          <option value="{{value}}" {{if selected}}selected{{endif selected}}>{{label}}</option>
          {{endfor areas}}
        </select>
      </label>
      <label>
        <span>Doctor</span>
        <select name="medico_id">
          <option value="">Todos los doctores</option>
          {{foreach medicos}}
          <option value="{{id}}" {{if selected}}selected{{endif selected}}>{{nombre}}</option>
          {{endfor medicos}}
        </select>
      </label>
      <label>
        <span>Estado del paciente</span>
        <select name="estado_paciente">
          <option value="">Todos los estados</option>
          {{foreach estadosPaciente}}
          <option value="{{value}}" {{if selected}}selected{{endif selected}}>{{nombre}}</option>
          {{endfor estadosPaciente}}
        </select>
      </label>
      <div class="nursing-filter-actions">
        <button type="submit" class="btn btn--primary">Aplicar filtros</button>
        {{if hayFiltros}}
        <a href="index.php?page=EnfermeriaPortalController" class="btn btn--outline">Limpiar</a>
        {{endif hayFiltros}}
      </div>
    </form>
  </section>

  {{if cola}}
  <section class="nursing-queue" aria-label="Pacientes programados para hoy">
    <div class="nursing-table-wrap">
      <table>
        <thead>
          <tr>
            <th>Hora</th>
            <th>Paciente</th>
            <th>Doctor</th>
            <th>Ubicación</th>
            <th>Estado del paciente</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          {{foreach cola}}
          <tr class="{{if esPrioritaria}}is-priority{{endif esPrioritaria}}">
            <td data-label="Hora">
              <strong class="nursing-time">{{hora}}</strong>
              <span class="nursing-subtext">Cita #{{id}}</span>
            </td>
            <td data-label="Paciente">
              <div class="nursing-patient">
                <span class="nursing-patient__avatar" aria-hidden="true">{{paciente_iniciales}}</span>
                <div>
                  <strong>{{paciente_nombre}}</strong>
                  <span>{{paciente_identidad}} · {{paciente_telefono}}</span>
                </div>
              </div>
            </td>
            <td data-label="Doctor">
              <strong>{{medico_nombre}}</strong>
              <span class="nursing-subtext">{{nombre_especialidad}}</span>
            </td>
            <td data-label="Ubicación">
              <strong>{{centro_nombre}}</strong>
              <span class="nursing-subtext">{{enfermera_area}} · Consultorio {{consultorio}}</span>
            </td>
            <td data-label="Estado del paciente">
              <div class="nursing-status-stack">
                <span class="nursing-status {{estado_clase}}">{{nombre_estado}}</span>
                {{if muestraEstadoPreclinica}}
                <span class="nursing-status {{preclinica_clase}}">{{preclinica_estado}}</span>
                {{endif muestraEstadoPreclinica}}
              </div>
            </td>
            <td data-label="Acción">
              {{if puedeConfirmarLlegada}}
              <form method="POST" action="index.php?page=EnfermeriaPortalController&action=confirmarLlegada" class="nursing-arrival-form" data-confirm="¿Confirma que el paciente ya llegó al centro de salud?">
                <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                <input type="hidden" name="cita_id" value="{{id}}">
                <button type="submit" class="nursing-action-link">Confirmar llegada</button>
              </form>
              {{endif puedeConfirmarLlegada}}
              {{if puedeRegistrarPreclinica}}
              <a href="index.php?page=EnfermeriaPortalController&action=preclinica&cita_id={{id}}" class="nursing-action-link">{{preclinica_accion}}</a>
              {{endif puedeRegistrarPreclinica}}
              {{ifnot tieneAccion}}
              <span class="nursing-action-state">{{accion_estado}}</span>
              {{endifnot tieneAccion}}
            </td>
          </tr>
          {{endfor cola}}
        </tbody>
      </table>
    </div>
  </section>
  {{endif cola}}

  {{ifnot cola}}
  <section class="nursing-empty">
    <span class="nursing-empty__icon" aria-hidden="true">+</span>
    <h2>No hay pacientes que coincidan</h2>
    <p>No existen citas de hoy para la combinación de centro, área, doctor y estado seleccionada.</p>
    {{if hayFiltros}}
    <a href="index.php?page=EnfermeriaPortalController" class="btn btn--outline">Ver toda la cola</a>
    {{endif hayFiltros}}
  </section>
  {{endifnot cola}}
  {{endif hayCentros}}

  {{ifnot hayCentros}}
  <section class="nursing-empty">
    <span class="nursing-empty__icon" aria-hidden="true">!</span>
    <h2>No tiene centros de salud asignados</h2>
    <p>Un administrador debe asignar al menos un centro activo a su registro de enfermería.</p>
  </section>
  {{endifnot hayCentros}}
</main>
