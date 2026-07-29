<div class="container section-pad">
  <div class="form-card purchase-form-card">
    <div style="margin-bottom:30px;">
      <h2 style="color:#033B9F; margin-bottom:10px; font-size:2.2rem;">Nueva factura de compra</h2>
      <p style="color:#636366;">Selecciona el centro de destino, el proveedor y los productos comprados.</p>
    </div>

    {{if error}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">{{error}}</div>
    {{endif error}}

    {{if sinCentros}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">No hay centros de salud activos para recibir la compra.</div>
    {{endif sinCentros}}

    <form method="POST">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <div class="form-grid">
        <div class="form-group">
          <label for="centro_salud_id">Centro de salud de destino</label>
          <select id="centro_salud_id" name="centro_salud_id" required>
            <option value="">Seleccione...</option>
            {{foreach centros}}
            <option value="{{id}}" {{if selected}}selected{{endif selected}}>{{nombre}} - {{ciudad}}</option>
            {{endfor centros}}
          </select>
        </div>
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

      </div>

      <div class="purchase-lines-heading">
        <div>
          <h3>Productos comprados</h3>
          <p>Agrega cada producto e indica cómo fue adquirido.</p>
        </div>
        <span>Detalle de la factura</span>
      </div>

      <div class="purchase-lines-editor" id="lineas-body">
        <div class="purchase-linea linea-row">
          <div class="purchase-field purchase-field--product">
            <label>Producto</label>
            <select name="producto_id[]" class="select-producto" aria-label="Producto" required>
              <option value="">Seleccione un producto...</option>
              {{foreach productos}}
              <option value="{{id}}" data-cajas="{{unidades_por_caja}}" data-unidad="{{unidad_medida}}">{{nombre}}</option>
              {{endfor productos}}
            </select>
          </div>
          <div class="purchase-field">
            <label>Comprar por</label>
            <select name="tipo_compra[]" class="select-tipo-compra" aria-label="Comprar por">
              <option value="UNI" selected>Unidad</option>
              <option value="CAJ">Caja</option>
            </select>
          </div>
          <div class="purchase-field">
            <label>Cantidad</label>
            <input type="number" name="cantidad[]" class="input-cantidad" aria-label="Cantidad" min="1" required placeholder="Ej. 10">
            <small class="hint-conversion" hidden></small>
          </div>
          <div class="purchase-field">
            <label class="precio-label">Precio por unidad (L)</label>
            <input type="number" name="precio_unitario[]" class="input-precio" aria-label="Precio" min="0.01" step="0.01" required placeholder="0.00">
          </div>
          <button type="button" class="btn-remove-linea" aria-label="Eliminar producto" title="Eliminar producto">&times;</button>
        </div>
      </div>

      <button type="button" id="btn-agregar-linea" class="btn btn--outline purchase-add-line">+ Agregar producto</button>

      <div class="form-actions">
        <a href="index.php?page=ComprasController&action=index" class="btn btn--outline">Cancelar</a>
        {{ifnot sinCentros}}<button type="submit" class="btn btn--primary">Guardar factura</button>{{endifnot sinCentros}}
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
          updateRemoveButtons();
        }
      });
    }

    function updateRemoveButtons() {
      var rows = body.querySelectorAll('.linea-row');
      rows.forEach(function (row) {
        var button = row.querySelector('.btn-remove-linea');
        button.disabled = rows.length === 1;
        button.title = rows.length === 1
          ? 'La factura debe conservar al menos un producto'
          : 'Eliminar producto';
      });
    }

    function updateHint(row) {
      var productoSelect = row.querySelector('.select-producto');
      var tipoSelect = row.querySelector('.select-tipo-compra');
      var cantidadInput = row.querySelector('.input-cantidad');
      var hint = row.querySelector('.hint-conversion');
      var precioLabel = row.querySelector('.precio-label');
      var option = productoSelect.options[productoSelect.selectedIndex];
      var cajas = option ? parseInt(option.getAttribute('data-cajas') || '1', 10) : 1;
      var unidad = option ? (option.getAttribute('data-unidad') || 'unidad') : 'unidad';

      precioLabel.textContent = tipoSelect.value === 'CAJ'
        ? 'Precio por caja (L)'
        : 'Precio por unidad (L)';

      if (tipoSelect.value === 'CAJ' && cajas > 1) {
        var cantidad = parseInt(cantidadInput.value || '0', 10);
        hint.hidden = false;
        hint.textContent = '= ' + (cantidad * cajas) + ' ' + unidad + ' (' + cajas + ' por caja)';
      } else {
        hint.hidden = true;
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
      updateHint(row);
    });
    updateRemoveButtons();

    document.getElementById('btn-agregar-linea').addEventListener('click', function () {
      var firstRow = body.querySelector('.linea-row');
      var clone = firstRow.cloneNode(true);
      clone.querySelectorAll('input').forEach(function (i) { i.value = ''; });
      clone.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
      clone.querySelector('.hint-conversion').hidden = true;
      body.appendChild(clone);
      wireRemove(clone);
      wireConversion(clone);
      updateHint(clone);
      updateRemoveButtons();
    });
  });
</script>
