<div class="container" style="max-width:900px; margin:140px auto 100px auto;">

{{with producto}}

<div style="background:#fff;padding:40px;border-radius:20px;box-shadow:0 10px 30px rgba(3,59,159,.12);border:1px solid #EAF5FD;">

    <div style="margin-bottom:30px;">
        <h2 style="color:#033B9F;margin-bottom:10px;font-size:2.2rem;">Editar producto</h2>
        <p style="color:#636366;">Actualiza los datos del producto. El stock actual solo se modifica desde un ajuste de inventario.</p>
    </div>

    {{if &error}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">{{&error}}</div>
    {{endif &error}}

    <form method="POST" action="index.php?page=InventarioController&action=edit&id={{id}}">

        <input type="hidden" name="csrf_token" value="{{&csrf_token}}">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:25px;">

            <div>
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Nombre</label>
                <input type="text" name="nombre" value="{{nombre}}" required style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Unidad de medida</label>
                <input type="text" name="unidad_medida" value="{{unidad_medida}}" required style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
            </div>

            <div style="grid-column:1/3;">
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Descripción</label>
                <input type="text" name="descripcion" value="{{descripcion}}" style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Stock mínimo</label>
                <input type="number" name="stock_minimo" min="0" value="{{stock_minimo}}" required style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Precio unitario</label>
                <input type="number" name="precio_unitario" min="0" step="0.01" value="{{precio_unitario}}" required style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Stock actual</label>
                <input type="text" value="{{stock_actual}}" disabled style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;background:#F3F4F6;color:#6B7280;">
            </div>

        </div>

        <div style="margin-top:30px;display:flex;justify-content:flex-end;gap:15px;">
            <a href="index.php?page=InventarioController&action=index" style="padding:12px 25px;border:1px solid #C7C7CC;border-radius:10px;text-decoration:none;color:#636366;">Cancelar</a>
            <button type="submit" style="background:#033B9F;color:#fff;border:none;padding:12px 25px;border-radius:10px;font-weight:600;cursor:pointer;">Actualizar producto</button>
        </div>

    </form>

</div>

{{endwith producto}}

</div>
