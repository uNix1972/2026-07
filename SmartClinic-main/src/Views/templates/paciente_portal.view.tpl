<div class="container section-pad">
  <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:22px;">
    <div>
      <h2 style="font-size:2.6rem; color:#111827; margin-bottom:8px;">Portal del paciente</h2>
      <p style="color:#64748b;">Autoservicio web, citas, pago simulado, historial y recetas.</p>
    </div>
    <a href="index.php?page=HomeController" class="btn btn--outline">Volver al panel</a>
  </div>
  {{if msg}}<div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:14px 18px; border-radius:14px; margin-bottom:16px;">{{msg}}</div>{{endif msg}}

  <section class="sc-panel-card" style="margin-bottom:22px;">
    <h3>Perfil</h3>
    <p><strong>{{paciente_nombres}} {{paciente_apellidos}}</strong></p>
    <p style="color:#64748b;">Teléfono: {{paciente_telefono}} · Dirección: {{paciente_direccion}}</p>
  </section>

  <div class="sc-two-columns">
    <section class="sc-panel-card">
      <h3>Agendar cita en línea</h3>
      <form method="POST" action="index.php?page=PacientePortalController&action=agendar">
        <input type="hidden" name="csrf_token" value="{{csrf_token}}">
        <div class="toolbar-field"><label for="medico_id">Médico</label><select id="medico_id" name="medico_id" required>{{foreach medicos}}<option value="{{id}}">{{nombres}} {{apellidos}} - {{nombre_especialidad}}</option>{{endfor medicos}}</select></div>
        <div class="toolbar-field"><label for="fecha">Fecha</label><input id="fecha" type="date" name="fecha" required></div>
        <div class="toolbar-field"><label for="hora">Hora</label><input id="hora" type="time" name="hora" min="08:00" max="17:00" required></div>
        <button type="submit" class="btn btn--primary" style="margin-top:12px;">Solicitar cita</button>
      </form>
    </section>

    <section class="sc-panel-card">
      <h3>Mis citas</h3>
      {{if citas}}
      <div class="table-responsive"><table style="width:100%; border-collapse:collapse;"><thead><tr style="background:#F1F5F9;"><th style="padding:12px;">ID</th><th style="padding:12px;">Fecha</th><th style="padding:12px;">Estado</th><th style="padding:12px;">Pago</th></tr></thead><tbody>{{foreach citas}}<tr style="border-bottom:1px solid #E5E7EB;"><td style="padding:12px;">{{id}}</td><td style="padding:12px;">{{fecha_hora}}</td><td style="padding:12px;">{{nombre_estado}}</td><td style="padding:12px;"><form method="POST" action="index.php?page=PacientePortalController&action=pagar"><input type="hidden" name="csrf_token" value="{{~csrf_token}}"><input type="hidden" name="cita_id" value="{{id}}"><input type="hidden" name="total" value="750.00"><button class="btn btn--outline" type="submit">Pagar demo</button></form></td></tr>{{endfor citas}}</tbody></table></div>
      {{endif citas}}
      {{ifnot citas}}<p style="color:#64748b;">Aún no tiene citas registradas.</p>{{endifnot citas}}
    </section>
  </div>

  <div class="sc-two-columns" style="margin-top:22px;">
    <section class="sc-panel-card"><h3>Historial médico</h3>{{if historial}}<div class="table-responsive"><table style="width:100%; border-collapse:collapse;"><tbody>{{foreach historial}}<tr style="border-bottom:1px solid #E5E7EB;"><td style="padding:12px;"><strong>{{fecha_hora}}</strong><br>{{diagnostico}}<br><span style="color:#64748b;">{{tratamiento}}</span></td></tr>{{endfor historial}}</tbody></table></div>{{endif historial}}{{ifnot historial}}<p style="color:#64748b;">Sin historial clínico registrado.</p>{{endifnot historial}}</section>
    <section class="sc-panel-card"><h3>Recetas y órdenes</h3>{{if recetas}}<div class="table-responsive"><table style="width:100%; border-collapse:collapse;"><tbody>{{foreach recetas}}<tr style="border-bottom:1px solid #E5E7EB;"><td style="padding:12px;"><strong>{{medicamento}}</strong><br>{{indicaciones}}<br><span style="color:#64748b;">{{fecha_emision}}</span></td></tr>{{endfor recetas}}</tbody></table></div>{{endif recetas}}{{ifnot recetas}}<p style="color:#64748b;">Sin recetas registradas.</p>{{endifnot recetas}}</section>
  </div>
</div>
