<div class="container section-pad">
  <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:22px;">
    <div>
      <h2 style="font-size:2.6rem; color:#111827; margin-bottom:8px;">Reportes operativos</h2>
      <p style="color:#64748b;">Resumen rápido para administración y recepción.</p>
    </div>
    <a href="index.php?page=HomeController" class="btn btn--outline">Volver al panel</a>
  </div>

  <div class="list-toolbar">
    <form method="GET" action="index.php" class="toolbar-form">
      <input type="hidden" name="page" value="ReportesController" />
      <div class="toolbar-row">
        <div class="toolbar-field">
          <label for="desde">Desde</label>
          <input id="desde" type="date" name="desde" value="{{desde}}" />
        </div>
        <div class="toolbar-field">
          <label for="hasta">Hasta</label>
          <input id="hasta" type="date" name="hasta" value="{{hasta}}" />
        </div>
        <button type="submit" class="btn btn--primary toolbar-submit">Filtrar</button><a class="btn btn--outline" href="index.php?page=ReportesController&desde={{desde}}&hasta={{hasta}}&export=csv">Exportar CSV</a>
      </div>
    </form>
  </div>

  <div class="sc-report-grid">
    <div class="sc-report-card"><span>Pacientes</span><strong>{{totalPacientes}}</strong></div>
    <div class="sc-report-card"><span>Médicos</span><strong>{{totalMedicos}}</strong></div>
    <div class="sc-report-card"><span>Citas filtradas</span><strong>{{totalCitas}}</strong></div>
    <div class="sc-report-card"><span>Citas de hoy</span><strong>{{citasHoy}}</strong></div>
  </div>

  <div class="sc-two-columns" style="margin-top:22px;">
    <section class="sc-panel-card">
      <h3>Citas por estado</h3>
      {{if estadoRows}}
      <div class="table-responsive">
        <table style="width:100%; border-collapse:collapse; text-align:left;">
          <thead>
            <tr style="background:#F1F5F9; color:#17203a;">
              <th style="padding:12px;">Estado</th>
              <th style="padding:12px;">Total</th>
            </tr>
          </thead>
          <tbody>
            {{foreach estadoRows}}
            <tr style="border-bottom:1px solid #E5E7EB;">
              <td style="padding:12px;">{{estado}}</td>
              <td style="padding:12px;"><strong>{{total}}</strong></td>
            </tr>
            {{endfor estadoRows}}
          </tbody>
        </table>
      </div>
      {{endif estadoRows}}
      {{ifnot estadoRows}}
      <p style="color:#64748b;">No hay citas en el rango seleccionado.</p>
      {{endifnot estadoRows}}
    </section>

    <section class="sc-panel-card">
      <h3>Últimas citas</h3>
      {{if ultimasCitas}}
      <div class="table-responsive">
        <table style="width:100%; border-collapse:collapse; text-align:left;">
          <thead>
            <tr style="background:#F1F5F9; color:#17203a;">
              <th style="padding:12px;">Fecha</th>
              <th style="padding:12px;">Paciente</th>
              <th style="padding:12px;">Estado</th>
            </tr>
          </thead>
          <tbody>
            {{foreach ultimasCitas}}
            <tr style="border-bottom:1px solid #E5E7EB;">
              <td style="padding:12px;">{{fecha_hora}}</td>
              <td style="padding:12px;">{{paciente_nombres}} {{paciente_apellidos}}</td>
              <td style="padding:12px;">{{nombre_estado}}</td>
            </tr>
            {{endfor ultimasCitas}}
          </tbody>
        </table>
      </div>
      {{endif ultimasCitas}}
      {{ifnot ultimasCitas}}
      <p style="color:#64748b;">No hay citas para mostrar.</p>
      {{endifnot ultimasCitas}}
    </section>
  </div>
</div>
