<div class="container section-pad health-centers-page">
    <header class="health-centers-hero">
        <div>
            <span class="health-centers-eyebrow">Administración de sedes</span>
            <h2>Centros de Salud</h2>
            <p>Administre las sedes y su información operativa desde un solo lugar.</p>
        </div>
        <a class="btn btn--primary" href="{{newUrl}}">
            <span aria-hidden="true">+</span> Nuevo centro
        </a>
    </header>

    <section class="health-centers-summary" aria-label="Resumen de centros de salud">
        <article class="health-centers-stat">
            <span class="health-centers-stat__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                    <path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4h2a2 2 0 0 1 2 2v10M8 7h4M10 5v4M8 12h2m2 0h2m-6 4h2m2 0h2M3 21h18"/>
                </svg>
            </span>
            <div>
                <span>Centros activos</span>
                <strong>{{centrosActivos}}</strong>
            </div>
        </article>
        <article class="health-centers-stat">
            <span class="health-centers-stat__icon is-blue" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2m6.5-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8.5 1v6m3-3h-6"/>
                </svg>
            </span>
            <div>
                <span>Médicos asignados</span>
                <strong>{{medicosAsignados}}</strong>
            </div>
        </article>
        <article class="health-centers-stat">
            <span class="health-centers-stat__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                    <path d="M6 2v4m12-4v4M3 9h18M5 4h14a2 2 0 0 1 2 2v15H3V6a2 2 0 0 1 2-2Zm3 9h3m2 0h3m-8 4h3m2 0h3"/>
                </svg>
            </span>
            <div>
                <span>Citas de hoy</span>
                <strong>{{citasHoy}}</strong>
            </div>
        </article>
    </section>

    {{if success}}
    <div class="form-alert success health-centers-alert" role="status">
        {{success}}
    </div>
    {{endif success}}

    {{if statusError}}
    <div class="form-alert error health-centers-alert" role="alert">
        {{statusError}}
    </div>
    {{endif statusError}}

    <div class="health-centers-workspace">
        <aside id="centro-form" class="health-center-editor">
            <div class="health-center-editor__heading">
                <div>
                    <span class="health-centers-eyebrow">
                        {{if editing}}Centro seleccionado{{endif editing}}
                        {{if creating}}Nuevo registro{{endif creating}}
                    </span>
                    <h3>{{formTitle}}</h3>
                    <p>{{formSubtitle}}</p>
                </div>
                {{if editing}}
                <a class="health-center-editor__new" href="{{newUrl}}">
                    + Nuevo
                </a>
                {{endif editing}}
            </div>

            {{if editing}}
            <div class="health-center-editor__selected">
                <span class="health-center-editor__avatar" aria-hidden="true">CS</span>
                <div>
                    <strong>{{nombre}}</strong>
                    <span>Código {{codigo}}</span>
                </div>
            </div>
            {{endif editing}}

            {{if error}}
            <div class="form-alert error health-center-editor__error" role="alert">
                {{error}}
            </div>
            {{endif error}}

            {{if creating}}
            <form method="POST" action="index.php?page=CentrosSaludController&amp;action=create">
            {{endif creating}}
            {{if editing}}
            <form method="POST" action="index.php?page=CentrosSaludController&amp;action=edit&amp;id={{id}}">
            {{endif editing}}
                <input type="hidden" name="csrf_token" value="{{csrf_token}}">
                <input type="hidden" name="return_search" value="{{searchValue}}">
                <input type="hidden" name="return_status" value="{{statusValue}}">

                <div class="health-center-form-grid">
                    <div class="form-group">
                        <label for="codigo">Código</label>
                        <input id="codigo" type="text" name="codigo" maxlength="30" value="{{codigo}}" required>
                    </div>

                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input id="nombre" type="text" name="nombre" maxlength="150" value="{{nombre}}" required>
                    </div>

                    <div class="form-group">
                        <label for="tipo">Tipo</label>
                        <select id="tipo" name="tipo" required>
                            {{foreach tipos}}
                            <option value="{{valor}}" {{if selected}}selected{{endif selected}}>{{etiqueta}}</option>
                            {{endfor tipos}}
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="ciudad">Ciudad</label>
                        <input id="ciudad" type="text" name="ciudad" maxlength="100" value="{{ciudad}}" required>
                    </div>

                    <div class="form-group health-center-form-grid__wide">
                        <label for="direccion">Dirección</label>
                        <input id="direccion" type="text" name="direccion" maxlength="255" value="{{direccion}}" required>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input id="telefono" type="tel" name="telefono" maxlength="20" value="{{telefono}}">
                    </div>

                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <input id="email" type="email" name="email" maxlength="150" value="{{email}}">
                    </div>
                </div>

                <div class="health-center-editor__actions">
                    <a class="btn btn--outline" href="{{newUrl}}">
                        {{if editing}}Cancelar edición{{endif editing}}
                        {{if creating}}Limpiar{{endif creating}}
                    </a>
                    <button type="submit" class="btn btn--primary">
                        {{submitLabel}}
                    </button>
                </div>
            </form>
        </aside>

        <section class="health-centers-list" aria-labelledby="health-centers-list-title">
            <div class="health-centers-list__heading">
                <div>
                    <span class="health-centers-eyebrow">Directorio</span>
                    <h3 id="health-centers-list-title">Centros registrados</h3>
                </div>
                <span class="health-centers-list__count">
                    {{totalResultados}} encontrados
                </span>
            </div>

            <form class="health-centers-filter" method="GET" action="index.php">
                <input type="hidden" name="page" value="CentrosSaludController">
                <input type="hidden" name="action" value="index">
                {{if editing}}
                <input type="hidden" name="edit_id" value="{{selectedId}}">
                {{endif editing}}

                <div class="health-centers-search">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-4-4"></path>
                    </svg>
                    <label class="sr-only" for="health-center-search">
                        Buscar centros de salud
                    </label>
                    <input id="health-center-search" type="search" name="search" value="{{searchValue}}" placeholder="Buscar por nombre, código o ciudad">
                </div>

                <label class="sr-only" for="health-center-status">
                    Filtrar por estado
                </label>
                <select id="health-center-status" name="status">
                    <option value="" {{if statusAll}}selected{{endif statusAll}}>Todos</option>
                    <option value="ACT" {{if statusActive}}selected{{endif statusActive}}>Activos</option>
                    <option value="INA" {{if statusInactive}}selected{{endif statusInactive}}>Inactivos</option>
                </select>

                <button type="submit" class="btn btn--outline">Filtrar</button>
            </form>

            <div class="health-centers-results">
                {{foreach centros}}
                <article class="health-center-card {{if selected}}is-selected{{endif selected}}">
                    <div class="health-center-card__identity">
                        <span class="health-center-card__icon" aria-hidden="true">CS</span>
                        <div>
                            <h4>{{nombre}}</h4>
                            <span>{{codigo}} · {{tipo_texto}}</span>
                        </div>
                    </div>

                    <div class="health-center-card__details">
                        <div>
                            <span>Ciudad</span>
                            <strong>{{ciudad}}</strong>
                        </div>
                        <div>
                            <span>Teléfono</span>
                            <strong>{{telefono_texto}}</strong>
                        </div>
                    </div>

                    <div class="health-center-card__actions">
                        <span class="health-center-status {{if activo}}is-active{{endif activo}}{{if inactivo}}is-inactive{{endif inactivo}}">
                            {{estado_texto}}
                        </span>
                        <a class="health-center-edit-link" href="{{edit_url}}">
                            Editar
                        </a>
                        {{if activo}}
                        <button type="submit" form="center-status-{{id}}" class="health-center-status-action">
                            Desactivar
                        </button>
                        {{endif activo}}
                        {{if inactivo}}
                        <button type="submit" form="center-status-{{id}}" class="health-center-status-action">
                            Activar
                        </button>
                        {{endif inactivo}}
                    </div>
                </article>
                {{endfor centros}}

                {{ifnot centros}}
                <div class="health-centers-empty">
                    <span aria-hidden="true">⌕</span>
                    <h4>No encontramos centros</h4>
                    <p>Pruebe otra búsqueda o cambie el filtro de estado.</p>
                </div>
                {{endifnot centros}}
            </div>
        </section>
    </div>

    {{foreach centros}}
    <form id="center-status-{{id}}" method="POST" action="index.php?page=CentrosSaludController&amp;action=status" data-confirm="¿Seguro que desea {{if activo}}desactivar{{endif activo}}{{if inactivo}}activar{{endif inactivo}} este centro de salud?" hidden>
        <input type="hidden" name="csrf_token" value="{{~csrf_token}}">
        <input type="hidden" name="id" value="{{id}}">
        <input type="hidden" name="estado" value="{{if activo}}INA{{endif activo}}{{if inactivo}}ACT{{endif inactivo}}">
        <input type="hidden" name="return_search" value="{{~searchValue}}">
        <input type="hidden" name="return_status" value="{{~statusValue}}">
        <input type="hidden" name="return_edit_id" value="{{~selectedId}}">
    </form>
    {{endfor centros}}
</div>
