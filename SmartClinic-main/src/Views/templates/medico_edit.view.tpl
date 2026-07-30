<div class="container section-pad">
  <div class="form-card">
    <div style="margin-bottom:30px;">
      <h2 style="color:#033B9F;margin-bottom:10px;font-size:2.2rem;">Editar Médico</h2>
      <p style="color:#636366;">Actualiza los datos profesionales y lugares de atención.</p>
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

    <form method="POST" action="index.php?page=MedicosController&action=edit&id={{id}}">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">

      <div class="form-grid">
        <div class="form-group">
          <label for="especialidad_id">Especialidad</label>
          <select id="especialidad_id" name="especialidad_id" required>
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
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
          <span style="color:#111827;font-size:1.15rem;font-weight:700;">Centros de salud y consultorios</span>
          <input type="text" id="centro_search_filter" autocomplete="off" placeholder="Buscar centro de salud..." style="max-width:280px;width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.9rem;box-sizing:border-box;">
        </div>

        <div style="border:1px solid #D1D5DB;border-radius:8px;overflow:hidden;">
          {{foreach centros}}
          <div data-centro-assignment data-centro-search="{{nombre}} {{codigo}} {{ciudad}}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;align-items:center;padding:16px;border-bottom:1px solid #E5E7EB;">
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
        <p data-centro-search-empty style="display:none;color:#64748b;padding:14px;margin:0;">No se encontraron centros con ese nombre.</p>
      </fieldset>
      {{endif puedeGuardar}}

      <div class="form-actions">
        <a href="index.php?page=MedicosController&action=index" class="btn btn--outline">Cancelar</a>
        {{if puedeGuardar}}
        <button type="submit" class="btn btn--primary">Actualizar médico</button>
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

(function () {
  var searchInput = document.getElementById("centro_search_filter");
  var filas = document.querySelectorAll("[data-centro-assignment]");
  var vacio = document.querySelector("[data-centro-search-empty]");
  if (!searchInput || !filas.length) {
    return;
  }

  function normalizar(texto) {
    return (texto || "")
      .toString()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .trim();
  }

  searchInput.addEventListener("input", function () {
    var query = normalizar(searchInput.value);
    var visibles = 0;

    filas.forEach(function (fila) {
      var texto = normalizar(fila.getAttribute("data-centro-search"));
      var coincide = query === "" || texto.indexOf(query) !== -1;
      fila.style.display = coincide ? "" : "none";
      if (coincide) {
        visibles += 1;
      }
    });

    if (vacio) {
      vacio.style.display = visibles === 0 ? "block" : "none";
    }
  });
})();
</script>
