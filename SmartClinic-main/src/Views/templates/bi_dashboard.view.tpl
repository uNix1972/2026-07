<div class="container section-pad">
  <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:22px;">
    <div><h2 style="font-size:2.6rem; color:#111827;">Inteligencia de negocio</h2><p style="color:#64748b;">Indicadores visuales para apoyo administrativo.</p></div>
    <a class="btn btn--outline" href="index.php?page=ReportesController">Reportes</a>
  </div>
  <div class="sc-two-columns">
    <section class="sc-panel-card"><h3>Citas por estado</h3>{{foreach citasPorEstado}}<div style="margin:10px 0;"><strong>{{estado}}</strong><div style="height:12px; background:#E5E7EB; border-radius:999px;"><span style="display:block; height:12px; width:calc({{total}} * 10% + 8px); max-width:100%; background:#0260CB; border-radius:999px;"></span></div><small>{{total}} citas</small></div>{{endfor citasPorEstado}}</section>
    <section class="sc-panel-card"><h3>Carga por médico</h3>{{foreach cargaMedicos}}<p style="display:flex; justify-content:space-between; border-bottom:1px solid #E5E7EB; padding:8px 0;"><span>{{medico}}</span><strong>{{total_citas}}</strong></p>{{endfor cargaMedicos}}</section>
  </div>
  <div class="sc-two-columns" style="margin-top:22px;">
    <section class="sc-panel-card"><h3>Citas por mes</h3>{{if citasPorMes}}{{foreach citasPorMes}}<p style="display:flex; justify-content:space-between; border-bottom:1px solid #E5E7EB; padding:8px 0;"><span>{{mes}}</span><strong>{{total}}</strong></p>{{endfor citasPorMes}}{{endif citasPorMes}}{{ifnot citasPorMes}}<p style="color:#64748b;">Sin datos.</p>{{endifnot citasPorMes}}</section>
    <section class="sc-panel-card"><h3>Ingresos por mes</h3>{{if ingresos}}{{foreach ingresos}}<p style="display:flex; justify-content:space-between; border-bottom:1px solid #E5E7EB; padding:8px 0;"><span>{{mes}}</span><strong>L {{total}}</strong></p>{{endfor ingresos}}{{endif ingresos}}{{ifnot ingresos}}<p style="color:#64748b;">Sin pagos registrados.</p>{{endifnot ingresos}}</section>
  </div>
</div>
