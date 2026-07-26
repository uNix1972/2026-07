<div class="container section-pad">
  <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:22px;">
    <div>
      <h2 style="font-size:2.6rem; color:#111827; margin-bottom:8px;">Portal de doctores</h2>
      <p style="color:#64748b;">Agenda dinámica, sala de espera, atención e historial clínico básico.</p>
    </div>
    <a href="index.php?page=HomeController" class="btn btn--outline">Volver al panel</a>
  </div>

  {{if msg}}
  <div class="sc-alert sc-alert--success" style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:14px 18px; border-radius:14px; margin-bottom:16px;">{{msg}}</div>
  {{endif msg}}

  <section class="sc-panel-card" style="margin-bottom:22px;">
    <h3>Datos del médico</h3>
    <p><strong>{{medico_nombres}} {{medico_apellidos}}</strong> · {{medico_especialidad}}</p>
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
        <div class="toolbar-field"><label for="cita_id">ID de cita</label><input id="cita_id" type="number" name="cita_id" required placeholder="Ej. 1"></div>
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
    <h3>Agenda del doctor</h3>
    {{if agenda}}
    <div class="table-responsive">
      <table style="width:100%; border-collapse:collapse; text-align:left;">
        <thead><tr style="background:#033B9F; color:white;"><th style="padding:12px;">ID</th><th style="padding:12px;">Fecha</th><th style="padding:12px;">Paciente</th><th style="padding:12px;">Centro / Consultorio</th><th style="padding:12px;">Estado</th><th style="padding:12px;">Flujo</th></tr></thead>
        <tbody>
        {{foreach agenda}}
        <tr style="border-bottom:1px solid #E5E7EB;">
          <td style="padding:12px;">{{id}}</td>
          <td style="padding:12px;">{{fecha_hora}}</td>
          <td style="padding:12px;">{{paciente_nombres}} {{paciente_apellidos}}</td>
          <td style="padding:12px;">{{centro_nombre}}<br><small>Consultorio {{consultorio}}</small></td>
          <td style="padding:12px;">{{nombre_estado}}</td>
          <td style="padding:12px; display:flex; gap:8px; flex-wrap:wrap;">
            <form method="POST" action="index.php?page=DoctoresController&action=confirmarLlegada"><input type="hidden" name="csrf_token" value="{{~csrf_token}}"><input type="hidden" name="cita_id" value="{{id}}"><button class="btn btn--outline" type="submit">En espera</button></form>
            <form method="POST" action="index.php?page=DoctoresController&action=finalizar"><input type="hidden" name="csrf_token" value="{{~csrf_token}}"><input type="hidden" name="cita_id" value="{{id}}"><button class="btn btn--primary" type="submit">Finalizar</button></form>
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
