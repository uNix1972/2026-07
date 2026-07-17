<div class="container section-pad">

    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:16px;">
        <h2 style="font-size:3rem; color:#111827;">Citas</h2>

        {{if showCrudActions}}
        <button type="button" class="btn btn--primary"
                style="background:#0260CB; color:white; border:none; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:600;"
                onclick="window.location.href='index.php?page=CitasController&action=agendar'">
            + Nueva cita
        </button>
        {{endif showCrudActions}}
    </div>

    <div class="list-toolbar">
        <form method="GET" action="index.php" class="toolbar-form">
            <input type="hidden" name="page" value="CitasController" />
            <input type="hidden" name="action" value="index" />
            <div class="toolbar-row">
                <div class="toolbar-field">
                    <label>Buscar</label>
                    <input type="search" name="search" value="{{searchValue}}" placeholder="Buscar por paciente, médico, especialidad o fecha" />
                </div>
                <div class="toolbar-field">
                    <label>Estado</label>
                    <select name="estado">
                        {{foreach estadoOptions}}
                        <option value="{{value}}" {{if selected}}selected{{endif selected}}>{{label}}</option>
                        {{endfor estadoOptions}}
                    </select>
                </div>
                <button type="submit" class="btn btn--primary toolbar-submit">Buscar</button>
            </div>
        </form>
    </div>

    {{if citas}}
    <div style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <div class="table-responsive">
            <table style="width:100%; border-collapse:collapse; text-align:left;">
                <thead>
                <tr style="background:#033B9F; color:white;">
                    <th style="padding:15px; text-align:left; vertical-align:middle;">ID</th>
                    <th style="padding:15px;">Paciente</th>
                    <th style="padding:15px;">Médico</th>
                    <th style="padding:15px;">Especialidad</th>
                    <th style="padding:15px;">Fecha y Hora</th>
                    <th style="padding:15px;">Estado</th>
                    <th style="padding:15px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                {{foreach citas}}
                <tr style="border-bottom:1px solid #E5E7EB;">
                    <td style="padding:14px; vertical-align:middle;">{{id}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{paciente_nombres}} {{paciente_apellidos}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{medico_nombres}} {{medico_apellidos}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{nombre_especialidad}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{fecha_hora}}</td>
                    <td style="padding:14px; vertical-align:middle;">
                        <span style="background:#EFF6FF; color:#0b4bb8; padding:4px 12px; border-radius:999px; font-size:.85rem; font-weight:700;">
                            {{nombre_estado}}
                        </span>
                    </td>
                    <td style="padding:14px; vertical-align:middle;">
                        {{if ~showCrudActions}}
                        <a href="index.php?page=CitasController&action=edit&id={{id}}" style="background:#0260CB; color:white; padding:8px 12px; border-radius:8px; text-decoration:none; margin-right:5px;">
                            Editar
                        </a>
                        <form method="POST" action="index.php?page=CitasController&action=delete" style="display:inline;" data-confirm="¿Seguro que desea cancelar esta cita? Esta acción no se puede deshacer.">
                            <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                            <input type="hidden" name="id" value="{{id}}">
                            <button type="submit" style="background:#D63031;color:white;padding:8px 12px;border-radius:8px;border:none;cursor:pointer;font:inherit;">
                                Cancelar
                            </button>
                        </form>
                        {{endif ~showCrudActions}}

                        {{ifnot ~showCrudActions}}
                        <span style="color:#475569; font-size:.95rem;">Solo lectura - sin permisos de edición</span>
                        {{endifnot ~showCrudActions}}
                    </td>
                </tr>
                {{endfor citas}}
            </tbody>
        </table>
        </div>
    </div>
    {{endif citas}}

    {{ifnot citas}}
    <div style="background:#fff; border-radius:16px; padding:2rem; text-align:center; color:#64748b;">
        <p style="font-size:1.1rem;">No hay citas agendadas.</p>
    </div>
    {{endifnot citas}}

</div>
