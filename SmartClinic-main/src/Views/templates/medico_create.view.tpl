<div class="container section-pad">
  <div class="form-card">
    <div style="margin-bottom:30px;">
      <h2 style="color:#033B9F;margin-bottom:10px;font-size:2.2rem;">Registrar Médico</h2>
      <p style="color:#636366;">Completa los datos profesionales y lugares de atención.</p>
    </div>

    {{if error}}
    <div class="form-alert error" style="display:block;margin-bottom:16px;">{{error}}</div>
    {{endif error}}

    {{if sinCentros}}
    <div class="form-alert error" style="display:block;margin-bottom:16px;">
      No hay centros de salud activos.
      <a href="index.php?page=CentrosSaludController&action=create">Registrar centro de salud</a>
    </div>
    {{endif sinCentros}}

    <form method="POST" action="index.php?page=MedicosController&action=create">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">

      <div class="form-grid">
        <div class="form-group">
          <label for="especialidad_id">Especialidad</label>
          <select id="especialidad_id" name="especialidad_id" required>
            <option value="">Seleccione...</option>
            {{foreach especialidades}}
            <option value="{{id}}" {{if selected}}selected{{endif selected}}>{{nombre_especialidad}}</option>
            {{endfor especialidades}}
          </select>
        </div>

        <div class="form-group">
          <label for="num_colegiatura">N.º de colegiatura</label>
          <input id="num_colegiatura" type="text" name="num_colegiatura" maxlength="50" value="{{num_colegiatura}}" required>
        </div>

        <div class="form-group">
          <label for="nombres">Nombres</label>
          <input id="nombres" type="text" name="nombres" maxlength="100" value="{{nombres}}" required>
        </div>

        <div class="form-group">
          <label for="apellidos">Apellidos</label>
          <input id="apellidos" type="text" name="apellidos" maxlength="100" value="{{apellidos}}" required>
        </div>

        <div class="form-group" style="grid-column:1/-1;">
          <label for="telefono">Teléfono</label>
          <input id="telefono" type="tel" name="telefono" maxlength="20" value="{{telefono}}" required>
        </div>
      </div>

      {{if puedeGuardar}}
      <fieldset style="margin:28px 0 0;padding:0;border:0;">
        <legend style="margin-bottom:14px;color:#111827;font-size:1.15rem;font-weight:700;">
          Centros de salud y consultorios
        </legend>

        <div style="border:1px solid #D1D5DB;border-radius:8px;overflow:hidden;">
          {{foreach centros}}
          <div data-centro-assignment style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;align-items:center;padding:16px;border-bottom:1px solid #E5E7EB;">
            <label style="display:flex;align-items:flex-start;gap:10px;color:#111827;font-weight:600;">
              <input data-centro-checkbox type="checkbox" name="centro_ids[]" value="{{id}}" {{if selected}}checked{{endif selected}}>
              <span>{{nombre}}<br><small style="color:#64748B;font-weight:400;">{{codigo}} · {{ciudad}}</small></span>
            </label>
            <div class="form-group" style="margin:0;">
              <label for="consultorio_{{id}}">Consultorio</label>
              <input data-consultorio id="consultorio_{{id}}" type="text" name="consultorios[{{id}}]" maxlength="30" value="{{consultorio}}" {{if selected}}required{{endif selected}} {{ifnot selected}}disabled{{endifnot selected}}>
            </div>
          </div>
          {{endfor centros}}
        </div>
      </fieldset>
      {{endif puedeGuardar}}

      <div class="form-actions">
        <a href="index.php?page=MedicosController&action=index" class="btn btn--outline">Cancelar</a>
        {{if puedeGuardar}}
        <button type="submit" class="btn btn--primary">Guardar médico</button>
        {{endif puedeGuardar}}
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll("[data-centro-assignment]").forEach(function (row) {
  var checkbox = row.querySelector("[data-centro-checkbox]");
  var consultorio = row.querySelector("[data-consultorio]");

  function syncConsultorio() {
    consultorio.disabled = !checkbox.checked;
    consultorio.required = checkbox.checked;
    if (!checkbox.checked) {
      consultorio.value = "";
    }
  }

  checkbox.addEventListener("change", syncConsultorio);
  syncConsultorio();
});
</script>
