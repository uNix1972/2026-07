<div class="container section-pad">
  <div class="form-card">
    <div style="margin-bottom:30px;">
      <h2 style="color:#033B9F; margin-bottom:10px; font-size:2rem;">Registrar Paciente</h2>
      <p style="color:#636366;">Complete la información del paciente para registrarlo en el sistema.</p>
    </div>

    {{if error}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">{{error}}</div>
    {{endif error}}

    <form method="POST">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <div class="form-grid">
        <div class="form-group">
          <label for="identidad">Identidad</label>
          <input id="identidad" type="text" name="identidad" required>
        </div>

        <div class="form-group">
          <label for="nombres">Nombres</label>
          <input id="nombres" type="text" name="nombres" required>
        </div>

        <div class="form-group">
          <label for="apellidos">Apellidos</label>
          <input id="apellidos" type="text" name="apellidos" required>
        </div>

        <div class="form-group">
          <label for="telefono">Teléfono</label>
          <input id="telefono" type="text" name="telefono" required>
        </div>

        <div class="form-group">
          <label for="fecha_nacimiento">Fecha de nacimiento</label>
          <input id="fecha_nacimiento" type="date" name="fecha_nacimiento" required>
        </div>
      </div>

      <div class="form-group" style="margin-top:20px;">
        <label for="direccion">Dirección</label>
        <textarea id="direccion" name="direccion" rows="4"></textarea>
      </div>

      <div class="form-actions">
        <a href="index.php?page=PacientesController&action=index" class="btn btn--outline">Cancelar</a>
        <button type="submit" class="btn btn--primary">Guardar Paciente</button>
      </div>
    </form>
  </div>
</div>
