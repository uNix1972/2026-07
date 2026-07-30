<style>
    /* Barra de búsqueda con autocompletar, mismo patrón sc-combo usado en
       Kárdex/Inventario/Médicos. Ver public/js/kardex-autocomplete.js. */
    .sc-combo { position: relative; }
    .sc-combo-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #C7C7CC;
        border-radius: 8px;
        font: inherit;
        box-sizing: border-box;
    }
    .sc-combo-results {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
        max-height: 220px;
        overflow-y: auto;
        z-index: 20;
    }
    .sc-combo-option {
        padding: 10px 14px;
        cursor: pointer;
        font-size: .95rem;
        color: #111827;
    }
    .sc-combo-option:hover,
    .sc-combo-option.is-active {
        background: #EAF5FD;
    }
    .sc-combo-empty {
        padding: 10px 14px;
        color: #64748b;
        font-size: .9rem;
    }
</style>

<div class="container section-pad enfermeras-page">

    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:16px;">
        <h2 style="font-size:3rem; color:#111827;">Enfermeras</h2>

        {{if showCrudActions}}
        <button type="button" class="btn btn--primary"
                style="background:#0260CB; color:white; border:none; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:600;"
                onclick="window.location.href='index.php?page=EnfermerasController&action=create'">
            + Nueva enfermera
        </button>
        {{endif showCrudActions}}
    </div>

    {{if errorEliminarEnfermera}}
    <div style="background:#FEF2F2; border:1px solid #FCA5A5; border-radius:12px; padding:14px 18px; margin-bottom:20px; color:#991B1B;">
        No se pudo eliminar "<strong>{{errorEliminarEnfermera}}</strong>": tiene centros de salud asignados y no se puede borrar sin perder ese historial. Usa "Desactivar" en su lugar.
    </div>
    {{endif errorEliminarEnfermera}}

    <div class="list-toolbar">
        <form method="GET" action="index.php" class="toolbar-form">
            <input type="hidden" name="page" value="EnfermerasController" />
            <input type="hidden" name="action" value="index" />
            <div class="toolbar-row">
                <div class="toolbar-field sc-combo" data-sc-combo data-sc-combo-submit-on-enter style="min-width:320px; flex:1 1 320px;">
                    <label for="enfermera_search">Buscar</label>
                    <input type="text" id="enfermera_search" name="search" class="sc-combo-input" autocomplete="off" placeholder="Buscar por nombres, apellidos o N° de colegiatura..." value="{{searchValue}}" data-sc-combo-input data-options="{{~enfermerasJsonAttr}}" />
                    <input type="hidden" name="enfermera_id" data-sc-combo-hidden value="{{enfermeraBuscadaIdValue}}" />
                    <div class="sc-combo-results" data-sc-combo-results hidden></div>
                </div>
                <button type="submit" class="btn btn--primary toolbar-submit">Buscar</button>
                {{if hayBusqueda}}
                <a class="btn btn--outline" href="index.php?page=EnfermerasController&action=index">Quitar búsqueda</a>
                {{endif hayBusqueda}}
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
                    <th style="padding:14px 10px; text-align:left; vertical-align:middle;">Nombres</th>
                    <th style="padding:14px 10px;">Apellidos</th>
                    <th style="padding:14px 10px; white-space:nowrap;">N° Colegiatura</th>
                    <th style="padding:14px 10px;">Teléfono</th>
                    <th style="padding:14px 10px;">Centros / Áreas</th>
                    <th style="padding:14px 10px; white-space:nowrap;">Estado</th>
                    <th style="padding:14px 10px; white-space:nowrap;">Acciones</th>
                </tr>
            </thead>

            <tbody>

                {{foreach enfermeras}}

                <tr style="border-bottom:1px solid #E5E7EB;">
                    <td style="padding:12px 10px; vertical-align:middle;">{{nombres}}</td>
                    <td style="padding:12px 10px; vertical-align:middle;">{{apellidos}}</td>
                    <td style="padding:12px 10px; vertical-align:middle; white-space:nowrap;">{{num_colegiatura}}</td>
                    <td style="padding:12px 10px; vertical-align:middle; white-space:nowrap;">{{telefono}}</td>
                    <td style="padding:12px 10px; vertical-align:middle;">
                        <button type="button" class="btn btn--outline" style="padding:5px 10px; font-size:11.5px; white-space:nowrap;" onclick="document.getElementById('centros-modal-{{id}}').showModal()">Centros de salud</button>

                        <dialog id="centros-modal-{{id}}" class="centros-modal">
                            <div class="centros-modal__header">
                                <span class="centros-modal__icon">&#9877;</span>
                                <div>
                                    <h3>Centros de salud</h3>
                                    <p>{{nombres}} {{apellidos}}</p>
                                </div>
                                <button type="button" class="centros-modal__close" onclick="document.getElementById('centros-modal-{{id}}').close()" aria-label="Cerrar">&times;</button>
                            </div>
                            <div class="centros-modal__body">
                                {{if tieneCentros}}
                                <div class="centros-modal__list">
                                    {{foreach centros_lista}}
                                    <div class="centros-modal__item">
                                        <div class="centros-modal__item-name">{{centro_nombre}}</div>
                                        <div class="centros-modal__item-sub">Área/turno: {{area}}</div>
                                    </div>
                                    {{endfor centros_lista}}
                                </div>
                                {{endif tieneCentros}}
                                {{ifnot tieneCentros}}<span class="centros-modal__empty">Sin centro asignado</span>{{endifnot tieneCentros}}
                            </div>
                        </dialog>
                    </td>

                    <td style="padding:12px 10px; vertical-align:middle; white-space:nowrap;">{{estado}}</td>

                    <td style="padding:12px 10px; vertical-align:middle;">

                        {{if ~showCrudActions}}
                        <div style="display:flex; justify-content:flex-end; flex-wrap:nowrap; gap:4px;">
                            <a href="index.php?page=EnfermerasController&action=edit&id={{id}}" class="btn btn--outline" style="padding:5px 9px; font-size:11.5px; white-space:nowrap;">Editar</a>

                            {{if esActivo}}
                            <form method="POST" action="index.php?page=EnfermerasController&action=desactivar" data-confirm="¿Seguro que desea desactivar esta enfermera? No se le asignarán más centros, pero su información e historial se conservan.">
                                <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                                <input type="hidden" name="id" value="{{id}}">
                                <button type="submit" class="btn btn--outline" style="padding:5px 9px; font-size:11.5px; white-space:nowrap;">Desactivar</button>
                            </form>
                            {{endif esActivo}}
                            {{ifnot esActivo}}
                            <form method="POST" action="index.php?page=EnfermerasController&action=activar">
                                <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                                <input type="hidden" name="id" value="{{id}}">
                                <button type="submit" class="btn btn--outline" style="padding:5px 9px; font-size:11.5px; white-space:nowrap;">Activar</button>
                            </form>
                            {{endifnot esActivo}}

                            {{if puedeEliminar}}
                            <form method="POST" action="index.php?page=EnfermerasController&action=eliminar" data-confirm="¿ELIMINAR PERMANENTEMENTE esta enfermera? Esta acción no se puede deshacer. Solo se permite porque nunca tuvo ningún centro asignado.">
                                <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                                <input type="hidden" name="id" value="{{id}}">
                                <button type="submit" class="btn btn--outline" style="padding:5px 9px; font-size:11.5px; white-space:nowrap;">Eliminar</button>
                            </form>
                            {{endif puedeEliminar}}
                        </div>
                        {{endif ~showCrudActions}}

                        {{ifnot ~showCrudActions}}
                        <span style="color:#475569; font-size:.95rem;">Solo lectura - sin permisos de edición</span>
                        {{endifnot ~showCrudActions}}

                    </td>
                </tr>

                {{endfor enfermeras}}

            </tbody>

        </table>
        </div>

        {{if enfermeras}}
        <div style="display:flex; justify-content:space-between; align-items:center; padding:16px; flex-wrap:wrap; gap:12px; border-top:1px solid #E5E7EB;">
            <span style="color:#64748b;">Página {{paginaActual}} de {{totalPaginas}} ({{totalEnfermeras}} enfermeras)</span>
            <div style="display:flex; gap:10px;">
                {{if urlPaginaAnterior}}
                <a class="btn btn--outline" href="{{urlPaginaAnterior}}">&larr; Anterior</a>
                {{endif urlPaginaAnterior}}
                {{ifnot urlPaginaAnterior}}
                <span class="btn btn--outline" style="opacity:.5; pointer-events:none;">&larr; Anterior</span>
                {{endifnot urlPaginaAnterior}}
                {{if urlPaginaSiguiente}}
                <a class="btn btn--outline" href="{{urlPaginaSiguiente}}">Siguiente &rarr;</a>
                {{endif urlPaginaSiguiente}}
                {{ifnot urlPaginaSiguiente}}
                <span class="btn btn--outline" style="opacity:.5; pointer-events:none;">Siguiente &rarr;</span>
                {{endifnot urlPaginaSiguiente}}
            </div>
        </div>
        {{endif enfermeras}}

    </div>

</div>

<style>
  /* Popup "Centros de salud" del listado de Enfermeras: mismo componente
     visual que el de Médicos (glass + paleta del sistema). */
  .centros-modal {
    position: fixed;
    inset: 0;
    margin: auto;
    border: 1px solid rgba(255, 255, 255, .5);
    border-radius: var(--sc-radius-xl, 24px);
    padding: 0;
    width: 92%;
    max-width: 460px;
    max-height: 82vh;
    overflow: hidden;
    background: rgba(255, 255, 255, .72);
    backdrop-filter: blur(22px) saturate(160%);
    -webkit-backdrop-filter: blur(22px) saturate(160%);
    box-shadow: var(--sc-shadow-lg, 0 8px 32px rgba(3, 59, 159, .16)), 0 0 0 1px rgba(3, 59, 159, .06);
    opacity: 0;
    transform: translateY(14px) scale(.94);
    transition: opacity .28s ease, transform .28s ease,
                overlay .28s ease allow-discrete, display .28s ease allow-discrete;
  }
  .centros-modal[open] {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  @starting-style {
    .centros-modal[open] { opacity: 0; transform: translateY(14px) scale(.94); }
  }
  .centros-modal::backdrop {
    background: linear-gradient(135deg, rgba(3, 59, 159, .55), rgba(1, 141, 236, .35));
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    opacity: 0;
    transition: opacity .28s ease, overlay .28s ease allow-discrete, display .28s ease allow-discrete;
  }
  .centros-modal[open]::backdrop { opacity: 1; }
  @starting-style {
    .centros-modal[open]::backdrop { opacity: 0; }
  }
  .centros-modal__header {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 22px 52px 18px 22px;
    background: linear-gradient(135deg, var(--sc-blue-900, #033B9F) 0%, var(--sc-blue-700, #0260CB) 100%);
    color: var(--sc-white, #FFFEFE);
  }
  .centros-modal__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .18);
    font-size: 1.2rem;
    flex-shrink: 0;
  }
  .centros-modal__header h3 {
    margin: 0 0 2px;
    color: var(--sc-white, #fff);
    font-size: 1.15rem;
  }
  .centros-modal__header p {
    margin: 0;
    color: var(--sc-blue-200, #99DEFC);
    font-size: .88rem;
  }
  .centros-modal__close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 30px;
    height: 30px;
    border: none;
    border-radius: 50%;
    background: rgba(255, 255, 255, .18);
    color: var(--sc-white, #fff);
    font-size: 1.2rem;
    line-height: 1;
    cursor: pointer;
    transition: background var(--sc-transition, .22s ease);
  }
  .centros-modal__close:hover {
    background: rgba(255, 255, 255, .32);
  }
  .centros-modal__body {
    padding: 20px 22px 24px;
    max-height: calc(82vh - 96px);
    overflow-y: auto;
  }
  .centros-modal__list {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .centros-modal__item {
    background: rgba(234, 245, 253, .7);
    border: 1px solid rgba(1, 141, 236, .18);
    border-radius: var(--sc-radius-md, 10px);
    padding: 10px 14px;
  }
  .centros-modal__item-name {
    font-weight: 600;
    color: var(--sc-blue-900, #033B9F);
    font-size: .92rem;
    line-height: 1.3;
  }
  .centros-modal__item-sub {
    color: var(--sc-gray-500, #636366);
    font-size: .8rem;
    margin-top: 2px;
  }
  .centros-modal__empty {
    color: var(--sc-gray-500, #636366);
  }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.centros-modal').forEach(function (dialogEl) {
    dialogEl.addEventListener('click', function (event) {
      if (event.target === dialogEl) {
        dialogEl.close();
      }
    });
  });
});
</script>
