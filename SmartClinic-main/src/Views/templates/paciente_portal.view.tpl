<div class="container section-pad clinical-page">
  <header class="clinical-hero">
    <div class="clinical-hero__content">
      <span class="clinical-hero__eyebrow">SmartClinic · Mi salud</span>
      <h1>Portal del paciente</h1>
      <p>Consulte sus citas, signos vitales, historial médico y documentos clínicos protegidos.</p>
    </div>
    <div class="clinical-hero__actions">
      <a href="index.php?page=HomeController" class="btn btn--outline">Volver al panel</a>
    </div>
  </header>
  {{if msg}}<div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:14px 18px; border-radius:14px; margin-bottom:16px;">{{msg}}</div>{{endif msg}}

  <section class="sc-panel-card" style="margin-bottom:22px;">
    <div class="clinical-profile">
      <span class="clinical-profile__avatar">P</span>
      <div>
        <h3>{{paciente_nombres}} {{paciente_apellidos}}</h3>
        <p>Teléfono: {{paciente_telefono}} · Dirección: {{paciente_direccion}}</p>
      </div>
    </div>
  </section>

  <div class="sc-two-columns">
    <section class="sc-panel-card">
      <h3>Agendar cita en línea</h3>
      <form method="POST" action="index.php?page=PacientePortalController&action=agendar">
        <input type="hidden" name="csrf_token" value="{{csrf_token}}">
        <input id="portal_paciente_id" type="hidden" value="{{paciente_id}}">
        <div class="toolbar-field">
          <label for="medico_search">Médico</label>
          <div class="sc-combo" id="medico_combo" data-sc-combo>
            <input type="text" id="medico_search" class="sc-combo-input" autocomplete="off" placeholder="Buscar médico por nombre o especialidad..." data-sc-combo-input data-options="{{~medicosJsonAttr}}" required>
            <input type="hidden" id="medico_id" name="medico_id" data-sc-combo-hidden value="">
            <div class="sc-combo-results" data-sc-combo-results hidden></div>
          </div>
        </div>
        <div class="toolbar-field"><label for="centro_salud_id">Centro de salud y consultorio</label><select id="centro_salud_id" name="centro_salud_id" required disabled><option value="">-- Selecciona un centro --</option></select></div>
        <div class="toolbar-field"><label for="fecha">Fecha</label><input id="fecha" type="date" name="fecha" min="{{minDate}}" max="{{maxDate}}" required></div>
        <div class="toolbar-field"><label for="hora">Hora</label><select id="hora" name="hora" required disabled><option value="">-- Selecciona una hora --</option></select></div>
        <button type="submit" class="btn btn--primary" style="margin-top:12px;">Solicitar cita</button>
      </form>
    </section>

    <section class="sc-panel-card">
      <h3>Mis citas</h3>
      {{if mostrarFiltroCentros}}
      <div class="agenda-filtros-centro" style="display:flex; gap:8px; margin:0 0 14px; flex-wrap:wrap;">
        {{foreach centrosFiltro}}
        <a class="btn {{if activo}}btn--primary{{endif activo}}{{ifnot activo}}btn--outline{{endifnot activo}}" style="padding:6px 12px; font-size:13px;" href="{{url}}">{{nombre}}</a>
        {{endfor centrosFiltro}}
      </div>
      {{endif mostrarFiltroCentros}}
      {{if citas}}
      <div class="table-responsive"><table style="width:100%; border-collapse:collapse;"><thead><tr style="background:#F1F5F9;"><th style="padding:12px;">ID</th><th style="padding:12px;">Fecha</th><th style="padding:12px;">Médico</th><th style="padding:12px;">Centro / Consultorio</th><th style="padding:12px;">Estado</th><th style="padding:12px;">Pago</th></tr></thead><tbody>{{foreach citas}}<tr style="border-bottom:1px solid #E5E7EB;"><td style="padding:12px;">{{id}}</td><td style="padding:12px;">{{fecha_hora}}</td><td style="padding:12px;">{{medico_nombres}} {{medico_apellidos}}</td><td style="padding:12px;">{{centro_nombre}}<br><small>Consultorio {{consultorio}}</small></td><td style="padding:12px;">{{nombre_estado}}</td><td style="padding:12px;"><form method="POST" action="index.php?page=PacientePortalController&action=pagar"><input type="hidden" name="csrf_token" value="{{~csrf_token}}"><input type="hidden" name="cita_id" value="{{id}}"><input type="hidden" name="total" value="750.00"><button class="btn btn--outline" type="submit">Pagar demo</button></form></td></tr>{{endfor citas}}</tbody></table></div>
      {{endif citas}}
      {{ifnot citas}}<p style="color:#64748b;">Aún no tiene citas registradas.</p>{{endifnot citas}}
    </section>
  </div>

  <div class="sc-two-columns" style="margin-top:22px;">
    <section class="sc-panel-card"><h3>Historial médico</h3>{{if historial}}<div class="table-responsive"><table style="width:100%; border-collapse:collapse;"><tbody>{{foreach historial}}<tr style="border-bottom:1px solid #E5E7EB;"><td style="padding:12px;"><strong>{{fecha_hora}}</strong><br>{{diagnostico}}<br><span style="color:#64748b;">{{tratamiento}}</span></td></tr>{{endfor historial}}</tbody></table></div>{{endif historial}}{{ifnot historial}}<p style="color:#64748b;">Sin historial clínico registrado.</p>{{endifnot historial}}</section>
    <section class="sc-panel-card"><h3>Recetas y órdenes</h3>{{if recetas}}<div class="table-responsive"><table style="width:100%; border-collapse:collapse;"><tbody>{{foreach recetas}}<tr style="border-bottom:1px solid #E5E7EB;"><td style="padding:12px;"><strong>{{medicamento}}</strong><br>{{indicaciones}}<br><span style="color:#64748b;">{{fecha_emision}}</span></td></tr>{{endfor recetas}}</tbody></table></div>{{endif recetas}}{{ifnot recetas}}<p style="color:#64748b;">Sin recetas registradas.</p>{{endifnot recetas}}</section>
  </div>

  <section class="sc-panel-card" style="margin-top:22px;">
    <h3>Mi expediente por cita</h3>
    <p class="clinical-section-intro">Consulte cada atención, sus signos vitales y descargue una copia PDF.</p>
    <form method="GET" action="index.php" class="clinical-filter">
      <input type="hidden" name="page" value="PacientePortalController">
      <div><label for="fecha_desde">Desde</label><input id="fecha_desde" type="date" name="fecha_desde" value="{{fecha_desde}}"></div>
      <div><label for="fecha_hasta">Hasta</label><input id="fecha_hasta" type="date" name="fecha_hasta" value="{{fecha_hasta}}"></div>
      <button class="btn btn--primary" type="submit">Filtrar expedientes</button>
      <a class="btn btn--outline" href="index.php?page=PacientePortalController">Limpiar</a>
    </form>
    {{if expedientes}}
    <div class="table-responsive">
      <table style="width:100%; border-collapse:collapse;">
        <thead><tr style="background:#F1F5F9;"><th style="padding:12px;">Cita</th><th style="padding:12px;">Médico / Centro</th><th style="padding:12px;">Signos vitales</th><th style="padding:12px;">Diagnóstico</th><th style="padding:12px;">PDF</th></tr></thead>
        <tbody>
        {{foreach expedientes}}
        <tr style="border-bottom:1px solid #E5E7EB;">
          <td style="padding:12px;">#{{id}}<br>{{fecha_hora}}<br><small>{{nombre_estado}}</small></td>
          <td style="padding:12px;">{{medico_nombres}} {{medico_apellidos}}<br>{{nombre_especialidad}}<br><small>{{centro_nombre}}</small></td>
          <td style="padding:12px;">Temp. {{temperatura}} °C<br>PA {{presion_sistolica}}/{{presion_diastolica}}<br>FC {{frecuencia_cardiaca}} · FR {{frecuencia_respiratoria}}<br>SpO₂ {{saturacion_oxigeno}}%</td>
          <td style="padding:12px;">{{diagnostico}}</td>
          <td style="padding:12px;"><a class="btn btn--primary" href="index.php?page=PacientePortalController&action=pdf&cita_id={{id}}">Descargar PDF</a></td>
        </tr>
        {{endfor expedientes}}
        </tbody>
      </table>
    </div>
    {{endif expedientes}}
    {{ifnot expedientes}}<p>Aún no tiene citas registradas.</p>{{endifnot expedientes}}
  </section>
</div>
<style>
  /* Barra de búsqueda con autocompletar (Médico), mismo componente que ya
     se usa en Inventario/Kárdex y en el módulo de Citas del admin.
     Ver public/js/kardex-autocomplete.js para el comportamiento. */
  .sc-combo { position: relative; }
  .sc-combo-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #C7C7CC;
    border-radius: 8px;
    font: inherit;
    box-sizing: border-box;
  }
  .sc-combo-results {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,.12);
    max-height: 220px;
    overflow-y: auto;
    z-index: 20;
  }
  .sc-combo-option {
    padding: 10px 14px;
    cursor: pointer;
    font-size: .95rem;
    color: #111827;
  }
  .sc-combo-option:hover,
  .sc-combo-option.is-active {
    background: #EAF5FD;
  }
  .sc-combo-empty {
    padding: 10px 14px;
    color: #64748b;
    font-size: .9rem;
  }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var medicoCombo = document.getElementById('medico_combo');
  var medicoSearch = document.getElementById('medico_search');
  var medicoIdInput = document.getElementById('medico_id');
  var centroSelect = document.getElementById('centro_salud_id');
  var pacienteId = document.getElementById('portal_paciente_id');
  var fechaInput = document.getElementById('fecha');
  var horaSelect = document.getElementById('hora');

  function refreshCenters() {
    if (!medicoIdInput || !centroSelect) return;
    centroSelect.disabled = true;
    if (!medicoIdInput.value) {
      centroSelect.innerHTML = '<option value="">-- Selecciona un centro --</option>';
      return;
    }
    fetch('index.php?page=CitasController&action=availableCenters&medico_id=' + encodeURIComponent(medicoIdInput.value))
      .then(function (response) { return response.json(); })
      .then(function (data) {
        centroSelect.innerHTML = '<option value="">-- Selecciona un centro --</option>';
        data.forEach(function (item) {
          var option = document.createElement('option');
          option.value = item.value;
          option.textContent = item.label;
          centroSelect.appendChild(option);
        });
        centroSelect.disabled = data.length === 0;
      })
      .catch(function () {
        centroSelect.innerHTML = '<option value="">No se pudieron cargar los centros</option>';
      });
  }

  function refreshTimes() {
    if (!medicoIdInput || !pacienteId || !fechaInput || !horaSelect) return;
    horaSelect.disabled = true;
    horaSelect.innerHTML = '<option value="">-- Selecciona una hora --</option>';
    if (!medicoIdInput.value || !fechaInput.value) return;

    fetch('index.php?page=CitasController&action=availableTimes&medico_id=' + encodeURIComponent(medicoIdInput.value) + '&paciente_id=' + encodeURIComponent(pacienteId.value) + '&fecha=' + encodeURIComponent(fechaInput.value))
      .then(function (response) { return response.json(); })
      .then(function (data) {
        data.forEach(function (item) {
          var option = document.createElement('option');
          option.value = item.value;
          option.textContent = item.label;
          horaSelect.appendChild(option);
        });
        horaSelect.disabled = data.length === 0;
      })
      .catch(function () {
        horaSelect.innerHTML = '<option value="">No se pudieron cargar las horas</option>';
      });
  }

  function actualizarValidezMedico() {
    if (!medicoSearch || !medicoIdInput) return;
    if (!medicoIdInput.value || parseInt(medicoIdInput.value, 10) <= 0) {
      medicoSearch.setCustomValidity('Selecciona un médico de la lista de resultados.');
    } else {
      medicoSearch.setCustomValidity('');
    }
  }

  medicoCombo && medicoCombo.addEventListener('sc-combo:select', function () {
    actualizarValidezMedico();
    refreshCenters();
    refreshTimes();
  });
  medicoCombo && medicoCombo.addEventListener('sc-combo:clear', function () {
    actualizarValidezMedico();
    refreshCenters();
    refreshTimes();
  });
  fechaInput && fechaInput.addEventListener('change', refreshTimes);

  actualizarValidezMedico();
  refreshCenters();
  refreshTimes();
});
</script>
