<div class="container section-pad">
  <div class="form-card">
    <div style="margin-bottom:30px;">
      <h2 style="color:#033B9F; margin-bottom:10px; font-size:2.2rem;">Registrar Médico</h2>
      <p style="color:#636366;">Completa los datos del médico para agregarlo al sistema.</p>
    </div>

    {{if error}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">{{error}}</div>
    {{endif error}}

    <form method="POST">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <div class="form-grid">
        <div class="form-group">
          <label for="especialidad_id">Especialidad</label>
          <select id="especialidad_id" name="especialidad_id" required>
            <option value="">Seleccione...</option>
            {{foreach especialidades}}
            <option value="{{id}}">{{nombre_especialidad}}</option>
            {{endfor especialidades}}
          </select>
        </div>

        <div class="form-group">
          <label for="num_colegiatura">N° Colegiatura</label>
          <input id="num_colegiatura" type="text" name="num_colegiatura" required placeholder="Número de colegiatura">
        </div>

        <div class="form-group">
          <label for="nombres">Nombres</label>
          <input id="nombres" type="text" name="nombres" required placeholder="Nombres">
        </div>

        <div class="form-group">
          <label for="apellidos">Apellidos</label>
          <input id="apellidos" type="text" name="apellidos" required placeholder="Apellidos">
        </div>

        <div class="form-group" style="grid-column:1/3;">
          <label for="telefono">Teléfono</label>
          <input id="telefono" type="text" name="telefono" required placeholder="Teléfono">
        </div>
      </div>

      <div class="form-actions">
        <a href="index.php?page=MedicosController&action=index" class="btn btn--outline">Cancelar</a>
        <button type="submit" class="btn btn--primary">Guardar Médico</button>
      </div>
    </form>
  </div>
</div>
