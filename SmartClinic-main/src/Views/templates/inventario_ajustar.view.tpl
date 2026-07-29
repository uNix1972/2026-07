<div class="container section-pad">
  <div class="form-card">
    <div style="margin-bottom:30px;">
      <h2 style="color:#033B9F; margin-bottom:10px; font-size:2.2rem;">Ajuste de inventario</h2>
      <p style="color:#636366;">Registra una entrada o salida manual de stock (mermas, conteos, correcciones).</p>
    </div>

    {{if error}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">{{error}}</div>
    {{endif error}}

    {{if sinCentros}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">
      No hay centros de salud activos. Registre o active un centro antes de ajustar inventario.
    </div>
    {{endif sinCentros}}

    {{if sinProductos}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">
      No hay productos activos para ajustar.
    </div>
    {{endif sinProductos}}

    <form method="POST">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <div class="form-grid">
        <div class="form-group">
          <label for="centro_salud_id">Centro de salud</label>
          <select id="centro_salud_id" name="centro_salud_id" required>
            <option value="">Seleccione...</option>
            {{foreach centros}}
            <option value="{{id}}" {{if selected}}selected{{endif selected}}>{{nombre}} - {{ciudad}}</option>
            {{endfor centros}}
          </select>
        </div>

        <div class="form-group">
          <label for="producto_id">Producto</label>
          <select id="producto_id" name="producto_id" required>
            <option value="">Seleccione...</option>
            {{foreach productos}}
            <option value="{{id}}" {{if selected}}selected{{endif selected}}>{{nombre}} (disponible: {{stock_disponible}})</option>
            {{endfor productos}}
          </select>
        </div>

        <div class="form-group">
          <label for="tipo_ajuste">Tipo de ajuste</label>
          <select id="tipo_ajuste" name="tipo_ajuste" required>
            <option value="ENTRADA" {{if tipoEntrada}}selected{{endif tipoEntrada}}>Entrada (suma stock)</option>
            <option value="SALIDA" {{if tipoSalida}}selected{{endif tipoSalida}}>Salida (resta stock)</option>
          </select>
        </div>

        <div class="form-group">
          <label for="cantidad">Cantidad</label>
          <input id="cantidad" type="number" name="cantidad" min="1" value="{{cantidad}}" required>
        </div>

        <div class="form-group" style="grid-column:1/-1;">
          <label for="motivo">Motivo</label>
          <input id="motivo" type="text" name="motivo" value="{{motivo}}" required placeholder="Ej: conteo físico, producto dañado, compra sin factura...">
        </div>
      </div>

      <div class="form-actions">
        <a href="index.php?page=InventarioController&action=index&centro_salud_id={{centroSaludId}}" class="btn btn--outline">Cancelar</a>
        {{if puedeGuardar}}
        <button type="submit" class="btn btn--primary">Registrar ajuste</button>
        {{endif puedeGuardar}}
      </div>
    </form>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var centro = document.getElementById('centro_salud_id');
  if (!centro) return;
  centro.addEventListener('change', function () {
    if (!centro.value) return;
    window.location.href = 'index.php?page=InventarioController&action=ajustar&centro_salud_id='
      + encodeURIComponent(centro.value);
  });
});
</script>
