<div class="container section-pad clinical-page">
  <header class="clinical-hero">
    <div class="clinical-hero__content">
      <span class="clinical-hero__eyebrow">SmartClinic · Atención médica</span>
      <h1>Portal de doctores</h1>
      <p>Agenda, signos vitales y expediente clínico por cada atención, todo en un mismo lugar.</p>
    </div>
  </header>

  {{if msg}}
  <div class="sc-alert sc-alert--success" style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:14px 18px; border-radius:14px; margin-bottom:16px;">{{msg}}</div>
  {{endif msg}}

  <section class="sc-panel-card" style="margin-bottom:22px;">
    <div class="clinical-profile">
      <span class="clinical-profile__avatar">Dr.</span>
      <div>
        <h3>{{medico_nombres}} {{medico_apellidos}}</h3>
        <p>{{medico_especialidad}} · Expediente médico autorizado</p>
      </div>
    </div>
  </section>

  <section class="sc-panel-card" style="margin-bottom:22px;">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
      <h3 style="margin:0;">Agenda del doctor</h3>
      {{if mostrarFiltroCentros}}
      <div class="centro-dropdown">
        <button type="button" class="btn btn--outline centro-dropdown__toggle">
          <span data-centro-dropdown-label>{{centroFiltroLabel}}</span>
          <span class="centro-dropdown__caret">&#9662;</span>
        </button>
        <div class="centro-dropdown__menu">
          <input type="text" class="centro-dropdown__search" placeholder="Buscar centro de salud..." autocomplete="off">
          <div class="centro-dropdown__list">
            {{foreach centrosFiltro}}
            <a href="{{url}}" class="centro-dropdown__item {{if activo}}is-active{{endif activo}}" data-centro-search="{{nombre}}">{{nombre}}</a>
            {{endfor centrosFiltro}}
          </div>
          <p class="centro-dropdown__empty" style="display:none;">No se encontraron centros con ese nombre.</p>
        </div>
      </div>
      {{endif mostrarFiltroCentros}}
    </div>
    <div class="agenda-filtros" style="display:flex; gap:8px; margin:10px 0 16px; flex-wrap:wrap;">
      <a class="btn {{if agendaFiltroDia}}btn--primary{{endif agendaFiltroDia}}{{ifnot agendaFiltroDia}}btn--outline{{endifnot agendaFiltroDia}}" style="padding:8px 14px;" href="{{urlFiltroDia}}">Día</a>
      <a class="btn {{if agendaFiltroSemana}}btn--primary{{endif agendaFiltroSemana}}{{ifnot agendaFiltroSemana}}btn--outline{{endifnot agendaFiltroSemana}}" style="padding:8px 14px;" href="{{urlFiltroSemana}}">Semana</a>
      <a class="btn {{if agendaFiltroMes}}btn--primary{{endif agendaFiltroMes}}{{ifnot agendaFiltroMes}}btn--outline{{endifnot agendaFiltroMes}}" style="padding:8px 14px;" href="{{urlFiltroMes}}">Mes</a>
      <a class="btn {{if agendaFiltroTodos}}btn--primary{{endif agendaFiltroTodos}}{{ifnot agendaFiltroTodos}}btn--outline{{endifnot agendaFiltroTodos}}" style="padding:8px 14px;" href="{{urlFiltroTodos}}">Todos</a>
    </div>
    {{if agenda}}
    <div class="table-responsive">
      <table style="width:100%; border-collapse:collapse; text-align:left;">
        <thead>
          <tr style="background:#033B9F; color:white;">
            <th style="padding:12px;">ID</th>
            <th style="padding:12px;">Fecha</th>
            <th style="padding:12px;">Paciente</th>
            <th style="padding:12px;">Centro / Consultorio</th>
            <th style="padding:12px;">Signos vitales</th>
            <th style="padding:12px;">Estado</th>
            <th style="padding:12px;">Flujo</th>
          </tr>
        </thead>
        <tbody>
        {{foreach agenda}}
        <tr style="border-bottom:1px solid #E5E7EB;">
          <td style="padding:12px;">{{id}}</td>
          <td style="padding:12px;">{{fecha_hora}}</td>
          <td style="padding:12px;">{{paciente_nombres}} {{paciente_apellidos}}</td>
          <td style="padding:12px;">{{centro_nombre}}<br><small>Consultorio {{consultorio}}</small></td>
          <td style="padding:12px;">Temp. {{temperatura}} °C<br>PA {{presion_sistolica}}/{{presion_diastolica}}<br>FC {{frecuencia_cardiaca}} · FR {{frecuencia_respiratoria}}<br>SpO₂ {{saturacion_oxigeno}}%</td>
          <td style="padding:12px;">{{nombre_estado}}</td>
          <td style="padding:12px; display:flex; gap:8px; flex-wrap:wrap;">
            {{if puedeAbrirPreclinica}}
            {{if tieneSignos}}
            <a class="btn btn--outline" href="index.php?page=DoctoresController&action=preclinica&cita_id={{id}}">Editar preclínica</a>
            {{endif tieneSignos}}
            {{ifnot tieneSignos}}
            <a class="btn btn--outline" href="index.php?page=DoctoresController&action=preclinica&cita_id={{id}}">Preclínica</a>
            {{endifnot tieneSignos}}
            {{endif puedeAbrirPreclinica}}
            {{if puedeConfirmarLlegada}}
            <form method="POST" action="index.php?page=DoctoresController&action=confirmarLlegada"><input type="hidden" name="csrf_token" value="{{~csrf_token}}"><input type="hidden" name="cita_id" value="{{id}}"><button class="btn btn--outline" type="submit">En espera</button></form>
            {{endif puedeConfirmarLlegada}}
            {{if puedeNoAsistio}}
            <form method="POST" action="index.php?page=DoctoresController&action=noAsistio" data-confirm="¿Confirma que el paciente no asistió a esta cita?"><input type="hidden" name="csrf_token" value="{{~csrf_token}}"><input type="hidden" name="cita_id" value="{{id}}"><button class="btn btn--outline" type="submit">No asistió</button></form>
            {{endif puedeNoAsistio}}
            {{if puedeFinalizar}}
            <form method="POST" action="index.php?page=DoctoresController&action=finalizar"><input type="hidden" name="csrf_token" value="{{~csrf_token}}"><input type="hidden" name="cita_id" value="{{id}}"><button class="btn btn--outline" type="submit">Finalizar</button></form>
            {{endif puedeFinalizar}}
            {{if puedeVerPdf}}
            <a class="btn btn--outline" href="index.php?page=DoctoresController&action=pdf&cita_id={{id}}">PDF</a>
            {{endif puedeVerPdf}}
          </td>
        </tr>
        {{endfor agenda}}
        </tbody>
      </table>
    </div>
    {{endif agenda}}
    {{ifnot agenda}}<p style="color:#64748b;">No hay citas en este período.</p>{{endifnot agenda}}
  </section>

  <section class="sc-panel-card" style="margin-bottom:22px;">
    <h3>Sala de espera de hoy</h3>
    {{if sala}}
    <div class="table-responsive">
      <table style="width:100%; border-collapse:collapse; text-align:left;">
        <thead><tr style="background:#F1F5F9;"><th style="padding:12px;">Hora</th><th style="padding:12px;">Paciente</th><th style="padding:12px;">Centro / Consultorio</th><th style="padding:12px;">Estado</th><th style="padding:12px;">Acción</th></tr></thead>
        <tbody>
        {{foreach sala}}
        <tr style="border-bottom:1px solid #E5E7EB;">
          <td style="padding:12px;">{{fecha_hora}}</td>
          <td style="padding:12px;">{{paciente_nombres}} {{paciente_apellidos}}</td>
          <td style="padding:12px;">{{centro_nombre}}<br><small>Consultorio {{consultorio}}</small></td>
          <td style="padding:12px;">{{nombre_estado}}</td>
          <td style="padding:12px; display:flex; gap:8px; flex-wrap:wrap;">
            {{if puedeIniciarAtencion}}
            <form method="POST" action="index.php?page=DoctoresController&action=iniciarAtencion">
              <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
              <input type="hidden" name="cita_id" value="{{id}}">
              <button type="submit" class="btn btn--outline">Iniciar atención</button>
            </form>
            {{endif puedeIniciarAtencion}}
            {{if faltaPreclinica}}<a class="btn btn--outline" href="index.php?page=DoctoresController&action=preclinica&cita_id={{id}}">Tomar preclínica</a>{{endif faltaPreclinica}}
            {{if puedeFinalizar}}
            <form method="POST" action="index.php?page=DoctoresController&action=finalizar">
              <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
              <input type="hidden" name="cita_id" value="{{id}}">
              <button type="submit" class="btn btn--outline">Finalizar</button>
            </form>
            {{endif puedeFinalizar}}
          </td>
        </tr>
        {{endfor sala}}
        </tbody>
      </table>
    </div>
    {{endif sala}}
    {{ifnot sala}}<p style="color:#64748b;">No hay pacientes en sala de espera para hoy.</p>{{endifnot sala}}
  </section>

  <section class="sc-panel-card">
      <h3>Registrar historial y receta</h3>
      <form method="POST" action="index.php?page=DoctoresController&action=guardarHistorial" class="toolbar-form">
        <input type="hidden" name="csrf_token" value="{{csrf_token}}">
        <div class="toolbar-field">
          <label for="cita_id">Cita del paciente</label>
          <select id="cita_id" name="cita_id" required>
            {{foreach agendaTodas}}
            {{if puedeFinalizar}}
            <option value="{{id}}" data-temperatura="{{temperatura}}" data-sistolica="{{presion_sistolica}}" data-diastolica="{{presion_diastolica}}" data-cardiaca="{{frecuencia_cardiaca}}" data-respiratoria="{{frecuencia_respiratoria}}" data-saturacion="{{saturacion_oxigeno}}" data-peso="{{peso}}" data-talla="{{talla}}">#{{id}} · {{fecha_hora}} · {{paciente_nombres}} {{paciente_apellidos}}</option>
            {{endif puedeFinalizar}}
            {{endfor agendaTodas}}
          </select>
          <small style="color:#64748b;">Solo se listan citas "En Atención". Use "Iniciar atención" en la agenda primero.</small>
        </div>
        <div id="consulta_signos_resumen" class="clinical-vitals-summary" style="border-radius:14px; padding:14px;">
          <strong>Signos vitales de la cita seleccionada</strong>
          <div class="clinical-vitals-grid">
            <div class="clinical-vital"><span class="clinical-vital__label">Temperatura</span><span class="clinical-vital__value"><span data-vital="temperatura">N/D</span> °C</span></div>
            <div class="clinical-vital"><span class="clinical-vital__label">Presión arterial</span><span class="clinical-vital__value"><span data-vital="sistolica">N/D</span>/<span data-vital="diastolica">N/D</span></span></div>
            <div class="clinical-vital"><span class="clinical-vital__label">Frecuencia cardiaca</span><span class="clinical-vital__value"><span data-vital="cardiaca">N/D</span> lpm</span></div>
            <div class="clinical-vital"><span class="clinical-vital__label">Frecuencia respiratoria</span><span class="clinical-vital__value"><span data-vital="respiratoria">N/D</span> rpm</span></div>
            <div class="clinical-vital"><span class="clinical-vital__label">Saturación</span><span class="clinical-vital__value"><span data-vital="saturacion">N/D</span>%</span></div>
            <div class="clinical-vital"><span class="clinical-vital__label">Peso / talla</span><span class="clinical-vital__value"><span data-vital="peso">N/D</span> kg · <span data-vital="talla">N/D</span> cm</span></div>
          </div>
        </div>
        <div class="toolbar-field"><label for="motivo_consulta">Motivo de consulta</label><input id="motivo_consulta" name="motivo_consulta" required></div>
        <div class="toolbar-field"><label for="diagnostico">Diagnóstico</label><textarea id="diagnostico" name="diagnostico" required rows="3"></textarea></div>
        <div class="toolbar-field"><label for="tratamiento">Tratamiento</label><textarea id="tratamiento" name="tratamiento" rows="3"></textarea></div>
        <div class="toolbar-field"><label for="observaciones">Observaciones</label><textarea id="observaciones" name="observaciones" rows="2"></textarea></div>
        <div class="toolbar-field"><label for="medicamento">Medicamento/orden</label><input id="medicamento" name="medicamento" placeholder="Receta u orden de examen"></div>
        <div class="toolbar-field"><label for="indicaciones">Indicaciones</label><textarea id="indicaciones" name="indicaciones" rows="2"></textarea></div>
        <button type="submit" class="btn btn--primary">Guardar historial</button>
      </form>
  </section>

  <section class="sc-panel-card" style="margin-top:22px;">
    <h3>Mis pacientes atendidos</h3>
    <p class="clinical-section-intro">Pacientes con al menos una consulta documentada o finalizada por usted.</p>
    <form method="GET" action="index.php" class="toolbar-form" style="margin-bottom:16px;">
      <input type="hidden" name="page" value="DoctoresController">
      <div class="toolbar-row" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
        <div class="toolbar-field" style="flex:1; min-width:220px;">
          <label for="pacientes_q">Buscar paciente</label>
          <input type="search" id="pacientes_q" name="pacientes_q" value="{{pacientesQuery}}" placeholder="Nombre, apellido o identidad">
        </div>
        <button type="submit" class="btn btn--primary" style="padding:10px 16px;">Buscar</button>
        {{if pacientesTieneQuery}}<a class="btn btn--outline" style="padding:10px 16px;" href="index.php?page=DoctoresController">Limpiar</a>{{endif pacientesTieneQuery}}
      </div>
    </form>
    {{if pacientes}}
    <div class="table-responsive">
      <table style="width:100%; border-collapse:collapse;">
        <thead><tr style="background:#F1F5F9;"><th style="padding:12px;">Paciente</th><th style="padding:12px;">Identidad</th><th style="padding:12px;">Citas</th><th style="padding:12px;">Última cita</th><th style="padding:12px;">Expediente</th></tr></thead>
        <tbody>
        {{foreach pacientes}}
        <tr style="border-bottom:1px solid #E5E7EB;">
          <td style="padding:12px;">{{nombres}} {{apellidos}}</td>
          <td style="padding:12px;">{{identidad}}</td>
          <td style="padding:12px;">{{total_citas}}</td>
          <td style="padding:12px;">{{ultima_cita}}</td>
          <td style="padding:12px;"><a class="btn btn--outline" href="index.php?page=DoctoresController&action=expediente&paciente_id={{id}}">Ver expediente</a></td>
        </tr>
        {{endfor pacientes}}
        </tbody>
      </table>
    </div>
    <div class="sc-noprint" style="display:flex; justify-content:space-between; align-items:center; padding:16px 0 0; flex-wrap:wrap; gap:12px;">
      <span style="color:#64748b;">Página {{paginaPacientes}} de {{totalPaginasPacientes}}</span>
      <div style="display:flex; gap:10px;">
        {{if urlPaginaAnteriorPacientes}}
        <a class="btn btn--outline" href="{{urlPaginaAnteriorPacientes}}">&larr; Anterior</a>
        {{endif urlPaginaAnteriorPacientes}}
        {{ifnot urlPaginaAnteriorPacientes}}
        <span class="btn btn--outline" style="opacity:.5; pointer-events:none;">&larr; Anterior</span>
        {{endifnot urlPaginaAnteriorPacientes}}
        {{if urlPaginaSiguientePacientes}}
        <a class="btn btn--outline" href="{{urlPaginaSiguientePacientes}}">Siguiente &rarr;</a>
        {{endif urlPaginaSiguientePacientes}}
        {{ifnot urlPaginaSiguientePacientes}}
        <span class="btn btn--outline" style="opacity:.5; pointer-events:none;">Siguiente &rarr;</span>
        {{endifnot urlPaginaSiguientePacientes}}
      </div>
    </div>
    {{endif pacientes}}
    {{ifnot pacientes}}
    {{if pacientesTieneQuery}}<p style="color:#64748b;">No se encontraron pacientes que coincidan con "{{pacientesQuery}}".</p>{{endif pacientesTieneQuery}}
    {{ifnot pacientesTieneQuery}}<p style="color:#64748b;">Aún no ha atendido pacientes.</p>{{endifnot pacientesTieneQuery}}
    {{endifnot pacientes}}
  </section>
</div>
<style>
  /* Dropdown "Centro de salud" junto al título de la Agenda del doctor:
     botón que despliega, hacia abajo, una barra de búsqueda + la lista
     de centros, sin afectar el resto de la tarjeta. */
  .centro-dropdown { position: relative; }
  .centro-dropdown__toggle { display: inline-flex; align-items: center; gap: 8px; }
  .centro-dropdown__caret { font-size: .7rem; }
  .centro-dropdown__menu {
    display: none;
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    background: #fff;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,.14);
    width: 260px;
    z-index: 20;
    padding: 10px;
  }
  .centro-dropdown.is-open .centro-dropdown__menu { display: block; }
  .centro-dropdown__search {
    width: 100%;
    box-sizing: border-box;
    padding: 8px 10px;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: .9rem;
  }
  .centro-dropdown__list {
    max-height: 240px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 2px;
  }
  .centro-dropdown__item {
    display: block;
    padding: 8px 10px;
    border-radius: 8px;
    color: #111827;
    text-decoration: none;
    font-size: .9rem;
    font-weight: 600;
  }
  .centro-dropdown__item:hover { background: #EAF5FD; }
  .centro-dropdown__item.is-active { background: #0260CB; color: #fff; }
  .centro-dropdown__empty { color: #64748b; font-size: .85rem; margin: 6px 2px 2px; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var citaSelect = document.getElementById('cita_id');
  var resumen = document.getElementById('consulta_signos_resumen');

  function updateVitalSummary() {
    if (!citaSelect || !resumen) return;
    var option = citaSelect.options[citaSelect.selectedIndex];
    ['temperatura', 'sistolica', 'diastolica', 'cardiaca', 'respiratoria', 'saturacion', 'peso', 'talla'].forEach(function (field) {
      var target = resumen.querySelector('[data-vital="' + field + '"]');
      var value = option && option.dataset[field] ? option.dataset[field] : 'N/D';
      if (target) target.textContent = value;
    });
  }

  citaSelect && citaSelect.addEventListener('change', updateVitalSummary);
  updateVitalSummary();
});

document.addEventListener('DOMContentLoaded', function () {
  var wrapper = document.querySelector('.centro-dropdown');
  if (!wrapper) {
    return;
  }
  var toggle = wrapper.querySelector('.centro-dropdown__toggle');
  var search = wrapper.querySelector('.centro-dropdown__search');
  var items = wrapper.querySelectorAll('.centro-dropdown__item');
  var vacio = wrapper.querySelector('.centro-dropdown__empty');

  function normalizar(texto) {
    return (texto || '')
      .toString()
      .normalize('NFD')
      .replace(new RegExp('[' + String.fromCharCode(768) + '-' + String.fromCharCode(879) + ']', 'g'), '')
      .toLowerCase()
      .trim();
  }

  function abrir() {
    wrapper.classList.add('is-open');
    if (search) {
      search.value = '';
      filtrar();
      window.setTimeout(function () { search.focus(); }, 0);
    }
  }

  function cerrar() {
    wrapper.classList.remove('is-open');
  }

  function filtrar() {
    var query = normalizar(search ? search.value : '');
    var visibles = 0;
    items.forEach(function (item) {
      var nombre = normalizar(item.getAttribute('data-centro-search'));
      var coincide = query === '' || nombre.indexOf(query) !== -1;
      item.style.display = coincide ? '' : 'none';
      if (coincide) {
        visibles += 1;
      }
    });
    if (vacio) {
      vacio.style.display = visibles === 0 ? '' : 'none';
    }
  }

  toggle && toggle.addEventListener('click', function (event) {
    event.stopPropagation();
    if (wrapper.classList.contains('is-open')) {
      cerrar();
    } else {
      abrir();
    }
  });

  search && search.addEventListener('input', filtrar);
  search && search.addEventListener('click', function (event) {
    event.stopPropagation();
  });

  document.addEventListener('click', function (event) {
    if (wrapper.classList.contains('is-open') && !wrapper.contains(event.target)) {
      cerrar();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && wrapper.classList.contains('is-open')) {
      cerrar();
    }
  });
});
</script>
