<div class="container section-pad">
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:16px;margin-bottom:20px;">
        <h2 style="font-size:2.2rem;color:#111827;margin:0;">Centros de Salud</h2>
        <a class="btn btn--primary" href="index.php?page=CentrosSaludController&action=create">
            Nuevo centro
        </a>
    </div>

    <form method="GET" action="index.php" style="display:flex;gap:10px;max-width:680px;margin-bottom:20px;">
        <input type="hidden" name="page" value="CentrosSaludController">
        <input type="hidden" name="action" value="index">
        <label for="search" style="position:absolute;left:-10000px;">Buscar centros de salud</label>
        <input
            id="search"
            type="search"
            name="search"
            value="{{searchValue}}"
            placeholder="Buscar por código, nombre, tipo o ciudad"
            style="flex:1;min-width:0;padding:11px 12px;border:1px solid #C7C7CC;border-radius:8px;"
        >
        <button type="submit" class="btn btn--outline">Buscar</button>
    </form>

    <div style="background:#fff;border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;">
        <div class="table-responsive">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#033B9F;color:#fff;">
                        <th style="padding:14px;text-align:left;">#</th>
                        <th style="padding:14px;text-align:left;">Código</th>
                        <th style="padding:14px;text-align:left;">Nombre</th>
                        <th style="padding:14px;text-align:left;">Tipo</th>
                        <th style="padding:14px;text-align:left;">Ciudad</th>
                        <th style="padding:14px;text-align:left;">Teléfono</th>
                        <th style="padding:14px;text-align:left;">Estado</th>
                        <th style="padding:14px;text-align:left;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    {{foreach centros}}
                    <tr style="border-bottom:1px solid #E5E7EB;">
                        <td style="padding:13px;">{{numero_fila}}</td>
                        <td style="padding:13px;font-weight:700;">{{codigo}}</td>
                        <td style="padding:13px;">{{nombre}}</td>
                        <td style="padding:13px;">{{tipo}}</td>
                        <td style="padding:13px;">{{ciudad}}</td>
                        <td style="padding:13px;">{{telefono}}</td>
                        <td style="padding:13px;">{{estado_texto}}</td>
                        <td style="padding:13px;white-space:nowrap;">
                            <a
                                class="btn btn--outline"
                                href="index.php?page=CentrosSaludController&action=edit&id={{id}}"
                                style="padding:7px 10px;"
                            >Editar</a>

                            {{if activo}}
                            <form
                                method="POST"
                                action="index.php?page=CentrosSaludController&action=status"
                                style="display:inline;"
                                data-confirm="¿Seguro que desea desactivar este centro de salud?"
                            >
                                <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                                <input type="hidden" name="id" value="{{id}}">
                                <input type="hidden" name="estado" value="INA">
                                <button type="submit" class="btn btn--danger" style="padding:7px 10px;">Desactivar</button>
                            </form>
                            {{endif activo}}

                            {{if inactivo}}
                            <form
                                method="POST"
                                action="index.php?page=CentrosSaludController&action=status"
                                style="display:inline;"
                                data-confirm="¿Seguro que desea activar este centro de salud?"
                            >
                                <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                                <input type="hidden" name="id" value="{{id}}">
                                <input type="hidden" name="estado" value="ACT">
                                <button type="submit" class="btn btn--primary" style="padding:7px 10px;">Activar</button>
                            </form>
                            {{endif inactivo}}
                        </td>
                    </tr>
                    {{endfor centros}}
                </tbody>
            </table>
        </div>
    </div>
</div>

