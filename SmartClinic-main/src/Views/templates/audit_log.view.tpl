<div class="container section-pad">
  <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:22px;">
    <div>
      <h2 style="font-size:2.6rem; color:#111827; margin-bottom:8px;">Bitácora de acciones</h2>
      <p style="color:#64748b;">Registro básico de acciones importantes del sistema.</p>
    </div>
    <a href="index.php?page=HomeController" class="btn btn--outline">Volver al panel</a>
  </div>

  <div class="sc-info-card" style="margin-bottom:20px;">
    <strong>Total registrado:</strong> {{totalRecords}} acciones. Se muestran las más recientes.
  </div>

  {{if records}}
  <div style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08);">
    <div class="table-responsive">
      <table style="width:100%; border-collapse:collapse; text-align:left;">
        <thead>
          <tr style="background:#033B9F; color:white;">
            <th style="padding:15px;">Fecha</th>
            <th style="padding:15px;">Usuario</th>
            <th style="padding:15px;">Módulo</th>
            <th style="padding:15px;">Acción</th>
            <th style="padding:15px;">Detalle</th>
            <th style="padding:15px;">IP</th>
          </tr>
        </thead>
        <tbody>
          {{foreach records}}
          <tr style="border-bottom:1px solid #E5E7EB;">
            <td style="padding:14px; vertical-align:middle;">{{fecha}}</td>
            <td style="padding:14px; vertical-align:middle;">{{usuario}}</td>
            <td style="padding:14px; vertical-align:middle;">{{modulo}}</td>
            <td style="padding:14px; vertical-align:middle;"><strong>{{accion}}</strong></td>
            <td style="padding:14px; vertical-align:middle;">{{detalle}}</td>
            <td style="padding:14px; vertical-align:middle;">{{ip}}</td>
          </tr>
          {{endfor records}}
        </tbody>
      </table>
    </div>
  </div>
  {{endif records}}

  {{ifnot records}}
  <div style="background:#fff; border-radius:16px; padding:2rem; text-align:center; color:#64748b;">
    <p style="font-size:1.1rem;">Aún no hay acciones registradas.</p>
  </div>
  {{endifnot records}}
</div>
