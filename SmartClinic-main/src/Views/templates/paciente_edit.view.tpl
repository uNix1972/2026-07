<div class="container section-pad">
  <div class="form-card">
    <div style="margin-bottom:30px;">
      <h2 style="color:#033B9F; margin-bottom:10px; font-size:2rem;">Editar Paciente</h2>
      <p style="color:#636366;">Actualice la información del paciente.</p>
    </div>

    {{if error}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">{{error}}</div>
    {{endif error}}

    <form method="POST" action="index.php?page=PacientesController&action=edit&id={{id}}">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <div class="form-grid">
        <div class="form-group">
          <label for="identidad">Identidad</label>
          <input id="identidad" type="text" name="identidad" value="{{identidad}}" placeholder="Ej. 0801199901234" minlength="5" maxlength="20" required>
        </div>

        <div class="form-group">
          <label for="nombres">Nombres</label>
          <input id="nombres" type="text" name="nombres" value="{{nombres}}" maxlength="100" required>
        </div>

        <div class="form-group">
          <label for="apellidos">Apellidos</label>
          <input id="apellidos" type="text" name="apellidos" value="{{apellidos}}" maxlength="100" required>
        </div>

        <div class="form-group">
          <label for="telefono">Teléfono</label>
          <input id="telefono" type="text" name="telefono" value="{{telefono}}" placeholder="Ej. 9999-9999" minlength="7" maxlength="20" pattern="[0-9+\-\s()]{7,20}" title="Solo números, espacios, +, - y paréntesis (7 a 20 caracteres)" required>
        </div>

        <div class="form-group">
          <label for="fecha_nacimiento">Fecha de nacimiento</label>
          <input id="fecha_nacimiento" type="date" name="fecha_nacimiento" value="{{fecha_nacimiento}}" min="{{minFechaNacimiento}}" max="{{maxFechaNacimiento}}" required>
        </div>
      </div>

      <div class="form-group" style="margin-top:20px;">
        <label for="direccion">Dirección</label>
        <textarea id="direccion" name="direccion" rows="4" maxlength="255" required>{{direccion}}</textarea>
      </div>

      <div class="form-actions">
        <a href="index.php?page=PacientesController&action=index" class="btn btn--outline">Cancelar</a>
        <button type="submit" class="btn btn--primary">Actualizar Paciente</button>
      </div>
    </form>
  </div>
</div>