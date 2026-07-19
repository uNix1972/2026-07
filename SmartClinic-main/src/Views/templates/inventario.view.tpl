<div class="container section-pad">

    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:16px;">
        <h2 style="font-size:3rem; color:#111827;">Inventario</h2>
        <div style="display:flex; gap:10px;">
            <button type="button" class="btn btn--outline" onclick="window.location.href='index.php?page=InventarioController&action=ajustar'">
                Ajustar stock
            </button>
            <button type="button" class="btn btn--primary"
                    style="background:#0260CB; color:white; border:none; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:600;"
                    onclick="window.location.href='index.php?page=InventarioController&action=create'">
                + Nuevo producto
            </button>
        </div>
    </div>

    <div style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08); margin-bottom:24px;">
        <div class="table-responsive">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#033B9F; color:white;">
                        <th style="padding:15px; text-align:left;">ID</th>
                        <th style="padding:15px;">Producto</th>
                        <th style="padding:15px;">Unidad</th>
                        <th style="padding:15px;">Stock actual</th>
                        <th style="padding:15px;">Stock mínimo</th>
                        <th style="padding:15px;">Precio unitario</th>
                        <th style="padding:15px;">Estado</th>
                        <th style="padding:15px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    {{foreach productos}}
                    <tr style="border-bottom:1px solid #E5E7EB;">
                        <td style="padding:14px; vertical-align:middle;">{{id}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{nombre}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{unidad_medida}}</td>
                        <td style="padding:14px; vertical-align:middle;">
                            {{stock_actual}}
                            {{if stock_bajo}}<span style="color:#D63031; font-weight:600;"> (bajo)</span>{{endif stock_bajo}}
                        </td>
                        <td style="padding:14px; vertical-align:middle;">{{stock_minimo}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{precio_unitario}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{estado}}</td>
                        <td style="padding:14px; vertical-align:middle;">
                            <a href="index.php?page=InventarioController&action=edit&id={{id}}"
                               style="background:#0260CB; color:white; padding:8px 12px; border-radius:8px; text-decoration:none; margin-right:5px;">
                                Editar
                            </a>
                            <form method="POST" action="index.php?page=InventarioController&action=delete" style="display:inline;" data-confirm="¿Seguro que desea desactivar este producto?">
                                <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                                <input type="hidden" name="id" value="{{id}}">
                                <button type="submit" style="background:#D63031;color:white;padding:8px 12px;border-radius:8px;border:none;cursor:pointer;font:inherit;">
                                    Desactivar
                                </button>
                            </form>
                        </td>
                    </tr>
                    {{endfor productos}}
                </tbody>
            </table>
        </div>
    </div>

    <div style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <div style="padding:20px 15px 0 15px;">
            <h3 style="color:#111827;">Ajustes recientes</h3>
        </div>
        <div class="table-responsive">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#033B9F; color:white;">
                        <th style="padding:15px; text-align:left;">Fecha</th>
                        <th style="padding:15px;">Producto</th>
                        <th style="padding:15px;">Tipo</th>
                        <th style="padding:15px;">Cantidad</th>
                        <th style="padding:15px;">Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    {{foreach ajustesRecientes}}
                    <tr style="border-bottom:1px solid #E5E7EB;">
                        <td style="padding:14px; vertical-align:middle;">{{fecha_ajuste}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{producto_nombre}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{tipo_ajuste}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{cantidad}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{motivo}}</td>
                    </tr>
                    {{endfor ajustesRecientes}}
                </tbody>
            </table>
        </div>
    </div>

</div>
