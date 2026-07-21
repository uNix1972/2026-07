<div class="container section-pad">
  <div class="form-card">
    <div style="margin-bottom:30px;">
      <h2 style="color:#033B9F; margin-bottom:10px; font-size:2.2rem;">Nuevo producto</h2>
      <p style="color:#636366;">Agrega un producto al catálogo de inventario.</p>
    </div>

    {{if error}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">{{error}}</div>
    {{endif error}}

    <form method="POST">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <div class="form-grid">
        <div class="form-group">
          <label for="nombre">Nombre</label>
          <input id="nombre" type="text" name="nombre" required placeholder="Nombre del producto">
        </div>

        <div class="form-group">
          <label for="unidad_medida">Unidad de medida</label>
          <select id="unidad_medida" name="unidad_medida" required>
            {{foreach unidades}}
            <option value="{{valor}}" {{if selected}}selected{{endif selected}}>{{valor}}</option>
            {{endfor unidades}}
          </select>
        </div>

        <div class="form-group">
          <label for="unidades_por_caja">Unidades por caja</label>
          <input id="unidades_por_caja" type="number" name="unidades_por_caja" min="1" value="1" required>
        </div>

        <div class="form-group" style="grid-column:1/3;">
          <label for="descripcion">Descripción</label>
          <input id="descripcion" type="text" name="descripcion" placeholder="Descripción (opcional)">
        </div>

        <div class="form-group">
          <label for="stock_minimo">Stock mínimo</label>
          <input id="stock_minimo" type="number" name="stock_minimo" min="0" value="0" required>
        </div>

        <div class="form-group">
          <label for="precio_unitario">Precio unitario</label>
          <input id="precio_unitario" type="number" name="precio_unitario" min="0" step="0.01" value="0.00" required>
        </div>
      </div>

      <div class="form-actions">
        <a href="index.php?page=InventarioController&action=index" class="btn btn--outline">Cancelar</a>
        <button type="submit" class="btn btn--primary">Guardar producto</button>
      </div>
    </form>
  </div>
</div>
