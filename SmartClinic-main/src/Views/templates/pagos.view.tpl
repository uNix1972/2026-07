<div class="container section-pad">
  <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:22px;">
    <div><h2 style="font-size:2.6rem; color:#111827;">Pagos y recibos</h2><p style="color:#64748b;">Pasarela de pago simulada para evidencia académica.</p></div>
    <a class="btn btn--outline" href="index.php?page=HomeController">Volver</a>
  </div>
  <section class="sc-panel-card">
  {{if pagos}}
  <div class="table-responsive"><table style="width:100%; border-collapse:collapse; text-align:left;"><thead><tr style="background:#033B9F; color:white;"><th style="padding:12px;">Factura</th><th style="padding:12px;">Paciente</th><th style="padding:12px;">Cita</th><th style="padding:12px;">Método</th><th style="padding:12px;">Transacción</th><th style="padding:12px;">Total</th></tr></thead><tbody>{{foreach pagos}}<tr style="border-bottom:1px solid #E5E7EB;"><td style="padding:12px;">{{id}}</td><td style="padding:12px;">{{paciente_nombres}} {{paciente_apellidos}}</td><td style="padding:12px;">{{fecha_hora}}</td><td style="padding:12px;">{{metodo_pago}}</td><td style="padding:12px;">{{id_transaccion_api}}</td><td style="padding:12px;">L {{total}}</td></tr>{{endfor pagos}}</tbody></table></div>
  {{endif pagos}}
  {{ifnot pagos}}<p style="color:#64748b;">No hay pagos registrados todavía.</p>{{endifnot pagos}}
  </section>
</div>
