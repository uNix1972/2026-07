<div class="container section-pad clinical-page">
  <header class="clinical-hero">
    <div class="clinical-hero__content">
      <span class="clinical-hero__eyebrow">SmartClinic · Atención médica</span>
      <h1>Portal de doctores</h1>
      <p>Agenda, signos vitales y expediente clínico por cada atención, todo en un mismo lugar.</p>
    </div>
    <div class="clinical-hero__actions">
      <a href="index.php?page=DoctoresController&action=preclinica" class="btn btn--primary">Abrir Preclínica</a>
      <a href="index.php?page=HomeController" class="btn btn--outline">Volver al panel</a>
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

  <div class="sc-two-columns">
    <section class="sc-panel-card">
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
            <td style="padding:12px;">
              <form method="POST" action="index.php?page=DoctoresController&action=iniciarAtencion" style="display:inline;">
                <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                <input type="hidden" name="cita_id" value="{{id}}">
                <button type="submit" class="btn btn--primary" style="padding:8px 12px;">Iniciar atención</button>
              </form>
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
            {{foreach agenda}}
            <option value="{{id}}" data-temperatura="{{temperatura}}" data-sistolica="{{presion_sistolica}}" data-diastolica="{{presion_diastolica}}" data-cardiaca="{{frecuencia_cardiaca}}" data-respiratoria="{{frecuencia_respiratoria}}" data-saturacion="{{saturacion_oxigeno}}" data-peso="{{peso}}" data-talla="{{talla}}">#{{id}} · {{fecha_hora}} · {{paciente_nombres}} {{paciente_apellidos}}</option>
            {{endfor agenda}}
          </select>
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
  </div>

  <section class="sc-panel-card" style="margin-top:22px;">
    <h3>Mis pacientes atendidos</h3>
    <p class="clinical-section-intro">Pacientes con al menos una consulta documentada o finalizada por usted.</p>
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
    {{endif pacientes}}
    {{ifnot pacientes}}<p style="color:#64748b;">Aún no ha atendido pacientes.</p>{{endifnot pacientes}}
  </section>

  <section class="sc-panel-card" style="margin-top:22px;">
    <h3>Registrar signos vitales por cita</h3>
    <p class="clinical-section-intro">Puede registrar o actualizar los signos. Se mostrarán al completar la consulta y en el PDF.</p>
    {{if agenda}}
    <form method="POST" action="index.php?page=DoctoresController&action=guardarSignos" class="toolbar-form">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <div class="toolbar-field">
        <label for="signos_cita_id">Cita</label>
        <select id="signos_cita_id" name="cita_id" required>
          {{foreach agenda}}
          <option value="{{id}}">#{{id}} · {{fecha_hora}} · {{paciente_nombres}} {{paciente_apellidos}} · {{centro_nombre}}</option>
          {{endfor agenda}}
        </select>
      </div>
      <div class="clinical-fields-grid">
        <div class="toolbar-field"><label for="temperatura">Temperatura °C</label><input id="temperatura" type="number" step="0.1" min="30" max="45" name="temperatura"></div>
        <div class="toolbar-field"><label for="presion_sistolica">Presión sistólica</label><input id="presion_sistolica" type="number" min="50" max="260" name="presion_sistolica"></div>
        <div class="toolbar-field"><label for="presion_diastolica">Presión diastólica</label><input id="presion_diastolica" type="number" min="30" max="180" name="presion_diastolica"></div>
        <div class="toolbar-field"><label for="frecuencia_cardiaca">Frecuencia cardiaca</label><input id="frecuencia_cardiaca" type="number" min="20" max="250" name="frecuencia_cardiaca"></div>
        <div class="toolbar-field"><label for="frecuencia_respiratoria">Frecuencia respiratoria</label><input id="frecuencia_respiratoria" type="number" min="5" max="80" name="frecuencia_respiratoria"></div>
        <div class="toolbar-field"><label for="saturacion_oxigeno">SpO₂ %</label><input id="saturacion_oxigeno" type="number" step="0.1" min="50" max="100" name="saturacion_oxigeno"></div>
        <div class="toolbar-field"><label for="peso">Peso kg</label><input id="peso" type="number" step="0.01" min="1" max="500" name="peso"></div>
        <div class="toolbar-field"><label for="talla">Talla cm</label><input id="talla" type="number" step="0.1" min="30" max="250" name="talla"></div>
      </div>
      <div class="toolbar-field"><label for="signos_notas">Notas</label><textarea id="signos_notas" name="notas" maxlength="500" rows="2"></textarea></div>
      <button class="btn btn--primary" type="submit">Guardar signos vitales</button>
    </form>
    {{endif agenda}}
    {{ifnot agenda}}<p style="color:#64748b;">No hay citas disponibles para registrar signos vitales.</p>{{endifnot agenda}}
  </section>

  <section class="sc-panel-card" style="margin-top:22px;">
    <h3>Agenda del doctor</h3>
    {{if agenda}}
    <div class="table-responsive">
      <table style="width:100%; border-collapse:collapse; text-align:left;">
        <thead><tr style="background:#033B9F; color:white;"><th style="padding:12px;">ID</th><th style="padding:12px;">Fecha</th><th style="padding:12px;">Paciente</th><th style="padding:12px;">Centro / Consultorio</th><th style="padding:12px;">Signos vitales</th><th style="padding:12px;">Estado</th><th style="padding:12px;">Flujo</th></tr></thead>
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
            <a class="btn btn--outline" href="index.php?page=DoctoresController&action=preclinica&cita_id={{id}}">Preclínica</a>
            <form method="POST" action="index.php?page=DoctoresController&action=confirmarLlegada"><input type="hidden" name="csrf_token" value="{{~csrf_token}}"><input type="hidden" name="cita_id" value="{{id}}"><button class="btn btn--outline" type="submit">En espera</button></form>
            <form method="POST" action="index.php?page=DoctoresController&action=finalizar"><input type="hidden" name="csrf_token" value="{{~csrf_token}}"><input type="hidden" name="cita_id" value="{{id}}"><button class="btn btn--primary" type="submit">Finalizar</button></form>
            <a class="btn btn--outline" href="index.php?page=DoctoresController&action=pdf&cita_id={{id}}">PDF</a>
          </td>
        </tr>
        {{endfor agenda}}
        </tbody>
      </table>
    </div>
    {{endif agenda}}
    {{ifnot agenda}}<p style="color:#64748b;">No hay citas asignadas.</p>{{endifnot agenda}}
  </section>
</div>
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
</script>
