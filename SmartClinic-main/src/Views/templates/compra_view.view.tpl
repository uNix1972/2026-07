<div class="container section-pad">

{{with factura}}
  <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:16px;">
    <h2 style="font-size:2.5rem; color:#111827;">Factura de compra #{{id}}</h2>
    <div style="display:flex; gap:10px;">
      <a href="index.php?page=ComprasController&action=edit&id={{id}}" class="btn btn--primary" style="background:#0260CB; color:white; border:none;">Editar</a>
      <a href="index.php?page=ComprasController&action=index" class="btn btn--outline">Volver</a>
    </div>
  </div>

  <div style="background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.08); padding:24px; margin-bottom:24px;">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
      <div><strong>N° Factura:</strong> {{numero_factura}}</div>
      <div><strong>Proveedor:</strong> {{proveedor_nombre}}</div>
      <div><strong>Centro de salud:</strong> {{centro_nombre}}</div>
      <div><strong>Fecha:</strong> {{fecha_compra}}</div>
      <div><strong>Total:</strong> {{total}}</div>
    </div>
  </div>
{{endwith factura}}

  <div style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08);">
    <div class="table-responsive">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr style="background:#033B9F; color:white;">
            <th style="padding:15px; text-align:left;">Producto</th>
            <th style="padding:15px;">Comprado por</th>
            <th style="padding:15px;">Cantidad</th>
            <th style="padding:15px;">Unidad</th>
            <th style="padding:15px;">Precio unitario</th>
            <th style="padding:15px;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          {{foreach detalle}}
          <tr style="border-bottom:1px solid #E5E7EB;">
            <td style="padding:14px; vertical-align:middle;">{{producto_nombre}}</td>
            <td style="padding:14px; vertical-align:middle;">
              {{if es_caja}}{{cantidad_cajas}} caja(s){{endif es_caja}}
              {{ifnot es_caja}}Unidad{{endifnot es_caja}}
            </td>
            <td style="padding:14px; vertical-align:middle;">{{cantidad}}</td>
            <td style="padding:14px; vertical-align:middle;">{{producto_unidad}}</td>
            <td style="padding:14px; vertical-align:middle;">{{precio_unitario}}</td>
            <td style="padding:14px; vertical-align:middle;">{{subtotal}}</td>
          </tr>
          {{endfor detalle}}
        </tbody>
      </table>
    </div>
  </div>

</div>
