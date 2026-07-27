<div class="container" style="max-width:900px; margin:140px auto 100px auto;">

{{with proveedor}}

<div style="background:#fff;padding:40px;border-radius:20px;box-shadow:0 10px 30px rgba(3,59,159,.12);border:1px solid #EAF5FD;">

    <div style="margin-bottom:30px;">
        <h2 style="color:#033B9F;margin-bottom:10px;font-size:2.2rem;">Editar proveedor</h2>
        <p style="color:#636366;">Actualiza los datos de contacto del proveedor.</p>
    </div>

    {{if &error}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">{{&error}}</div>
    {{endif &error}}

    <form method="POST" action="index.php?page=ComprasController&action=proveedor_edit&id={{id}}">

        <input type="hidden" name="csrf_token" value="{{&csrf_token}}">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:25px;">

            <div style="grid-column:1/3;">
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Nombre</label>
                <input type="text" name="nombre" value="{{nombre}}" required style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Contacto</label>
                <input type="text" name="contacto" value="{{contacto}}" style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Teléfono</label>
                <input type="text" name="telefono" value="{{telefono}}" style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Correo</label>
                <input type="email" name="email" value="{{email}}" style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
            </div>

            <div>
                <label style="display:block;margin-bottom:8px;font-weight:600;color:#0f172a;">Dirección</label>
                <input type="text" name="direccion" value="{{direccion}}" style="width:100%;padding:12px;border:1px solid #C7C7CC;border-radius:10px;">
            </div>

        </div>

        <div style="margin-top:30px;display:flex;justify-content:flex-end;gap:15px;">
            <a href="index.php?page=ComprasController&action=proveedores" style="padding:12px 25px;border:1px solid #C7C7CC;border-radius:10px;text-decoration:none;color:#636366;">Cancelar</a>
            <button type="submit" style="background:#033B9F;color:#fff;border:none;padding:12px 25px;border-radius:10px;font-weight:600;cursor:pointer;">Actualizar proveedor</button>
        </div>

    </form>

</div>

{{endwith proveedor}}

</div>
