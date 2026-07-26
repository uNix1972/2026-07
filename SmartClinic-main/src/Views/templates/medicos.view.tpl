<div class="container section-pad">

    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:16px;">
        <h2 style="font-size:3rem; color:#111827;">Médicos</h2>

        {{if showCrudActions}}
        <button type="button" class="btn btn--primary"
                style="background:#0260CB; color:white; border:none; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:600;"
                onclick="window.location.href='index.php?page=MedicosController&action=create'">
            + Nuevo médico
        </button>
        {{endif showCrudActions}}
    </div>

    <div class="list-toolbar">
        <form method="GET" action="index.php" class="toolbar-form">
            <input type="hidden" name="page" value="MedicosController" />
            <input type="hidden" name="action" value="index" />
            <div class="toolbar-row">
                <div class="toolbar-field">
                    <label>Buscar</label>
                    <input type="search" name="search" value="{{searchValue}}" placeholder="Buscar médico, especialidad, colegiatura o centro" />
                </div>
                <div class="toolbar-field">
                    <label>Especialidad</label>
                    <select name="especialidad">
                        {{foreach especialidades}}
                        <option value="{{nombre_especialidad}}" {{if selected}}selected{{endif selected}}>{{nombre_especialidad}}</option>
                        {{endfor especialidades}}
                    </select>
                </div>
                <button type="submit" class="btn btn--primary toolbar-submit">Buscar</button>
            </div>
        </form>
    </div>

    <div style="
        background:#fff;
        border-radius:16px;
        overflow:hidden;
        box-shadow:0 4px 20px rgba(0,0,0,.08);
    ">

        <div class="table-responsive">
            <table style="width:100%; border-collapse:collapse;">

            <thead>
                <tr style="background:#033B9F; color:white;">
                    <th style="padding:15px; text-align:left; vertical-align:middle;">ID</th>
                    <th style="padding:15px;">Especialidad</th>
                    <th style="padding:15px;">Nombres</th>
                    <th style="padding:15px;">Apellidos</th>
                    <th style="padding:15px;">N° Colegiatura</th>
                    <th style="padding:15px;">Teléfono</th>
                    <th style="padding:15px;">Centros / Consultorios</th>
                    <th style="padding:15px;">Acciones</th>
                </tr>
            </thead>

            <tbody>

                {{foreach medicos}}

                <tr style="border-bottom:1px solid #E5E7EB;">
                    <td style="padding:14px; vertical-align:middle;">{{id}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{nombre_especialidad}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{nombres}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{apellidos}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{num_colegiatura}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{telefono}}</td>
                    <td style="padding:14px;vertical-align:middle;min-width:220px;">{{centros_salud_texto}}</td>

                    <td style="padding:14px; vertical-align:middle;">

                        {{if ~showCrudActions}}
                        <a href="index.php?page=MedicosController&action=edit&id={{id}}"
                           style="
                                background:#0260CB;
                                color:white;
                                padding:8px 12px;
                                border-radius:8px;
                                text-decoration:none;
                                margin-right:5px;
                           ">
                            Editar
                        </a>

                        <form method="POST" action="index.php?page=MedicosController&action=delete" style="display:inline;" data-confirm="¿Seguro que desea eliminar este médico? Revise primero si tiene citas asociadas.">
                            <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                            <input type="hidden" name="id" value="{{id}}">
                            <button type="submit" style="background:#D63031;color:white;padding:8px 12px;border-radius:8px;border:none;cursor:pointer;font:inherit;">
                                Eliminar
                            </button>
                        </form>
                        {{endif ~showCrudActions}}

                        {{ifnot ~showCrudActions}}
                        <span style="color:#475569; font-size:.95rem;">Solo lectura - sin permisos de edición</span>
                        {{endifnot ~showCrudActions}}

                    </td>
                </tr>

                {{endfor medicos}}

            </tbody>

        </table>
        </div>

    </div>

</div>
