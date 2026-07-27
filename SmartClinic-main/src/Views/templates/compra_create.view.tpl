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
          <label for="numero_factura">N° Factura</label>
          <input type="text" id="numero_factura" name="numero_factura" maxlength="50" required placeholder="Ej: FC-0001">
        </div>

      </div>

      <h3 style="margin:24px 0 12px 0; color:#111827;">Productos comprados</h3>

      <div class="table-responsive">
        <table style="width:100%; border-collapse:collapse;" id="lineas-table">
          <thead>
            <tr style="background:#033B9F; color:white;">
              <th style="padding:10px; text-align:left;">Producto</th>
              <th style="padding:10px;">Comprar por</th>
              <th style="padding:10px;">Cantidad</th>
              <th style="padding:10px;">Precio</th>
              <th style="padding:10px;"></th>
            </tr>
          </thead>
          <tbody id="lineas-body">
            <tr class="linea-row">
              <td style="padding:10px;">
                <select name="producto_id[]" class="select-producto" required style="width:100%;">
                  <option value="">Seleccione...</option>
                  {{foreach productos}}
                  <option value="{{id}}" data-cajas="{{unidades_por_caja}}" data-unidad="{{unidad_medida}}">{{nombre}}</option>
                  {{endfor productos}}
                </select>
              </td>
              <td style="padding:10px;">
                <select name="tipo_compra[]" class="select-tipo-compra" style="width:100%;">
                  <option value="UNI" selected>Unidad</option>
                  <option value="CAJ">Caja</option>
                </select>
              </td>
              <td style="padding:10px;">
                <input type="number" name="cantidad[]" class="input-cantidad" min="1" required style="width:100%;">
                <small class="hint-conversion" style="display:none; color:#636366;"></small>
              </td>
              <td style="padding:10px;"><input type="number" name="precio_unitario[]" class="input-precio" min="0.01" step="0.01" required style="width:100%;"></td>
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

    function updateHint(row) {
      var productoSelect = row.querySelector('.select-producto');
      var tipoSelect = row.querySelector('.select-tipo-compra');
      var cantidadInput = row.querySelector('.input-cantidad');
      var hint = row.querySelector('.hint-conversion');
      var option = productoSelect.options[productoSelect.selectedIndex];
      var cajas = option ? parseInt(option.getAttribute('data-cajas') || '1', 10) : 1;
      var unidad = option ? (option.getAttribute('data-unidad') || 'unidad') : 'unidad';

      if (tipoSelect.value === 'CAJ' && cajas > 1) {
        var cantidad = parseInt(cantidadInput.value || '0', 10);
        hint.style.display = 'block';
        hint.textContent = '= ' + (cantidad * cajas) + ' ' + unidad + ' (' + cajas + ' por caja)';
      } else {
        hint.style.display = 'none';
        hint.textContent = '';
      }
    }

    function wireConversion(row) {
      row.querySelector('.select-producto').addEventListener('change', function () { updateHint(row); });
      row.querySelector('.select-tipo-compra').addEventListener('change', function () { updateHint(row); });
      row.querySelector('.input-cantidad').addEventListener('input', function () { updateHint(row); });
    }

    document.querySelectorAll('.linea-row').forEach(function (row) {
      wireRemove(row);
      wireConversion(row);
    });

    document.getElementById('btn-agregar-linea').addEventListener('click', function () {
      var firstRow = body.querySelector('.linea-row');
      var clone = firstRow.cloneNode(true);
      clone.querySelectorAll('input').forEach(function (i) { i.value = ''; });
      clone.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
      clone.querySelector('.hint-conversion').style.display = 'none';
      body.appendChild(clone);
      wireRemove(clone);
      wireConversion(clone);
    });
  });
</script>
