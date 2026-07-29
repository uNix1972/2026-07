<div class="container section-pad">

    <h2 style="font-size:3rem; color:#111827; margin-bottom:16px;">Proveedores</h2>

    {{if error}}
    <div class="form-alert error" style="display:block; margin-bottom:16px;">{{error}}</div>
    {{endif error}}

    {{if errorEliminarProveedor}}
    <div style="background:#FEF2F2; border:1px solid #FCA5A5; border-radius:12px; padding:14px 18px; margin-bottom:20px; color:#991B1B;">
        No se pudo eliminar "<strong>{{errorEliminarProveedor}}</strong>": tiene compras registradas y no se puede borrar sin perder esa historia. Usa "Desactivar" en su lugar.
    </div>
    {{endif errorEliminarProveedor}}

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
                        <th style="padding:15px;">Acciones</th>
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
                        <td style="padding:14px; vertical-align:middle;">
                            <div style="display:flex; justify-content:flex-end; flex-wrap:nowrap; gap:6px;">
                                <a href="index.php?page=ComprasController&action=proveedor_edit&id={{id}}" class="btn btn--outline" style="padding:6px 12px; font-size:12px;">Editar</a>
                                {{if esActivo}}
                                <form method="POST" action="index.php?page=ComprasController&action=proveedor_desactivar" data-confirm="¿Seguro que desea desactivar este proveedor? (esto no borra su historial, solo lo oculta del combo de compras)">
                                    <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                                    <input type="hidden" name="id" value="{{id}}">
                                    <button type="submit" class="btn btn--outline" style="padding:6px 12px; font-size:12px;">Desactivar</button>
                                </form>
                                {{endif esActivo}}
                                {{ifnot esActivo}}
                                <form method="POST" action="index.php?page=ComprasController&action=proveedor_activar">
                                    <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                                    <input type="hidden" name="id" value="{{id}}">
                                    <button type="submit" class="btn btn--outline" style="padding:6px 12px; font-size:12px;">Activar</button>
                                </form>
                                {{endifnot esActivo}}
                                <form method="POST" action="index.php?page=ComprasController&action=proveedor_eliminar" data-confirm="¿ELIMINAR PERMANENTEMENTE este proveedor? Esta acción no se puede deshacer. Si tiene compras registradas, no se podrá eliminar (usa Desactivar en ese caso).">
                                    <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                                    <input type="hidden" name="id" value="{{id}}">
                                    <button type="submit" class="btn btn--outline" style="padding:6px 12px; font-size:12px;">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    {{endfor proveedores}}
                </tbody>
            </table>
        </div>
    </div>

</div>
