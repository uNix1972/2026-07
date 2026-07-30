<main class="nursing-page nursing-preclinic-page">
  <header class="nursing-heading">
    <div>
      <span class="nursing-eyebrow">Atención de enfermería</span>
      <h1>Preclínica</h1>
      <p>Registre los signos vitales antes de que el médico inicie la consulta.</p>
    </div>
    <a href="index.php?page=EnfermeriaPortalController" class="btn btn--outline">Volver a la cola</a>
  </header>

  {{if mensajeError}}
  <div class="nursing-alert is-error" role="alert">{{mensaje}}</div>
  {{endif mensajeError}}

  <section class="nursing-preclinic-context" aria-label="Cita seleccionada">
    <div class="nursing-patient nursing-preclinic-patient">
      <span class="nursing-patient__avatar" aria-hidden="true">{{paciente_iniciales}}</span>
      <div>
        <span class="nursing-context-label">Paciente</span>
        <strong>{{paciente_nombre}}</strong>
        <span>{{paciente_identidad}} · {{paciente_telefono}}</span>
      </div>
    </div>
    <div>
      <span class="nursing-context-label">Cita</span>
      <strong>#{{cita_id}} · {{fecha_cita}} · {{hora_cita}}</strong>
      <span>{{centro_nombre}} · {{enfermera_area}} · Consultorio {{consultorio}}</span>
    </div>
    <div>
      <span class="nursing-context-label">Médico</span>
      <strong>{{medico_nombre}}</strong>
      <span>{{nombre_especialidad}}</span>
    </div>
  </section>

  <section class="nursing-preclinic-form" aria-labelledby="vitals-title">
    <div class="nursing-section-title">
      <div>
        <span>{{if esEdicion}}Corrección autorizada{{endif esEdicion}}{{ifnot esEdicion}}Nuevo registro{{endifnot esEdicion}}</span>
        <h2 id="vitals-title">Toma de signos vitales</h2>
      </div>
      <span class="nursing-result-count">Las 8 mediciones son obligatorias</span>
    </div>

    <form method="POST" action="index.php?page=EnfermeriaPortalController&action=guardarPreclinica" data-confirm="¿Confirma que los signos vitales registrados son correctos?">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <input type="hidden" name="cita_id" value="{{cita_id}}">

      <div class="nursing-vitals-grid">
        <label>
          <span>Temperatura</span>
          <div class="nursing-unit-input">
            <input type="number" name="temperatura" value="{{temperatura}}" min="30" max="45" step="0.1" placeholder="36.5" required>
            <span>°C</span>
          </div>
        </label>
        <label>
          <span>Presión sistólica</span>
          <div class="nursing-unit-input">
            <input type="number" name="presion_sistolica" value="{{presion_sistolica}}" min="50" max="260" step="1" placeholder="120" required>
            <span>mmHg</span>
          </div>
        </label>
        <label>
          <span>Presión diastólica</span>
          <div class="nursing-unit-input">
            <input type="number" name="presion_diastolica" value="{{presion_diastolica}}" min="30" max="180" step="1" placeholder="80" required>
            <span>mmHg</span>
          </div>
        </label>
        <label>
          <span>Frecuencia cardiaca</span>
          <div class="nursing-unit-input">
            <input type="number" name="frecuencia_cardiaca" value="{{frecuencia_cardiaca}}" min="20" max="250" step="1" placeholder="72" required>
            <span>lpm</span>
          </div>
        </label>
        <label>
          <span>Frecuencia respiratoria</span>
          <div class="nursing-unit-input">
            <input type="number" name="frecuencia_respiratoria" value="{{frecuencia_respiratoria}}" min="5" max="80" step="1" placeholder="16" required>
            <span>rpm</span>
          </div>
        </label>
        <label>
          <span>Saturación de oxígeno</span>
          <div class="nursing-unit-input">
            <input type="number" name="saturacion_oxigeno" value="{{saturacion_oxigeno}}" min="50" max="100" step="0.1" placeholder="98" required>
            <span>%</span>
          </div>
        </label>
        <label>
          <span>Peso</span>
          <div class="nursing-unit-input">
            <input type="number" name="peso" value="{{peso}}" min="1" max="500" step="0.01" placeholder="70" required>
            <span>kg</span>
          </div>
        </label>
        <label>
          <span>Talla</span>
          <div class="nursing-unit-input">
            <input type="number" name="talla" value="{{talla}}" min="30" max="250" step="0.1" placeholder="170" required>
            <span>cm</span>
          </div>
        </label>
      </div>

      <label class="nursing-notes-field">
        <span>Observaciones de preclínica</span>
        <textarea name="notas" maxlength="500" rows="4" placeholder="Condiciones relevantes para el médico...">{{signos_notas}}</textarea>
      </label>

      <div class="nursing-preclinic-actions">
        <span>Los datos quedarán asociados exclusivamente a esta cita.</span>
        <div>
          <a href="index.php?page=EnfermeriaPortalController" class="btn btn--outline">Cancelar</a>
          <button type="submit" class="btn btn--primary">{{if esEdicion}}Actualizar preclínica{{endif esEdicion}}{{ifnot esEdicion}}Guardar preclínica{{endifnot esEdicion}}</button>
        </div>
      </div>
    </form>
  </section>
</main>
