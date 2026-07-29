<div class="container section-pad clinical-page">
  <header class="clinical-hero">
    <div><span class="clinical-eyebrow">Portal del doctor</span><h2>Preclínica</h2><p>Registra los signos vitales antes de iniciar la atención médica.</p></div>
    <div class="clinical-hero__actions"><span class="clinical-doctor">Dr. {{medico_nombres}} {{medico_apellidos}}</span><a href="index.php?page=DoctoresController" class="btn btn--outline">Volver al portal</a></div>
  </header>
  {{if msg}}<div class="clinical-alert">{{msg}}</div>{{endif msg}}
  <section class="sc-panel-card clinical-selector">
    <div><span class="clinical-step">Paso 1</span><h3>Seleccionar cita agendada</h3><p>Elige al paciente que pasará a toma de signos vitales.</p></div>
    <form method="GET" action="index.php" class="clinical-select-form">
      <input type="hidden" name="page" value="DoctoresController"><input type="hidden" name="action" value="preclinica">
      <label for="preclinica_cita">Cita</label><div class="clinical-select-row"><select id="preclinica_cita" name="cita_id" required>{{foreach agenda}}<option value="{{id}}" {{selected}}>#{{id}} · {{fecha_hora}} · {{paciente_nombres}} {{paciente_apellidos}}</option>{{endfor agenda}}</select><button type="submit" class="btn btn--primary">Cargar cita</button></div>
    </form>
  </section>
  {{if hay_cita}}
  <section class="clinical-patient-card"><div class="clinical-avatar">PC</div><div><span>Paciente</span><strong>{{paciente_nombres}} {{paciente_apellidos}}</strong><small>Cita #{{cita_id}} · {{fecha_hora}}</small></div><span class="clinical-status">{{nombre_estado}}</span></section>
  <section class="sc-panel-card clinical-form-card">
    <div class="clinical-section-heading"><div><span class="clinical-step">Paso 2</span><h3>Toma de signos vitales</h3></div><p>Los datos quedan asociados exclusivamente a esta cita.</p></div>
    <form method="POST" action="index.php?page=DoctoresController&action=guardarSignos">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}"><input type="hidden" name="cita_id" value="{{cita_id}}"><input type="hidden" name="return_to" value="preclinica">
      <div class="clinical-fields-grid">
        <label><span>Temperatura</span><div class="clinical-input"><input type="number" step="0.1" min="30" max="45" name="temperatura" value="{{temperatura}}" placeholder="36.5"><em>°C</em></div></label>
        <label><span>Presión sistólica</span><div class="clinical-input"><input type="number" min="50" max="260" name="presion_sistolica" value="{{presion_sistolica}}" placeholder="120"><em>mmHg</em></div></label>
        <label><span>Presión diastólica</span><div class="clinical-input"><input type="number" min="30" max="180" name="presion_diastolica" value="{{presion_diastolica}}" placeholder="80"><em>mmHg</em></div></label>
        <label><span>Frecuencia cardiaca</span><div class="clinical-input"><input type="number" min="20" max="250" name="frecuencia_cardiaca" value="{{frecuencia_cardiaca}}" placeholder="72"><em>lpm</em></div></label>
        <label><span>Frecuencia respiratoria</span><div class="clinical-input"><input type="number" min="5" max="80" name="frecuencia_respiratoria" value="{{frecuencia_respiratoria}}" placeholder="16"><em>rpm</em></div></label>
        <label><span>Saturación de oxígeno</span><div class="clinical-input"><input type="number" step="0.1" min="50" max="100" name="saturacion_oxigeno" value="{{saturacion_oxigeno}}" placeholder="98"><em>%</em></div></label>
        <label><span>Peso</span><div class="clinical-input"><input type="number" step="0.01" min="1" max="500" name="peso" value="{{peso}}" placeholder="70"><em>kg</em></div></label>
        <label><span>Talla</span><div class="clinical-input"><input type="number" step="0.1" min="30" max="250" name="talla" value="{{talla}}" placeholder="170"><em>cm</em></div></label>
      </div>
      <label class="clinical-notes"><span>Observaciones de preclínica</span><textarea name="notas" maxlength="500" rows="3" placeholder="Anote condiciones relevantes para el médico...">{{signos_notas}}</textarea></label>
      <div class="clinical-form-actions"><span>El médico podrá consultar estos datos al llenar la atención y en el PDF.</span><button class="btn btn--primary" type="submit">Guardar preclínica</button></div>
    </form>
  </section>
  {{endif hay_cita}}
  {{ifnot hay_cita}}<section class="sc-panel-card clinical-empty"><h3>No hay una cita disponible</h3><p>Cuando exista una cita agendada aparecerá aquí para completar su preclínica.</p></section>{{endifnot hay_cita}}
  <section class="sc-panel-card clinical-queue">
    <div class="clinical-section-heading"><div><span class="clinical-step">Seguimiento</span><h3>Agenda y estado de preclínica</h3></div></div>
    {{if agenda}}<div class="table-responsive"><table><thead><tr><th>Cita</th><th>Paciente</th><th>Estado de cita</th><th>Signos vitales</th><th>Acción</th></tr></thead><tbody>{{foreach agenda}}<tr><td>#{{id}}<br><small>{{fecha_hora}}</small></td><td><strong>{{paciente_nombres}} {{paciente_apellidos}}</strong></td><td>{{nombre_estado}}</td><td><span class="clinical-pill">{{signos_estado}}</span></td><td><a class="btn btn--outline" href="index.php?page=DoctoresController&action=preclinica&cita_id={{id}}">Abrir</a></td></tr>{{endfor agenda}}</tbody></table></div>{{endif agenda}}
    {{ifnot agenda}}<p>No hay citas asignadas.</p>{{endifnot agenda}}
  </section>
</div>
