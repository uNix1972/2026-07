<style>
    /* Barra de búsqueda con autocompletar (mismo componente que el Kárdex).
       Ver public/js/kardex-autocomplete.js para el comportamiento. */
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

<div class="container section-pad">

    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:16px;">
        <h2 style="font-size:3rem; color:#111827;">Inventario</h2>
        <div style="display:flex; gap:10px;">
            <button type="button" class="btn btn--outline" onclick="window.location.href='index.php?page=InventarioController&action=kardex'">
                Ver kárdex
            </button>
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

    {{if errorEliminarProducto}}
    <div style="background:#FEF2F2; border:1px solid #FCA5A5; border-radius:12px; padding:14px 18px; margin-bottom:20px; color:#991B1B;">
        No se pudo eliminar "<strong>{{errorEliminarProducto}}</strong>": tiene compras registradas y no se puede borrar sin perder esa historia. Usa "Desactivar" en su lugar.
    </div>
    {{endif errorEliminarProducto}}

    <div id="buscar-producto" style="background:#fff; border-radius:16px; padding:16px 20px; box-shadow:0 4px 20px rgba(0,0,0,.08); margin-bottom:24px;">
        <form method="GET" action="index.php#buscar-producto" style="display:flex; flex-wrap:wrap; align-items:end; gap:14px;">
            <input type="hidden" name="page" value="InventarioController">
            <input type="hidden" name="action" value="index">
            <div class="sc-combo" data-sc-combo style="min-width:280px; flex:1 1 280px;">
                <label for="producto_buscador" style="display:block; font-size:.85rem; color:#334155; margin-bottom:4px;">Buscar producto</label>
                <input type="text" id="producto_buscador" name="q" class="sc-combo-input" autocomplete="off" placeholder="Escribe el nombre del producto..." value="{{productoBuscadoNombre}}" data-sc-combo-input data-options="{{~productosJsonAttr}}">
                <input type="hidden" name="producto_id" data-sc-combo-hidden value="{{productoBuscadoIdValue}}">
                <div class="sc-combo-results" data-sc-combo-results hidden></div>
            </div>
            <div>
                <label style="display:block; font-size:.85rem; margin-bottom:4px; visibility:hidden;">Acción</label>
                <button type="submit" class="btn btn--primary" style="background:#0260CB; color:white; border:none; padding:10px 16px; border-radius:8px; cursor:pointer; font-weight:600;">Buscar</button>
            </div>
            {{if hayBusquedaProducto}}
            <div>
                <label style="display:block; font-size:.85rem; margin-bottom:4px; visibility:hidden;">Acción</label>
                <a href="index.php?page=InventarioController&action=index#buscar-producto" style="display:inline-block; padding:10px 4px; font-weight:600;">Quitar búsqueda</a>
            </div>
            {{endif hayBusquedaProducto}}
        </form>
        {{if hayBusquedaProducto}}
        {{ifnot productos}}
        <p style="margin-top:12px; padding:10px 14px; background:#FEF2F2; border:1px solid #FCA5A5; border-radius:8px; color:#991B1B;">
            No se encontró ningún producto que coincida con "<strong>{{productoBuscadoNombre}}</strong>".
        </p>
        {{endifnot productos}}
        {{endif hayBusquedaProducto}}
    </div>

    <div id="inventario-historico" style="background:#fff; border-radius:16px; padding:16px 20px; box-shadow:0 4px 20px rgba(0,0,0,.08); margin-bottom:24px;">
        <form method="GET" action="index.php#inventario-historico" style="display:flex; flex-wrap:wrap; align-items:end; gap:14px;">
            <input type="hidden" name="page" value="InventarioController">
            <input type="hidden" name="action" value="index">
            <div>
                <label style="display:block; font-size:.85rem; color:#334155; margin-bottom:4px;">Ver inventario en una fecha específica</label>
                <input type="date" name="fecha_corte" value="{{fechaCorte}}" style="padding:10px 12px; font-size:1rem; border:1px solid #CBD5E1; border-radius:8px; min-width:170px;">
            </div>
            <div>
                <label style="display:block; font-size:.85rem; margin-bottom:4px; visibility:hidden;">Acción</label>
                <button type="submit" class="btn btn--outline" style="padding:10px 16px;">Consultar</button>
            </div>
            {{if modoHistorico}}
            <div>
                <label style="display:block; font-size:.85rem; margin-bottom:4px; visibility:hidden;">Acción</label>
                <a href="index.php?page=InventarioController&action=index#inventario-historico" style="display:inline-block; padding:10px 4px; font-weight:600;">Volver a inventario actual</a>
            </div>
            {{endif modoHistorico}}
        </form>
        {{if modoHistorico}}
        <p style="margin-top:12px; padding:10px 14px; background:#FFF7E0; border:1px solid #F5C542; border-radius:8px; color:#7A5B00;">
            Mostrando el inventario reconstruido a partir de los movimientos registrados hasta el <strong>{{fechaCorte}}</strong> (no es el stock actual).
        </p>
        {{endif modoHistorico}}
    </div>

    <div id="tabla-productos" style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08); margin-bottom:24px;">
        <div class="table-responsive">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#033B9F; color:white;">
                        <th style="padding:15px; text-align:left;">ID</th>
                        <th style="padding:15px;">Producto</th>
                        <th style="padding:15px;">Unidad</th>
                        <th style="padding:15px;">{{if modoHistorico}}Stock al {{fechaCorte}}{{endif modoHistorico}}{{ifnot modoHistorico}}Stock actual{{endifnot modoHistorico}}</th>
                        <th style="padding:15px;">Stock mínimo</th>
                        <th style="padding:15px;">Precio unitario</th>
                        <th style="padding:15px;">Estado</th>
                        <th style="padding:15px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    {{foreach productos}}
                    <tr style="border-bottom:1px solid #E5E7EB;">
                        <td style="padding:14px; vertical-align:middle;">{{numero_fila}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{nombre}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{unidad_medida}}</td>
                        <td style="padding:14px; vertical-align:middle;">
                            {{if noExistiaAun}}<span style="color:#94A3B8; font-style:italic;">No existía aún</span>{{endif noExistiaAun}}
                            {{ifnot noExistiaAun}}
                            {{stockMostrado}}
                            {{if stock_bajo}}<span style="color:#D63031; font-weight:600;"> (bajo)</span>{{endif stock_bajo}}
                            {{endifnot noExistiaAun}}
                        </td>
                        <td style="padding:14px; vertical-align:middle;">{{stock_minimo}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{precio_unitario}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{estado}}</td>
                        <td style="padding:14px; vertical-align:middle;">
                            <a href="index.php?page=InventarioController&action=edit&id={{id}}"
                               style="background:#0260CB; color:white; padding:8px 12px; border-radius:8px; text-decoration:none; margin-right:5px;">
                                Editar
                            </a>
                            <form method="POST" action="index.php?page=InventarioController&action=delete" style="display:inline; margin-right:5px;" data-confirm="¿Seguro que desea desactivar este producto? (esto no borra su historial, solo lo oculta de los combos de compras/ajustes)">
                                <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                                <input type="hidden" name="id" value="{{id}}">
                                <button type="submit" style="background:#D63031;color:white;padding:8px 12px;border-radius:8px;border:none;cursor:pointer;font:inherit;">
                                    Desactivar
                                </button>
                            </form>
                            <form method="POST" action="index.php?page=InventarioController&action=eliminar" style="display:inline;" data-confirm="¿ELIMINAR PERMANENTEMENTE este producto? Esta acción no se puede deshacer y borra también su historial de ajustes manuales. Si tiene compras registradas, no se podrá eliminar (usa Desactivar en ese caso).">
                                <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
                                <input type="hidden" name="id" value="{{id}}">
                                <button type="submit" style="background:#7F1D1D;color:white;padding:8px 12px;border-radius:8px;border:none;cursor:pointer;font:inherit;">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    {{endfor productos}}
                </tbody>
            </table>
        </div>
        {{if productos}}
        <div class="sc-noprint" style="display:flex; justify-content:space-between; align-items:center; padding:16px; flex-wrap:wrap; gap:12px;">
            <span style="color:#64748b;">Página {{paginaProductos}} de {{totalPaginasProductos}}</span>
            <div style="display:flex; gap:10px;">
                {{if urlPaginaAnteriorProductos}}
                <a class="btn btn--outline" href="{{urlPaginaAnteriorProductos}}">&larr; Anterior</a>
                {{endif urlPaginaAnteriorProductos}}
                {{ifnot urlPaginaAnteriorProductos}}
                <span class="btn btn--outline" style="opacity:.5; pointer-events:none;">&larr; Anterior</span>
                {{endifnot urlPaginaAnteriorProductos}}
                {{if urlPaginaSiguienteProductos}}
                <a class="btn btn--outline" href="{{urlPaginaSiguienteProductos}}">Siguiente &rarr;</a>
                {{endif urlPaginaSiguienteProductos}}
                {{ifnot urlPaginaSiguienteProductos}}
                <span class="btn btn--outline" style="opacity:.5; pointer-events:none;">Siguiente &rarr;</span>
                {{endifnot urlPaginaSiguienteProductos}}
            </div>
        </div>
        {{endif productos}}
    </div>

    <div id="movimientos-recientes" style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <div style="padding:20px 15px 0 15px; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:12px;">
            <h3 style="color:#111827;">Movimientos recientes</h3>
            <a href="index.php?page=InventarioController&action=kardex" style="font-weight:600;">Ver kárdex completo</a>
        </div>
        <form method="GET" action="index.php#movimientos-recientes" style="display:flex; flex-wrap:wrap; align-items:end; gap:14px; padding:12px 15px 16px 15px;">
            <input type="hidden" name="page" value="InventarioController">
            <input type="hidden" name="action" value="index">
            <div class="sc-combo" data-sc-combo style="min-width:240px; flex:1 1 240px;">
                <label for="mov_producto_buscador" style="display:block; font-size:.85rem; color:#334155; margin-bottom:4px;">Producto</label>
                <input type="text" id="mov_producto_buscador" name="mov_q" class="sc-combo-input" autocomplete="off" placeholder="Buscar producto..." value="{{movProductoBuscadoNombre}}" data-sc-combo-input data-options="{{~productosJsonAttrMov}}">
                <input type="hidden" name="mov_producto_id" data-sc-combo-hidden value="{{movProductoBuscadoIdValue}}">
                <div class="sc-combo-results" data-sc-combo-results hidden></div>
            </div>
            <div>
                <label style="display:block; font-size:.85rem; color:#334155; margin-bottom:4px;">Desde</label>
                <input type="date" name="mov_fecha_inicio" value="{{movFechaInicio}}" style="padding:10px 12px; font-size:1rem; border:1px solid #CBD5E1; border-radius:8px; min-width:170px;">
            </div>
            <div>
                <label style="display:block; font-size:.85rem; color:#334155; margin-bottom:4px;">Hasta</label>
                <input type="date" name="mov_fecha_fin" value="{{movFechaFin}}" style="padding:10px 12px; font-size:1rem; border:1px solid #CBD5E1; border-radius:8px; min-width:170px;">
            </div>
            <div>
                <label style="display:block; font-size:.85rem; margin-bottom:4px; visibility:hidden;">Acción</label>
                <button type="submit" class="btn btn--outline" style="padding:10px 16px;">Filtrar</button>
            </div>
            <div>
                <label style="display:block; font-size:.85rem; margin-bottom:4px; visibility:hidden;">Acción</label>
                <a href="index.php?page=InventarioController&action=index#movimientos-recientes" style="display:inline-block; padding:10px 4px; font-weight:600;">Quitar filtro</a>
            </div>
        </form>
        {{if movBusquedaSinResultados}}
        <p style="margin:0 15px 12px 15px; padding:10px 14px; background:#FEF2F2; border:1px solid #FCA5A5; border-radius:8px; color:#991B1B;">
            No se encontró ningún producto que coincida con "<strong>{{movProductoBuscadoNombre}}</strong>".
        </p>
        {{endif movBusquedaSinResultados}}
        <div class="table-responsive">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#033B9F; color:white;">
                        <th style="padding:15px; text-align:left;">Fecha</th>
                        <th style="padding:15px;">Producto</th>
                        <th style="padding:15px;">Centro de salud</th>
                        <th style="padding:15px;">Tipo</th>
                        <th style="padding:15px;">Origen</th>
                        <th style="padding:15px;">Cantidad</th>
                        <th style="padding:15px;">Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    {{foreach movimientosRecientes}}
                    <tr style="border-bottom:1px solid #E5E7EB;">
                        <td style="padding:14px; vertical-align:middle;">{{fecha}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{producto_nombre}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{centro_nombre}}</td>
                        <td style="padding:14px; vertical-align:middle;">
                            {{if es_salida}}<span style="color:#D63031; font-weight:600;">Salida</span>{{endif es_salida}}
                            {{ifnot es_salida}}<span style="color:#0F9D58; font-weight:600;">Entrada</span>{{endifnot es_salida}}
                        </td>
                        <td style="padding:14px; vertical-align:middle;">
                            {{if es_compra}}<span style="background:#E0ECFF; color:#033B9F; padding:3px 10px; border-radius:999px; font-size:.85rem;">Compra</span>{{endif es_compra}}
                            {{ifnot es_compra}}<span style="background:#F1F5F9; color:#334155; padding:3px 10px; border-radius:999px; font-size:.85rem;">Ajuste manual</span>{{endifnot es_compra}}
                        </td>
                        <td style="padding:14px; vertical-align:middle;">{{cantidad}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{referencia}}</td>
                    </tr>
                    {{endfor movimientosRecientes}}
                </tbody>
            </table>
        </div>

        {{ifnot movimientosRecientes}}
        {{ifnot movBusquedaSinResultados}}
        <p style="color:#64748b; padding:20px;">Todavía no hay movimientos de inventario registrados.</p>
        {{endifnot movBusquedaSinResultados}}
        {{endifnot movimientosRecientes}}

        {{if movimientosRecientes}}
        <div class="sc-noprint" style="display:flex; justify-content:space-between; align-items:center; padding:16px; flex-wrap:wrap; gap:12px;">
            <span style="color:#64748b;">Página {{paginaMovimientos}} de {{totalPaginasMovimientos}}</span>
            <div style="display:flex; gap:10px;">
                {{if urlPaginaAnteriorMovimientos}}
                <a class="btn btn--outline" href="{{urlPaginaAnteriorMovimientos}}">&larr; Anterior</a>
                {{endif urlPaginaAnteriorMovimientos}}
                {{ifnot urlPaginaAnteriorMovimientos}}
                <span class="btn btn--outline" style="opacity:.5; pointer-events:none;">&larr; Anterior</span>
                {{endifnot urlPaginaAnteriorMovimientos}}
                {{if urlPaginaSiguienteMovimientos}}
                <a class="btn btn--outline" href="{{urlPaginaSiguienteMovimientos}}">Siguiente &rarr;</a>
                {{endif urlPaginaSiguienteMovimientos}}
                {{ifnot urlPaginaSiguienteMovimientos}}
                <span class="btn btn--outline" style="opacity:.5; pointer-events:none;">Siguiente &rarr;</span>
                {{endifnot urlPaginaSiguienteMovimientos}}
            </div>
        </div>
        {{endif movimientosRecientes}}
    </div>

</div>
