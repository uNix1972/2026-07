<div class="container section-pad">

    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:16px;">
        <h2 style="font-size:3rem; color:#111827;">Compras</h2>
        <div style="display:flex; gap:10px;">
            <button type="button" class="btn btn--outline" onclick="window.location.href='index.php?page=ComprasController&action=proveedores'">
                Proveedores
            </button>
            <button type="button" class="btn btn--primary"
                    style="background:#0260CB; color:white; border:none; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:600;"
                    onclick="window.location.href='index.php?page=ComprasController&action=create'">
                + Nueva compra
            </button>
        </div>
    </div>

    <form method="GET" action="index.php" style="display:flex; flex-wrap:wrap; align-items:end; gap:12px; margin-bottom:20px;">
        <input type="hidden" name="page" value="ComprasController">
        <input type="hidden" name="action" value="index">
        <div>
            <label for="centro_salud_id" style="display:block; margin-bottom:5px;">Centro de salud</label>
            <select id="centro_salud_id" name="centro_salud_id" style="min-width:260px; padding:10px 12px;">
                <option value="">Todos los centros</option>
                {{foreach centros}}
                <option value="{{id}}" {{if selected}}selected{{endif selected}}>{{nombre}}</option>
                {{endfor centros}}
            </select>
        </div>
        <button type="submit" class="btn btn--outline">Filtrar</button>
        <a href="index.php?page=ComprasController&action=index" class="btn btn--outline">Limpiar</a>
    </form>

    <div style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <div class="table-responsive">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#033B9F; color:white;">
                        <th style="padding:15px; text-align:left;">ID</th>
                        <th style="padding:15px;">N° Factura</th>
                        <th style="padding:15px;">Proveedor</th>
                        <th style="padding:15px;">Centro de salud</th>
                        <th style="padding:15px;">Fecha</th>
                        <th style="padding:15px;">Total</th>
                        <th style="padding:15px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    {{foreach facturas}}
                    <tr style="border-bottom:1px solid #E5E7EB;">
                        <td style="padding:14px; vertical-align:middle;">{{numero_fila}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{numero_factura}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{proveedor_nombre}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{centro_nombre}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{fecha_compra}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{total}}</td>
                        <td style="padding:14px; vertical-align:middle;">
                            <a href="index.php?page=ComprasController&action=view&id={{id}}"
                               style="background:#0260CB; color:white; padding:8px 12px; border-radius:8px; text-decoration:none; margin-right:5px;">
                                Ver
                            </a>
                            <a href="index.php?page=ComprasController&action=edit&id={{id}}"
                               style="background:#033B9F; color:white; padding:8px 12px; border-radius:8px; text-decoration:none;">
                                Editar
                            </a>
                        </td>
                    </tr>
                    {{endfor facturas}}
                </tbody>
            </table>
        </div>
    </div>

</div>
