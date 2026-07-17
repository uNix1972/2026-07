<div class="container section-pad">
  {{with paciente}}
  <div class="form-card">
    <div style="margin-bottom:30px;">
      <h2 style="color:#033B9F; margin-bottom:10px; font-size:2rem;">Editar Paciente</h2>
      <p style="color:#636366;">Actualice la información del paciente.</p>
    </div>

    {{if &error}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">{{&error}}</div>
    {{endif &error}}

    <form method="POST" action="index.php?page=PacientesController&action=edit&id={{id}}">
      <input type="hidden" name="csrf_token" value="{{&csrf_token}}">
      <div class="form-grid">
        <div class="form-group">
          <label>Identidad</label>
          <input type="text" name="identidad" value="{{identidad}}" required>
        </div>

        <div class="form-group">
          <label>Nombres</label>
          <input type="text" name="nombres" value="{{nombres}}" required>
        </div>

        <div class="form-group">
          <label>Apellidos</label>
          <input type="text" name="apellidos" value="{{apellidos}}" required>
        </div>

        <div class="form-group">
          <label>Teléfono</label>
          <input type="text" name="telefono" value="{{telefono}}" required>
        </div>

        <div class="form-group">
          <label>Fecha de nacimiento</label>
          <input type="date" name="fecha_nacimiento" value="{{fecha_nacimiento}}" required>
        </div>
      </div>

      <div class="form-group" style="margin-top:20px;">
        <label>Dirección</label>
        <textarea name="direccion" rows="4">{{direccion}}</textarea>
      </div>

      <div class="form-actions">
        <a href="index.php?page=PacientesController&action=index" class="btn btn--outline">Cancelar</a>
        <button type="submit" class="btn btn--primary">Actualizar Paciente</button>
      </div>
    </form>
  </div>
  {{endwith paciente}}
</div>