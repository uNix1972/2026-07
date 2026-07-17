<div class="container section-pad">
  <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:22px;">
    <div><h2 style="font-size:2.6rem; color:#111827;">Notificaciones</h2><p style="color:#64748b;">Alertas internas de citas, pagos y cambios de estado.</p></div>
    <a class="btn btn--outline" href="index.php?page=HomeController">Volver</a>
  </div>
  <section class="sc-panel-card">
  {{if notificaciones}}
  {{foreach notificaciones}}
  <article style="border-bottom:1px solid #E5E7EB; padding:14px 0;">
    <strong>{{tipo}}</strong> · <span style="color:#64748b;">{{fecha_creacion}}</span>
    <p style="margin:8px 0;">{{mensaje}}</p>
    <a href="index.php?page=NotificacionesController&action=read&id={{id}}" class="btn btn--outline" style="padding:6px 10px;">Marcar leída</a>
  </article>
  {{endfor notificaciones}}
  {{endif notificaciones}}
  {{ifnot notificaciones}}<p style="color:#64748b;">No hay notificaciones.</p>{{endifnot notificaciones}}
  </section>
</div>
