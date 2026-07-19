<div class="container section-pad">
  <div class="form-card">
    <div style="margin-bottom:30px;">
      <h2 style="color:#033B9F; margin-bottom:10px; font-size:2.2rem;">Ajuste de inventario</h2>
      <p style="color:#636366;">Registra una entrada o salida manual de stock (mermas, conteos, correcciones).</p>
    </div>

    {{if error}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">{{error}}</div>
    {{endif error}}

    <form method="POST">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <div class="form-grid">
        <div class="form-group">
          <label for="producto_id">Producto</label>
          <select id="producto_id" name="producto_id" required>
            <option value="">Seleccione...</option>
            {{foreach productos}}
            <option value="{{id}}">{{nombre}} (stock actual: {{stock_actual}})</option>
            {{endfor productos}}
          </select>
        </div>

        <div class="form-group">
          <label for="tipo_ajuste">Tipo de ajuste</label>
          <select id="tipo_ajuste" name="tipo_ajuste" required>
            <option value="ENTRADA">Entrada (suma stock)</option>
            <option value="SALIDA">Salida (resta stock)</option>
          </select>
        </div>

        <div class="form-group">
          <label for="cantidad">Cantidad</label>
          <input id="cantidad" type="number" name="cantidad" min="1" required>
        </div>

        <div class="form-group" style="grid-column:1/3;">
          <label for="motivo">Motivo</label>
          <input id="motivo" type="text" name="motivo" required placeholder="Ej: conteo físico, producto dañado, compra sin factura...">
        </div>
      </div>

      <div class="form-actions">
        <a href="index.php?page=InventarioController&action=index" class="btn btn--outline">Cancelar</a>
        <button type="submit" class="btn btn--primary">Registrar ajuste</button>
      </div>
    </form>
  </div>
</div>
