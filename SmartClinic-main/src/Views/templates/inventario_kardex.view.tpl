<style>
    /* Barra de búsqueda con autocompletar (Producto / Centro de salud).
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

    /* Impresión: se oculta todo lo que no sea el título y la tabla
       (filtros, botones, paginación, menú, footer), para que "Imprimir"
       saque una hoja limpia en vez de la pantalla completa del panel. */
    .sc-print-only { display: none; }
    @media print {
        .sc-noprint,
        nav,
        header,
        footer,
        .admin-sidebar { display: none !important; }
        .sc-print-only { display: block; margin-bottom: 16px; }
        body { background: #fff; }
    }
</style>

<div class="container section-pad">

    <div class="sc-noprint" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:16px;">
        <div>
            <h2 style="font-size:2.6rem; color:#111827; margin-bottom:8px;">Kárdex de inventario</h2>
            <p style="color:#64748b;">Historial de entradas y salidas de cada producto (ajustes manuales y compras a proveedor), con saldo acumulado.</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button type="button" class="btn btn--outline" onclick="window.print()">Imprimir</button>
            <a class="btn btn--outline" href="{{~urlExportarCsv}}">Exportar CSV</a>
            <a href="index.php?page=InventarioController&action=index" class="btn btn--outline">Volver a Inventario</a>
        </div>
    </div>

    <!-- Encabezado que solo se ve al imprimir (la pantalla normal ya
         muestra el título de arriba, pero ese bloque se oculta con
         @media print para no imprimir botones). -->
    <h2 class="sc-print-only">Kárdex de inventario — SmartClinic</h2>

    <!-- Filtro: viaje por GET a propósito, esta pantalla solo consulta y
         no modifica nada, así que no necesita token CSRF ni método POST.
         Al ser GET, además la URL con filtros se puede copiar/compartir. -->
    <div class="list-toolbar sc-noprint">
        <form method="GET" action="index.php" class="toolbar-form">
            <input type="hidden" name="page" value="InventarioController" />
            <input type="hidden" name="action" value="kardex" />
            <div class="toolbar-row">
                <div class="toolbar-field sc-combo" data-sc-combo>
                    <label for="producto_search">Producto</label>
                    <input type="text" id="producto_search" class="sc-combo-input" autocomplete="off" placeholder="Buscar producto..." value="{{productoNombreSeleccionado}}" data-sc-combo-input data-options="{{~productosJsonAttr}}" />
                    <input type="hidden" name="producto_id" data-sc-combo-hidden value="{{productoIdSeleccionadoValue}}" />
                    <div class="sc-combo-results" data-sc-combo-results hidden></div>
                </div>
                <div class="toolbar-field sc-combo" data-sc-combo>
                    <label for="centro_search">Centro de salud</label>
                    <input type="text" id="centro_search" class="sc-combo-input" autocomplete="off" placeholder="Buscar centro..." value="{{centroNombreSeleccionado}}" data-sc-combo-input data-options="{{~centrosJsonAttr}}" />
                    <input type="hidden" name="centro_salud_id" data-sc-combo-hidden value="{{centroIdSeleccionadoValue}}" />
                    <div class="sc-combo-results" data-sc-combo-results hidden></div>
                </div>
                <div class="toolbar-field">
                    <label for="fecha_inicio">Desde</label>
                    <input id="fecha_inicio" type="date" name="fecha_inicio" value="{{fechaInicio}}" />
                </div>
                <div class="toolbar-field">
                    <label for="fecha_fin">Hasta</label>
                    <input id="fecha_fin" type="date" name="fecha_fin" value="{{fechaFin}}" />
                </div>
                <button type="submit" class="btn btn--primary toolbar-submit">Filtrar</button>
                <a class="btn btn--outline" href="index.php?page=InventarioController&action=kardex">Limpiar filtros</a>
            </div>
        </form>
    </div>

    <!-- Se corrigió solo un rango de fechas invertido (Desde posterior a
         Hasta), en vez de devolver una tabla vacía sin explicación. -->
    {{if fechasInvertidas}}
    <div class="sc-noprint" style="background:#EAF2FF; border:1px solid #BFDBFE; border-radius:12px; padding:14px 18px; margin-bottom:20px; color:#1D4ED8;">
        ℹ Las fechas "Desde" y "Hasta" estaban invertidas, así que se intercambiaron automáticamente para mostrar el rango correcto.
    </div>
    {{endif fechasInvertidas}}

    <!-- Panel de verificación: solo aparece cuando se filtra por UN
         producto específico. Compara el saldo que resulta de sumar todo
         el historial de movimientos (ajustes + compras) contra el
         stock_actual real guardado en la tabla producto. Si ambos números
         coinciden, es la prueba de que el kárdex no se está "comiendo"
         ningún movimiento. -->
    {{if productoSeleccionado}}
    {{with productoSeleccionado}}
    <div style="background:#fff; border-radius:16px; padding:20px; box-shadow:0 4px 20px rgba(0,0,0,.08); margin-bottom:20px;">
        <h3 style="color:#111827; margin-bottom:10px;">Verificación de saldo — {{nombre}}</h3>
        <div style="display:flex; flex-wrap:wrap; gap:24px; align-items:center;">
            <div><span style="color:#64748b;">Stock actual en sistema:</span> <strong>{{stock_actual}}</strong></div>
            <div><span style="color:#64748b;">Saldo calculado desde el kárdex (todos los centros):</span> <strong>{{saldo_calculado}}</strong></div>
            {{if &saldoCuadra}}
            <div style="color:#0F9D58; font-weight:600;">✔ Cuadra: el historial explica el 100% del stock actual.</div>
            {{endif &saldoCuadra}}
            {{ifnot &saldoCuadra}}
            <div style="color:#D63031; font-weight:600;">⚠ No cuadra: hay stock que no proviene de un ajuste ni de una compra registrada.</div>
            {{endifnot &saldoCuadra}}
        </div>
    </div>
    {{endwith productoSeleccionado}}
    {{endif productoSeleccionado}}

    <!-- Nota aclaratoria: las compras a proveedor todavía no están ligadas
         a un centro de salud específico (quedan en "Inventario general"),
         así que al filtrar por un centro dejan de aparecer en la lista. Se
         avisa esto explícitamente para que no parezca que el kárdex está
         "perdiendo" movimientos otra vez. -->
    {{if filtroPorCentroActivo}}
    <div style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:12px; padding:14px 18px; margin-bottom:20px; color:#92400E;">
        ℹ Al filtrar por un centro de salud, las compras a proveedor no aparecen en esta lista porque todavía se registran como "Inventario general" y no están ligadas a un centro específico. El saldo acumulado sí sigue reflejando el stock real del sistema completo.
    </div>
    {{endif filtroPorCentroActivo}}

    <!-- Resumen rápido del rango filtrado -->
    <div class="sc-report-grid" style="margin-bottom:20px;">
        <div class="sc-report-card"><span>Movimientos</span><strong>{{totalMovimientos}}</strong></div>
        <div class="sc-report-card"><span>Total entradas</span><strong style="color:#0F9D58;">+{{totalEntradas}}</strong></div>
        <div class="sc-report-card"><span>Total salidas</span><strong style="color:#D63031;">-{{totalSalidas}}</strong></div>
    </div>

    <div style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08);">
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
                        <th style="padding:15px;">Registrado por</th>
                        <th style="padding:15px;">Saldo acumulado</th>
                    </tr>
                </thead>
                <tbody>
                    {{foreach movimientos}}
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
                        <td style="padding:14px; vertical-align:middle;">{{usuario_nombre}}</td>
                        <td style="padding:14px; vertical-align:middle;"><strong>{{saldo_acumulado}}</strong></td>
                    </tr>
                    {{endfor movimientos}}
                </tbody>
            </table>
        </div>

        {{ifnot movimientos}}
        <p style="color:#64748b; padding:20px;">No hay movimientos para el filtro seleccionado.</p>
        {{endifnot movimientos}}
    </div>

    <!-- Paginación: 25 movimientos por página. Los enlaces "Anterior" y
         "Siguiente" van con los mismos filtros que ya estaban aplicados
         (ver InventarioController::kardex, variable $filtrosUrl), para no
         perderlos al cambiar de página. -->
    {{if movimientos}}
    <div class="sc-noprint" style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; flex-wrap:wrap; gap:12px;">
        <span style="color:#64748b;">Página {{paginaActual}} de {{totalPaginas}}</span>
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
    {{endif movimientos}}

</div>
