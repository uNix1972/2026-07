<div class="container section-pad clinical-page">
  <header class="clinical-hero">
    <div class="clinical-hero__content">
      <span class="clinical-hero__eyebrow">SmartClinic · Atención médica</span>
      <h1>Portal de doctores</h1>
      <p>Agenda, sala de espera y expediente clínico por cada atención, todo en un mismo lugar.</p>
    </div>
  </header>

  {{if msgSuccess}}
  <div class="doctor-alert doctor-alert--success" role="status">{{msg}}</div>
  {{endif msgSuccess}}
  {{if msgError}}
  <div class="doctor-alert doctor-alert--error" role="alert">{{msg}}</div>
  {{endif msgError}}

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
              <button type="submit" class="btn btn--outline">Iniciar consulta</button>
            </form>
            {{endif puedeIniciarAtencion}}
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
            <option value="{{id}}" data-centro-id="{{centro_salud_id}}" data-temperatura="{{temperatura}}" data-sistolica="{{presion_sistolica}}" data-diastolica="{{presion_diastolica}}" data-cardiaca="{{frecuencia_cardiaca}}" data-respiratoria="{{frecuencia_respiratoria}}" data-saturacion="{{saturacion_oxigeno}}" data-peso="{{peso}}" data-talla="{{talla}}">#{{id}} · {{fecha_hora}} · {{paciente_nombres}} {{paciente_apellidos}}</option>
            {{endif puedeFinalizar}}
            {{endfor agendaTodas}}
          </select>
          <small style="color:#64748b;">Solo se listan citas "En Atención". Use "Iniciar consulta" en la sala de espera primero.</small>
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
        <div class="receta-lineas-heading">
          <strong>Receta</strong>
          <p class="clinical-section-intro" style="margin:2px 0 10px;">Agregue un medicamento por línea. Si el paciente lo compra con la clínica, márquelo y busque el producto: se genera una factura y se descuenta del inventario del centro.</p>
        </div>
        <div id="receta-lineas-body" class="receta-lineas-editor">
          <div class="receta-linea">
            <button type="button" class="btn-remove-linea receta-linea__remove" title="Quitar medicamento">&times;</button>
            <div class="receta-field">
              <label>Medicamento</label>
              <input type="text" name="medicamento[]" placeholder="Nombre del medicamento u orden">
            </div>
            <div class="receta-field">
              <label>Indicaciones</label>
              <input type="text" name="indicaciones[]" placeholder="Dosis, frecuencia, duración...">
            </div>
            <label class="receta-linea__check">
              <input type="checkbox" class="chk-comprar-aqui"> El paciente lo compra con nosotros
            </label>
            <input type="hidden" name="comprar_aqui[]" value="0" data-compra-flag>
            <div class="receta-linea__compra" hidden>
              <div class="receta-field">
                <label>Buscar producto en inventario</label>
                <div class="sc-combo" data-sc-combo data-receta-producto-combo>
                  <input type="text" class="sc-combo-input" autocomplete="off" placeholder="Escriba el nombre del producto..." data-sc-combo-input data-options="{{~productosRecetaJsonAttr}}">
                  <input type="hidden" name="producto_id[]" data-sc-combo-hidden value="">
                  <div class="sc-combo-results" data-sc-combo-results hidden></div>
                </div>
              </div>
              <div class="receta-field">
                <label>Cantidad a vender</label>
                <input type="number" name="cantidad[]" min="1" placeholder="Ej. 10" data-cantidad-venta>
                <small class="receta-stock-disponible" data-stock-disponible>Disponibles: —</small>
              </div>
              <div class="receta-field">
                <label>Precio unitario</label>
                <span class="receta-linea__precio" data-precio-preview>—</span>
              </div>
            </div>
            <p class="receta-stock-error" data-stock-error role="alert" hidden></p>
          </div>
        </div>
        <div id="receta-stock-alert" class="receta-stock-alert" role="alert" hidden>Corrija las cantidades resaltadas antes de guardar el historial.</div>
        <button type="button" id="btn-agregar-receta-linea" class="btn btn--outline" style="margin:10px 0 18px;">+ Agregar medicamento</button>
        <button type="submit" id="btn-guardar-historial" class="btn btn--primary">Guardar historial</button>
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
  /* Líneas repetibles de la receta ("+ Agregar medicamento"): cada fila
     tiene medicamento/indicaciones y, si se marca "comprar con nosotros",
     un buscador de producto + cantidad + precio informativo. */
  .receta-lineas-editor { display: grid; gap: 12px; margin-bottom: 4px; }
  .receta-linea {
    position: relative;
    display: grid;
    gap: 10px;
    padding: 16px 52px 16px 16px;
    border: 1px solid #d8e2ee;
    border-left: 4px solid #16827a;
    border-radius: 8px;
    background: #f8fafc;
  }
  .receta-linea:focus-within {
    border-color: #78a8dc;
    border-left-color: #075fc7;
    box-shadow: 0 8px 22px rgba(23, 52, 94, 0.08);
  }
  .receta-linea.receta-linea--stock-error {
    border-color: #dc2626;
    border-left-color: #dc2626;
    background: #fff7f7;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, .1);
  }
  .receta-linea__remove { position: absolute; top: 14px; right: 14px; margin-top: 0; width: 34px; height: 34px; font-size: 1.25rem; }
  .receta-field { display: grid; min-width: 0; gap: 6px; }
  .receta-field label { color: #34445d; font-size: .78rem; font-weight: 800; }
  .receta-field input,
  .receta-field select {
    width: 100%;
    min-width: 0;
    height: 42px;
    padding: 0 12px;
    border: 1px solid #b9c6d6;
    border-radius: 8px;
    background: #fff;
    color: #172033;
    font: inherit;
    box-sizing: border-box;
  }
  .receta-field input:focus,
  .receta-field select:focus { outline: none; border-color: #075fc7; box-shadow: 0 0 0 3px rgba(7, 95, 199, 0.12); }
  .receta-linea__check { display: flex; align-items: center; gap: 8px; font-size: .9rem; font-weight: 600; color: #172033; cursor: pointer; }
  .receta-linea__check input { width: auto; height: auto; flex: 0 0 auto; margin: 0; }
  .receta-linea__compra {
    display: grid;
    grid-template-columns: minmax(220px, 1.6fr) minmax(110px, 0.6fr) minmax(120px, 0.7fr);
    gap: 12px;
    align-items: end;
    padding-top: 6px;
    border-top: 1px dashed #cbd5e1;
  }
  /* El atributo HTML "hidden" pierde contra cualquier regla de "display"
     del propio autor (como la de arriba), así que sin este selector el
     bloque de compra se quedaba visible siempre, aunque el checkbox
     estuviera desmarcado. Este selector es más específico y gana. */
  .receta-linea__compra[hidden] { display: none; }
  .receta-linea__precio {
    display: flex;
    align-items: center;
    height: 42px;
    padding: 0 12px;
    border: 1px dashed #b9c6d6;
    border-radius: 8px;
    background: #eef2f7;
    color: #34445d;
    font-weight: 700;
  }
  .receta-stock-disponible {
    color: #475569;
    font-size: .8rem;
    font-weight: 700;
  }
  .receta-stock-error {
    margin: 0;
    padding: 10px 12px;
    border: 1px solid #fca5a5;
    border-radius: 8px;
    background: #fef2f2;
    color: #991b1b;
    font-size: .86rem;
    font-weight: 800;
  }
  .receta-stock-error[hidden],
  .receta-stock-alert[hidden] { display: none; }
  .receta-stock-alert {
    margin: 10px 0 12px;
    padding: 12px 14px;
    border: 1px solid #fca5a5;
    border-radius: 8px;
    background: #fef2f2;
    color: #991b1b;
    font-weight: 800;
  }
  #btn-guardar-historial:disabled {
    cursor: not-allowed;
    opacity: .55;
    filter: grayscale(.25);
  }
  .receta-linea__compra .sc-combo { position: relative; }
  .receta-linea__compra .sc-combo-results {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    z-index: 30;
    max-height: 220px;
    overflow-y: auto;
    border: 1px solid #d8e2ee;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 12px 28px rgba(23, 52, 94, .16);
  }
  .receta-linea__compra .sc-combo-option,
  .receta-linea__compra .sc-combo-empty {
    padding: 10px 12px;
    color: #172033;
    font-size: .9rem;
  }
  .receta-linea__compra .sc-combo-option { cursor: pointer; }
  .receta-linea__compra .sc-combo-option:hover,
  .receta-linea__compra .sc-combo-option.is-active { background: #eaf5fd; }
  .receta-linea__compra .sc-combo-empty { color: #64748b; }
  @media (max-width: 640px) {
    .receta-linea__compra { grid-template-columns: 1fr; }
  }

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

document.addEventListener('DOMContentLoaded', function () {
  /*
   * Líneas repetibles de la receta: "+ Agregar medicamento" clona la
   * primera fila y el checkbox muestra u oculta la compra en clínica.
   * Se usan comentarios de bloque porque Renderer elimina los saltos de
   * línea de la plantilla antes de entregar el JavaScript al navegador.
   */
  var body = document.getElementById('receta-lineas-body');
  var btnAgregar = document.getElementById('btn-agregar-receta-linea');
  if (!body || !btnAgregar) {
    return;
  }
  var form = body.closest('form');
  var citaSelect = document.getElementById('cita_id');
  var btnGuardar = document.getElementById('btn-guardar-historial');
  var stockAlert = document.getElementById('receta-stock-alert');
  var plantillaFila = body.querySelector('.receta-linea').cloneNode(true);

  function formatearPrecio(precio) {
    var numero = parseFloat(precio);
    if (isNaN(numero)) {
      return '—';
    }
    return 'L. ' + numero.toFixed(2);
  }

  function actualizarProductoSeleccionado(fila, producto) {
    fila._productoSeleccionado = producto || null;
    var precioPreview = fila.querySelector('[data-precio-preview]');
    if (!precioPreview) {
      return;
    }
    precioPreview.textContent = producto && producto.precio_unitario
      ? formatearPrecio(producto.precio_unitario)
      : '—';

    var medicamento = fila.querySelector('input[name="medicamento[]"]');
    if (producto && medicamento && medicamento.value.trim() === '') {
      medicamento.value = producto.nombre || '';
    }
  }

  function centroSeleccionado() {
    if (!citaSelect || citaSelect.selectedIndex < 0) {
      return '';
    }
    var opcion = citaSelect.options[citaSelect.selectedIndex];
    return opcion ? String(opcion.getAttribute('data-centro-id') || '') : '';
  }

  function stockDelProducto(producto, centroId) {
    if (!producto || !producto.stock_por_centro || !centroId) {
      return 0;
    }
    var stock = parseInt(producto.stock_por_centro[centroId] || 0, 10);
    return isNaN(stock) ? 0 : stock;
  }

  function limpiarEstadoStock(fila) {
    fila.classList.remove('receta-linea--stock-error');
    var cantidad = fila.querySelector('[data-cantidad-venta]');
    var error = fila.querySelector('[data-stock-error]');
    if (cantidad) cantidad.removeAttribute('aria-invalid');
    if (error) {
      error.hidden = true;
      error.textContent = '';
    }
  }

  function validarStockReceta() {
    var centroId = centroSeleccionado();
    var filas = Array.prototype.slice.call(
      body.querySelectorAll('.receta-linea')
    );
    var solicitudes = {};
    var hayCamposPendientes = false;
    var hayExceso = false;

    filas.forEach(function (fila) {
      limpiarEstadoStock(fila);
      var checkbox = fila.querySelector('.chk-comprar-aqui');
      var productoIdInput = fila.querySelector('[data-sc-combo-hidden]');
      var cantidadInput = fila.querySelector('[data-cantidad-venta]');
      var disponibleTexto = fila.querySelector('[data-stock-disponible]');
      if (!checkbox || !checkbox.checked) {
        if (disponibleTexto) disponibleTexto.textContent = 'Disponibles: —';
        return;
      }

      var producto = fila._productoSeleccionado || null;
      var productoId = productoIdInput ? String(productoIdInput.value || '') : '';
      if (!producto || !productoId || String(producto.id) !== productoId) {
        hayCamposPendientes = true;
        if (disponibleTexto) {
          disponibleTexto.textContent = 'Seleccione un producto para consultar existencias.';
        }
        return;
      }

      var disponible = stockDelProducto(producto, centroId);
      var unidad = String(producto.unidad_medida || 'unidad');
      if (disponibleTexto) {
        disponibleTexto.textContent =
          'Disponibles en este centro: ' + disponible + ' ' + unidad + '(s).';
      }

      var cantidad = cantidadInput
        ? parseInt(cantidadInput.value || '0', 10)
        : 0;
      if (!cantidad || cantidad < 1) {
        hayCamposPendientes = true;
        return;
      }

      if (!solicitudes[productoId]) {
        solicitudes[productoId] = {
          total: 0,
          disponible: disponible,
          nombre: String(producto.nombre || 'Producto'),
          filas: []
        };
      }
      solicitudes[productoId].total += cantidad;
      solicitudes[productoId].filas.push(fila);
    });

    Object.keys(solicitudes).forEach(function (productoId) {
      var solicitud = solicitudes[productoId];
      if (solicitud.total <= solicitud.disponible) {
        return;
      }
      hayExceso = true;
      solicitud.filas.forEach(function (fila) {
        fila.classList.add('receta-linea--stock-error');
        var cantidad = fila.querySelector('[data-cantidad-venta]');
        var error = fila.querySelector('[data-stock-error]');
        if (cantidad) cantidad.setAttribute('aria-invalid', 'true');
        if (error) {
          error.hidden = false;
          error.textContent =
            'Existencia insuficiente: esta receta solicita '
            + solicitud.total + ' de "' + solicitud.nombre
            + '", pero solo hay ' + solicitud.disponible
            + ' en el centro de la cita.';
        }
      });
    });

    if (btnGuardar) {
      btnGuardar.disabled = hayCamposPendientes || hayExceso;
      btnGuardar.title = hayExceso
        ? 'Corrija las cantidades que superan la existencia disponible.'
        : '';
    }
    if (stockAlert) {
      stockAlert.hidden = !hayExceso;
    }
    return !hayCamposPendientes && !hayExceso;
  }

  function limpiarCompra(fila) {
    var combo = fila.querySelector('[data-receta-producto-combo]');
    var buscador = combo ? combo.querySelector('[data-sc-combo-input]') : null;
    var productoId = combo ? combo.querySelector('[data-sc-combo-hidden]') : null;
    var resultados = combo ? combo.querySelector('[data-sc-combo-results]') : null;
    var cantidad = fila.querySelector('input[name="cantidad[]"]');
    if (buscador) buscador.value = '';
    if (productoId) productoId.value = '';
    if (resultados) {
      resultados.hidden = true;
      resultados.innerHTML = '';
    }
    if (cantidad) cantidad.value = '';
    actualizarProductoSeleccionado(fila, null);
  }

  function wireFila(fila) {
    var checkbox = fila.querySelector('.chk-comprar-aqui');
    var compraFlag = fila.querySelector('[data-compra-flag]');
    var bloqueCompra = fila.querySelector('.receta-linea__compra');
    var combo = fila.querySelector('[data-receta-producto-combo]');
    var buscador = combo ? combo.querySelector('[data-sc-combo-input]') : null;
    var cantidad = fila.querySelector('input[name="cantidad[]"]');
    var removeBtn = fila.querySelector('.receta-linea__remove');

    if (checkbox && bloqueCompra) {
      checkbox.addEventListener('change', function () {
        bloqueCompra.hidden = !checkbox.checked;
        if (compraFlag) compraFlag.value = checkbox.checked ? '1' : '0';
        if (buscador) buscador.required = checkbox.checked;
        if (cantidad) cantidad.required = checkbox.checked;
        if (!checkbox.checked) {
          limpiarCompra(fila);
        } else if (buscador) {
          buscador.focus();
        }
        validarStockReceta();
      });
    }

    if (combo) {
      combo.addEventListener('sc-combo:select', function (event) {
        actualizarProductoSeleccionado(fila, event.detail || null);
        validarStockReceta();
      });
      combo.addEventListener('sc-combo:clear', function () {
        actualizarProductoSeleccionado(fila, null);
        validarStockReceta();
      });
    }

    if (cantidad) {
      cantidad.addEventListener('input', validarStockReceta);
    }

    if (removeBtn) {
      removeBtn.addEventListener('click', function () {
        /*
         * La receta es opcional y puede quedarse sin líneas cuando el
         * médico no indica ningún medicamento.
         */
        fila.remove();
        validarStockReceta();
      });
    }
  }

  document.querySelectorAll('.receta-linea').forEach(wireFila);
  citaSelect && citaSelect.addEventListener('change', validarStockReceta);
  form && form.addEventListener('submit', function (event) {
    if (!validarStockReceta()) {
      event.preventDefault();
      var primeraConError = body.querySelector(
        '.receta-linea--stock-error [data-cantidad-venta]'
      );
      if (primeraConError) primeraConError.focus();
    }
  });
  validarStockReceta();

  btnAgregar.addEventListener('click', function () {
    var clon = plantillaFila.cloneNode(true);
    clon.querySelectorAll('input[type="text"], input[type="number"]').forEach(function (input) {
      input.value = '';
    });
    clon.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
      input.checked = false;
    });
    var compraFlag = clon.querySelector('[data-compra-flag]');
    if (compraFlag) compraFlag.value = '0';
    var productoId = clon.querySelector('[data-sc-combo-hidden]');
    if (productoId) productoId.value = '';
    var resultados = clon.querySelector('[data-sc-combo-results]');
    if (resultados) {
      resultados.hidden = true;
      resultados.innerHTML = '';
    }
    var bloqueCompra = clon.querySelector('.receta-linea__compra');
    if (bloqueCompra) {
      bloqueCompra.hidden = true;
    }
    var precioPreview = clon.querySelector('[data-precio-preview]');
    if (precioPreview) {
      precioPreview.textContent = '—';
    }
    body.appendChild(clon);
    wireFila(clon);
    var combo = clon.querySelector('[data-receta-producto-combo]');
    if (combo && window.ScComboWidget) {
      window.ScComboWidget.inicializar(combo);
    }
    validarStockReceta();
  });
});
</script>
