<div class="container section-pad">
  <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:22px;">
    <div><h2 style="font-size:2.6rem; color:#111827;">Notificaciones</h2><p style="color:#64748b;">Alertas internas de citas, pagos y cambios de estado.</p></div>
    <a class="btn btn--outline" href="index.php?page=HomeController">Volver</a>
  </div>
  {{if msg}}<div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:14px 18px; border-radius:14px; margin-bottom:16px;">{{msg}}</div>{{endif msg}}
  <div style="display:flex; gap:8px; margin-bottom:16px;">
    <a class="btn {{ifnot verLeidas}}btn--primary{{endifnot verLeidas}}{{if verLeidas}}btn--outline{{endif verLeidas}}" style="padding:8px 14px;" href="index.php?page=NotificacionesController">Pendientes</a>
    <a class="btn {{if verLeidas}}btn--primary{{endif verLeidas}}{{ifnot verLeidas}}btn--outline{{endifnot verLeidas}}" style="padding:8px 14px;" href="index.php?page=NotificacionesController&ver=leidas">Ver leídas</a>
  </div>
  <section class="sc-panel-card">
  {{if notificaciones}}
  {{foreach notificaciones}}
  <article style="border-bottom:1px solid #E5E7EB; padding:14px 0 14px 14px; border-left:4px solid {{colorSemaforo}};">
    <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:{{colorSemaforo}}; margin-right:8px; vertical-align:middle;"></span>
    <strong>{{tipo}}</strong> · <span style="color:#64748b;">{{fecha_creacion}}</span>
    <p style="margin:8px 0;">{{mensaje}}</p>
    {{ifnot estaLeida}}
    <form method="POST" action="index.php?page=NotificacionesController&action=read" style="display:inline;">
      <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
      <input type="hidden" name="id" value="{{id}}">
      <button type="submit" class="btn btn--outline" style="padding:6px 10px;">Marcar leída</button>
    </form>
    {{endifnot estaLeida}}
  </article>
  {{endfor notificaciones}}
  {{endif notificaciones}}
  {{ifnot notificaciones}}
  {{if verLeidas}}<p style="color:#64748b;">Todavía no has marcado ninguna notificación como leída.</p>{{endif verLeidas}}
  {{ifnot verLeidas}}<p style="color:#64748b;">No hay notificaciones pendientes.</p>{{endifnot verLeidas}}
  {{endifnot notificaciones}}
  </section>
</div>
