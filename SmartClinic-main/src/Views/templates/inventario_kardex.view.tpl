<div class="container section-pad">

    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; margin-bottom:16px;">
        <div>
            <h2 style="font-size:2.6rem; color:#111827; margin-bottom:8px;">Kárdex de inventario</h2>
            <p style="color:#64748b;">Historial de entradas y salidas de cada producto (ajustes manuales y compras a proveedor), con saldo acumulado.</p>
        </div>
        <a href="index.php?page=InventarioController&action=index" class="btn btn--outline">Volver a Inventario</a>
    </div>

    <!-- Filtro: viaje por GET a propósito, esta pantalla solo consulta y
         no modifica nada, así que no necesita token CSRF ni método POST.
         Al ser GET, además la URL con filtros se puede copiar/compartir. -->
    <div class="list-toolbar">
        <form method="GET" action="index.php" class="toolbar-form">
            <input type="hidden" name="page" value="InventarioController" />
            <input type="hidden" name="action" value="kardex" />
            <div class="toolbar-row">
                <div class="toolbar-field">
                    <label for="producto_id">Producto</label>
                    <select id="producto_id" name="producto_id">
                        <option value="">Todos los productos</option>
                        {{foreach productos}}
                        <option value="{{id}}" {{if selected}}selected{{endif selected}}>{{nombre}}</option>
                        {{endfor productos}}
                    </select>
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
            <div><span style="color:#64748b;">Saldo calculado desde el kárdex:</span> <strong>{{saldo_calculado}}</strong></div>
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
                        <th style="padding:15px;">Tipo</th>
                        <th style="padding:15px;">Origen</th>
                        <th style="padding:15px;">Cantidad</th>
                        <th style="padding:15px;">Referencia</th>
                        <th style="padding:15px;">Saldo acumulado</th>
                    </tr>
                </thead>
                <tbody>
                    {{foreach movimientos}}
                    <tr style="border-bottom:1px solid #E5E7EB;">
                        <td style="padding:14px; vertical-align:middle;">{{fecha}}</td>
                        <td style="padding:14px; vertical-align:middle;">{{producto_nombre}}</td>
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

</div>
