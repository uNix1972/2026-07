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

    {{if notificationSent}}
    <div style="background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46;padding:12px 16px;margin-bottom:16px;">
        La notificación fue enviada al paciente.
    </div>
    {{endif notificationSent}}

    {{if notificationFailed}}
    <div style="background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;padding:12px 16px;margin-bottom:16px;">
        No se pudo enviar la notificación. Verifique el teléfono y la configuración de WhatsApp.
    </div>
    {{endif notificationFailed}}

    {{if notificationUnavailable}}
    <div style="background:#FFF7ED;border:1px solid #FED7AA;color:#9A3412;padding:12px 16px;margin-bottom:16px;">
        Esta cita no está disponible para notificación.
    </div>
    {{endif notificationUnavailable}}

    <div class="list-toolbar">
        <div class="toolbar-form">
            <div class="toolbar-row">
                <div class="toolbar-field">
                    <label for="citas_search_filter">Buscar</label>
                    <input type="text" id="citas_search_filter" autocomplete="off" placeholder="Buscar por paciente, médico, centro, consultorio o fecha" />
                </div>
                <div class="toolbar-field">
                    <label for="citas_estado_filter">Estado</label>
                    <select id="citas_estado_filter">
                        {{foreach estadoOptions}}
                        <option value="{{value}}">{{label}}</option>
                        {{endfor estadoOptions}}
                    </select>
                </div>
            </div>
        </div>
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
                    <th style="padding:15px;">Centro / Consultorio</th>
                    <th style="padding:15px;">Fecha y Hora</th>
                    <th style="padding:15px;">Estado</th>
                    <th style="padding:15px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                {{foreach citas}}
                <tr data-cita-row data-cita-estado="{{nombre_estado}}" data-cita-search="{{paciente_nombres}} {{paciente_apellidos}} {{medico_nombres}} {{medico_apellidos}} {{nombre_especialidad}} {{centro_nombre}} {{consultorio}} {{fecha_hora}}" style="border-bottom:1px solid #E5E7EB;">
                    <td style="padding:14px; vertical-align:middle;">{{id}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{paciente_nombres}} {{paciente_apellidos}}<br><small>{{paciente_telefono_texto}}</small></td>
                    <td style="padding:14px; vertical-align:middle;">{{medico_nombres}} {{medico_apellidos}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{nombre_especialidad}}</td>
                    <td style="padding:14px;vertical-align:middle;min-width:190px;">{{centro_nombre}}<br><small>Consultorio {{consultorio}}</small></td>
                    <td style="padding:14px; vertical-align:middle;">{{fecha_hora}}</td>
                    <td style="padding:14px; vertical-align:middle;">
                        <span style="background:#EFF6FF; color:#0b4bb8; padding:4px 12px; border-radius:999px; font-size:.85rem; font-weight:700;">
                            {{nombre_estado}}
                        </span>
                    </td>
                    <td style="padding:14px; vertical-align:middle;">
                        {{if ~showCrudActions}}
                        <div style="display:flex; justify-content:flex-end; flex-wrap:nowrap; gap:6px;">
                            <a href="index.php?page=CitasController&action=edit&id={{id}}" class="btn btn--outline" style="padding:6px 12px; font-size:12px;">Editar</a>
                            {{if canNotify}}
                            <form method="POST" action="index.php?page=CitasController&action=notify" data-confirm="¿Desea enviar ahora la notificación de esta cita al paciente?">
                                <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                                <input type="hidden" name="id" value="{{id}}">
                                <button type="submit" class="btn btn--outline" style="padding:6px 12px; font-size:12px;">Notificar</button>
                            </form>
                            {{endif canNotify}}
                            {{if cannotNotify}}
                            <button type="button" disabled title="Solo disponible para citas futuras activas con teléfono registrado" class="btn btn--outline" style="padding:6px 12px; font-size:12px; opacity:.5; cursor:not-allowed;">Notificar</button>
                            {{endif cannotNotify}}
                            <form method="POST" action="index.php?page=CitasController&action=delete" data-confirm="¿Seguro que desea cancelar esta cita? Esta acción no se puede deshacer.">
                                <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                                <input type="hidden" name="id" value="{{id}}">
                                <button type="submit" class="btn btn--outline" style="padding:6px 12px; font-size:12px;">Cancelar</button>
                            </form>
                        </div>
                        {{endif ~showCrudActions}}

                        {{ifnot ~showCrudActions}}
                        <span style="color:#475569; font-size:.95rem;">Solo lectura - sin permisos de edición</span>
                        {{endifnot ~showCrudActions}}
                    </td>
                </tr>
                {{endfor citas}}
                <tr data-citas-search-empty style="display:none;">
                    <td colspan="8" style="padding:20px; text-align:center; color:#64748b;">No se encontraron citas con esa búsqueda.</td>
                </tr>
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

<script>
(function () {
  var searchInput = document.getElementById("citas_search_filter");
  var estadoSelect = document.getElementById("citas_estado_filter");
  var filas = document.querySelectorAll("[data-cita-row]");
  var vacio = document.querySelector("[data-citas-search-empty]");
  if ((!searchInput && !estadoSelect) || !filas.length) {
    return;
  }

  function normalizar(texto) {
    return (texto || "")
      .toString()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .trim();
  }

  function aplicarFiltro() {
    var query = normalizar(searchInput ? searchInput.value : "");
    var estado = normalizar(estadoSelect ? estadoSelect.value : "");
    var visibles = 0;

    filas.forEach(function (fila) {
      var texto = normalizar(fila.getAttribute("data-cita-search"));
      var estadoFila = normalizar(fila.getAttribute("data-cita-estado"));
      var coincideTexto = query === "" || texto.indexOf(query) !== -1;
      var coincideEstado = estado === "" || estadoFila === estado;
      var coincide = coincideTexto && coincideEstado;
      fila.style.display = coincide ? "" : "none";
      if (coincide) {
        visibles += 1;
      }
    });

    if (vacio) {
      vacio.style.display = visibles === 0 ? "" : "none";
    }
  }

  searchInput && searchInput.addEventListener("input", aplicarFiltro);
  estadoSelect && estadoSelect.addEventListener("change", aplicarFiltro);
})();
</script>
