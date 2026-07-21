<div class="container section-pad">

    <h2 style="font-size:3rem; color:#111827; margin-bottom:16px;">Proveedores</h2>

    {{if error}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">{{error}}</div>
    {{endif error}}

    <div class="form-card" style="margin-bottom:24px;">
        <h3 style="color:#033B9F; margin-bottom:16px;">Registrar proveedor</h3>
        <form method="POST" action="index.php?page=ComprasController&action=proveedores">
            <input type="hidden" name="csrf_token" value="{{csrf_token}}">
            <div class="form-grid">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" type="text" name="nombre" required placeholder="Nombre del proveedor">
                </div>
                <div class="form-group">
                    <label for="contacto">Contacto</label>
                    <input id="contacto" type="text" name="contacto" placeholder="Persona de contacto (opcional)">
                </div>
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input id="telefono" type="text" name="telefono" placeholder="Teléfono (opcional)">
                </div>
                <div class="form-group">
                    <label for="email">Correo</label>
                    <input id="email" type="email" name="email" placeholder="Correo (opcional)">
                </div>
                <div class="form-group" style="grid-column:1/3;">
                    <label for="direccion">Dirección</label>
                    <input id="direccion" type="text" name="direccion" placeholder="Dirección (opcional)">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Guardar proveedor</button>
            </div>
        </form>
    </div>

    <div style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <div class="table-responsive">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#033B9F; color:white;">
                        <th style="padding:15px; text-align:left;">ID</th>
                        <th style="padding:15px;">Nombre</th>
                        <th style="padding:15px;">Contacto</th>
                        <th style="padding:15px;">Teléfono</th>
                        <th style="padding:15px;">Correo</th>
                        <th style="padding:15px;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    {{foreach proveedores}}
                    <tr style="border-bottom:1px solid #E5E7EB;">
                        <td style="padding:14px; vertical-align:middle;">{{numero_fila}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{nombre}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{contacto}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{telefono}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{email}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{estado}}</td>
                    </tr>
                    {{endfor proveedores}}
                </tbody>
            </table>
        </div>
    </div>

</div>
