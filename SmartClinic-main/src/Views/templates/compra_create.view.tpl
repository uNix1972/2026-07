<div class="container section-pad">
  <div class="form-card" style="max-width:900px; margin:0 auto;">
    <div style="margin-bottom:30px;">
      <h2 style="color:#033B9F; margin-bottom:10px; font-size:2.2rem;">Nueva factura de compra</h2>
      <p style="color:#636366;">Selecciona el proveedor y agrega los productos comprados. El stock se actualiza automáticamente al guardar.</p>
    </div>

    {{if error}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">{{error}}</div>
    {{endif error}}

    <form method="POST">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <div class="form-grid">
        <div class="form-group">
          <label for="proveedor_id">Proveedor</label>
          <select id="proveedor_id" name="proveedor_id" required>
            <option value="">Seleccione...</option>
            {{foreach proveedores}}
            <option value="{{id}}">{{nombre}}</option>
            {{endfor proveedores}}
          </select>
          <small style="display:block; margin-top:6px;">
            ¿No está en la lista? <a href="index.php?page=ComprasController&action=proveedores">Registrar nuevo proveedor</a>
          </small>
        </div>

        <div class="form-group">
          <label for="numero_factura">N° de factura</label>
          <input id="numero_factura" type="text" name="numero_factura" required placeholder="Número de la factura del proveedor">
        </div>
      </div>

      <h3 style="margin:24px 0 12px 0; color:#111827;">Productos comprados</h3>

      <div class="table-responsive">
        <table style="width:100%; border-collapse:collapse;" id="lineas-table">
          <thead>
            <tr style="background:#033B9F; color:white;">
              <th style="padding:10px; text-align:left;">Producto</th>
              <th style="padding:10px;">Cantidad</th>
              <th style="padding:10px;">Precio unitario</th>
              <th style="padding:10px;"></th>
            </tr>
          </thead>
          <tbody id="lineas-body">
            <tr class="linea-row">
              <td style="padding:10px;">
                <select name="producto_id[]" required style="width:100%;">
                  <option value="">Seleccione...</option>
                  {{foreach productos}}
                  <option value="{{id}}">{{nombre}}</option>
                  {{endfor productos}}
                </select>
              </td>
              <td style="padding:10px;"><input type="number" name="cantidad[]" min="1" required style="width:100%;"></td>
              <td style="padding:10px;"><input type="number" name="precio_unitario[]" min="0.01" step="0.01" required style="width:100%;"></td>
              <td style="padding:10px;"><button type="button" class="btn-remove-linea" style="background:#D63031;color:white;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;">✕</button></td>
            </tr>
          </tbody>
        </table>
      </div>

      <button type="button" id="btn-agregar-linea" class="btn btn--outline" style="margin-top:12px;">+ Agregar producto</button>

      <div class="form-actions">
        <a href="index.php?page=ComprasController&action=index" class="btn btn--outline">Cancelar</a>
        <button type="submit" class="btn btn--primary">Guardar factura</button>
      </div>
    </form>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var body = document.getElementById('lineas-body');

    function wireRemove(row) {
      var btn = row.querySelector('.btn-remove-linea');
      btn.addEventListener('click', function () {
        if (body.querySelectorAll('.linea-row').length > 1) {
          row.remove();
        }
      });
    }

    document.querySelectorAll('.linea-row').forEach(wireRemove);

    document.getElementById('btn-agregar-linea').addEventListener('click', function () {
      var firstRow = body.querySelector('.linea-row');
      var clone = firstRow.cloneNode(true);
      clone.querySelectorAll('input').forEach(function (i) { i.value = ''; });
      clone.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
      body.appendChild(clone);
      wireRemove(clone);
    });
  });
</script>
