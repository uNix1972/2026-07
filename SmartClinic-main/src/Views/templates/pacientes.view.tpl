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

    {{if msg}}
    <div role="status" style="margin-bottom:16px;padding:14px 16px;border:1px solid #A7F3D0;border-radius:10px;background:#ECFDF5;color:#065F46;">
        {{msg}}
    </div>
    {{endif msg}}

    <div class="list-toolbar">
        <div class="toolbar-form">
            <div class="toolbar-row">
                <div class="toolbar-field">
                    <label for="pacientes_search_filter">Buscar</label>
                    <input type="text" id="pacientes_search_filter" autocomplete="off" placeholder="Buscar paciente, identidad o teléfono" />
                </div>
            </div>
        </div>
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

                <tr data-paciente-row data-paciente-search="{{identidad}} {{nombres}} {{apellidos}} {{telefono}} {{direccion}}" style="border-bottom:1px solid #E5E7EB;">
                    <td style="padding:14px; vertical-align:middle;">{{id}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{identidad}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{nombres}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{apellidos}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{fecha_nacimiento}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{telefono}}</td>
                    <td style="padding:14px; vertical-align:middle;">{{direccion}}</td>

                    <td style="padding:14px; vertical-align:middle;">

                        {{if ~showCrudActions}}
                        <div style="display:flex; justify-content:flex-end; flex-wrap:nowrap; gap:6px;">
                            <a href="index.php?page=PacientesController&action=edit&id={{id}}" class="btn btn--outline" style="padding:6px 12px; font-size:12px;">Editar</a>

                            <form method="POST" action="index.php?page=PacientesController&action=delete" data-confirm="¿Seguro que desea eliminar este paciente? Esta acción puede afectar su historial de citas.">
                                <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                                <input type="hidden" name="id" value="{{id}}">
                                <button type="submit" class="btn btn--outline" style="padding:6px 12px; font-size:12px;">Eliminar</button>
                            </form>
                        </div>
                        {{endif ~showCrudActions}}

                        {{ifnot ~showCrudActions}}
                        <span style="color:#475569; font-size:.95rem;">Solo lectura - sin permisos de edición</span>
                        {{endifnot ~showCrudActions}}

                    </td>
                </tr>

                {{endfor pacientes}}

                <tr data-pacientes-search-empty style="display:none;">
                    <td colspan="8" style="padding:20px; text-align:center; color:#64748b;">No se encontraron pacientes con esa búsqueda.</td>
                </tr>

            </tbody>

        </table>
        </div>

    </div>

</div>

<script>
(function () {
  var searchInput = document.getElementById("pacientes_search_filter");
  var filas = document.querySelectorAll("[data-paciente-row]");
  var vacio = document.querySelector("[data-pacientes-search-empty]");
  if (!searchInput || !filas.length) {
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

  searchInput.addEventListener("input", function () {
    var query = normalizar(searchInput.value);
    var visibles = 0;

    filas.forEach(function (fila) {
      var texto = normalizar(fila.getAttribute("data-paciente-search"));
      var coincide = query === "" || texto.indexOf(query) !== -1;
      fila.style.display = coincide ? "" : "none";
      if (coincide) {
        visibles += 1;
      }
    });

    if (vacio) {
      vacio.style.display = visibles === 0 ? "" : "none";
    }
  });
})();
</script>