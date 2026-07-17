<div class="container section-pad">

    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:16px;">
        <h2 style="font-size:3rem; color:#111827;">Pacientes</h2>

        {{if showCrudActions}}
        <button type="button" class="btn btn--primary"
                style="background:#0260CB; color:white; border:none; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:600;"
                onclick="window.location.href='index.php?page=PacientesController&action=create'">
            + Nuevo paciente
        </button>
        {{endif showCrudActions}}
    </div>

    <div class="list-toolbar">
        <form method="GET" action="index.php" class="toolbar-form">
            <input type="hidden" name="page" value="PacientesController" />
            <input type="hidden" name="action" value="index" />
            <div class="toolbar-row">
                <div class="toolbar-field">
                    <label>Buscar</label>
                    <input type="search" name="search" value="{{searchValue}}" placeholder="Buscar paciente, identidad o teléfono" />
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
                    <th style="padding:15px;">Identidad</th>
                    <th style="padding:15px;">Nombres</th>
                    <th style="padding:15px;">Apellidos</th>
                    <th style="padding:15px;">Fecha nacimiento</th>
                    <th style="padding:15px;">Teléfono</th>
                    <th style="padding:15px;">Dirección</th>
                    <th style="padding:15px;">Acciones</th>
                </tr>
            </thead>

            <tbody>

                {{foreach pacientes}}

                <tr style="border-bottom:1px solid #E5E7EB;">
                    <td style="padding:14px; vertical-align:middle;">{{id}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{identidad}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{nombres}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{apellidos}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{fecha_nacimiento}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{telefono}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{direccion}}</td>

                    <td style="padding:14px; vertical-align:middle;">

                        {{if ~showCrudActions}}
                        <a href="index.php?page=PacientesController&action=edit&id={{id}}"
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

                        <form method="POST" action="index.php?page=PacientesController&action=delete" style="display:inline;" data-confirm="¿Seguro que desea eliminar este paciente? Esta acción puede afectar su historial de citas.">
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

                {{endfor pacientes}}

            </tbody>

        </table>
        </div>

    </div>

</div>