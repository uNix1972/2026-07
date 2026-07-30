<style>
  .form-card { background: #fff; border-radius: 8px; box-shadow: 0 4px 20px rgba(6,54,112,0.08); padding: 2rem; max-width: 720px; margin: 0 auto; }
  .form-card h2 { color: var(--cedro); font-size: 1.6rem; font-weight: 800; margin-bottom: 1.5rem; }
  .form-field { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1.2rem; }
  .form-field label { color: var(--cedro); font-weight: 700; font-size: 0.9rem; }
  .form-input, .form-select { border: 1px solid #ddd; border-radius: 10px; padding: 0.7rem 0.9rem; font-size: 0.95rem; background: #fff; outline: none; width: 100%; font-family: inherit; transition: border-color 0.2s; box-sizing: border-box; }
  .form-input:focus, .form-select:focus { border-color: var(--dorado); box-shadow: 0 0 0 3px rgba(197,160,89,0.15); }
  .form-input[readonly], .form-select:disabled { background: #f5f5f5; color: #999; cursor: not-allowed; border-color: #e0e0e0; }
  .error { color: #c0392b; font-size: 0.85rem; background: #fdecea; padding: 0.4rem 0.7rem; border-radius: 8px; }
  .self-note { color: #7a6033; font-size: 0.82rem; background: #fdf6e3; border: 1px solid #e8d5a0; padding: 0.35rem 0.7rem; border-radius: 8px; }
  .self-access-enabled { color:#145b58; font-size:.84rem; background:#ecfbf8; border-left:3px solid #0b7a75; padding:.65rem .8rem; margin:-.7rem 0 1.2rem; }
  .roles-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; }
  .role-option { min-width:0; border:1px solid #d7e2f0; border-radius:8px; background:#f8fbff; overflow:hidden; }
  .role-option:has(.role-checkbox:checked) { border-color:#0b5fc6; background:#eef6ff; box-shadow:0 0 0 2px rgba(11,95,198,.1); }
  .role-option-select { display:grid; grid-template-columns:20px minmax(0,1fr); gap:.7rem; align-items:start; padding:.85rem; cursor:pointer; }
  .role-option-select:has(input:disabled) { cursor:not-allowed; opacity:.72; }
  .role-checkbox { width:18px; height:18px; margin:.1rem 0 0; accent-color:#0b5fc6; }
  .role-option-select strong { display:block; color:#082b5c; font-size:.92rem; }
  .role-option-select small { display:block; color:#5f6f83; font-size:.8rem; line-height:1.35; margin-top:.2rem; }
  .role-access { border-top:1px solid #d7e2f0; background:#fff; }
  .role-access summary { display:flex; align-items:center; justify-content:space-between; gap:.5rem; padding:.62rem .85rem; color:#0b4fb3; font-size:.8rem; font-weight:750; cursor:pointer; list-style:none; }
  .role-access summary::-webkit-details-marker { display:none; }
  .role-access summary::after { content:"+"; font-size:1rem; line-height:1; }
  .role-access[open] summary::after { content:"−"; }
  .role-access-count { display:inline-flex; align-items:center; min-height:22px; padding:0 .48rem; border-radius:999px; background:#eaf2ff; color:#084aa4; font-size:.7rem; }
  .role-access-body { padding:.15rem .85rem .8rem; }
  .role-access-note { color:#17655f; background:#ecfbf8; border-left:3px solid #0b7a75; padding:.5rem .6rem; margin:.25rem 0 .55rem; font-size:.76rem; }
  .role-access-list { list-style:none; padding:0; margin:0; display:grid; gap:.38rem; }
  .role-access-item { display:grid; grid-template-columns:auto minmax(0,1fr); gap:.45rem; align-items:start; color:#34445b; font-size:.76rem; line-height:1.35; }
  .access-kind { display:inline-flex; align-items:center; min-height:19px; padding:0 .38rem; border-radius:4px; font-size:.63rem; font-weight:800; text-transform:uppercase; }
  .access-kind--menu { background:#e9f7f4; color:#0b6b63; }
  .access-kind--module { background:#edf2ff; color:#264db5; }
  .access-kind--action { background:#fff4df; color:#8a5a00; }
  .role-access-empty { color:#7a8698; font-size:.76rem; margin:.25rem 0; }
  .form-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; }
  .btn-confirmar { background: var(--dorado); color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 999px; font-weight: 700; cursor: pointer; font-size: 0.95rem; transition: background 0.2s; }
  .btn-confirmar:hover { background: var(--cedro); }
  .btn-eliminar-confirm { background: #c0392b; color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 999px; font-weight: 700; cursor: pointer; font-size: 0.95rem; transition: background 0.2s; }
  .btn-eliminar-confirm:hover { background: #922b21; }
  .btn-cancelar { background: #fff; color: var(--cedro); border: 1px solid var(--cedro); padding: 0.75rem 1.5rem; border-radius: 999px; font-weight: 700; text-decoration: none; font-size: 0.95rem; transition: all 0.2s; display: inline-flex; align-items: center; }
  .btn-cancelar:hover { background: var(--cedro); color: #fff; }
  @media (max-width: 640px) {
    .form-card { padding: 1.25rem; }
    .roles-grid { grid-template-columns: 1fr; }
  }

  /* Barra de búsqueda con autocompletar, mismo patrón sc-combo usado en
     Kárdex/Inventario/Médicos/Citas. Ver public/js/kardex-autocomplete.js. */
  .sc-combo { position: relative; }
  .sc-combo-input {
    width: 100%;
    padding: 0.7rem 0.9rem;
    border: 1px solid #ddd;
    border-radius: 10px;
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
  .links-grid { display: grid; gap: 1rem; }
  .links-hint { color: #5f6f83; font-size: .8rem; margin: -.3rem 0 0; }
</style>

<div class="form-card">
  <h2>{{FormTitle}}</h2>

  {{if self_access_enabled}}
  <div class="self-access-enabled">Tiene autorización para modificar sus propios datos, estado y roles.</div>
  {{endif self_access_enabled}}

  <form method="post" autocomplete="off" action="index.php?page=Security_User&mode={{mode}}&id={{u_usercod}}" {{if confirm_self_changes}}data-confirm="¿Confirma los cambios sobre su propia cuenta? Modificar el estado o retirar el rol Administrador puede cambiar su acceso inmediatamente."{{endif confirm_self_changes}}>
    <input type="hidden" name="csrf_token" value="{{csrf_token}}">

    <input type="hidden" name="usercod" value="{{u_usercod}}">

    <div class="form-field">
      <label>Nombre</label>
      {{if field_readonly}}
      <input type="text" name="username" value="{{val_username}}" readonly autocomplete="off" class="form-input">
      {{endif field_readonly}}
      {{ifnot field_readonly}}
      <input type="text" name="username" value="{{val_username}}" autocomplete="off" class="form-input">
      {{endifnot field_readonly}}
      {{if errorNombre}}<div class="error">{{errorNombre}}</div>{{endif errorNombre}}
    </div>

    <div class="form-field">
      <label>Email</label>
      {{if email_readonly}}
      <input type="email" name="useremail" value="{{val_useremail}}" readonly autocomplete="off" class="form-input">
      {{endif email_readonly}}
      {{ifnot email_readonly}}
      <input type="email" name="useremail" value="{{val_useremail}}" autocomplete="off" class="form-input">
      {{endifnot email_readonly}}
      {{if errorEmail}}<div class="error">{{errorEmail}}</div>{{endif errorEmail}}
    </div>

    {{if is_insert}}
    <div class="form-field">
      <label>Password</label>
      <input type="password" name="userpswd" value="" autocomplete="new-password" class="form-input">
      {{if errorPswd}}<div class="error">{{errorPswd}}</div>{{endif errorPswd}}
    </div>
    {{endif is_insert}}

    <div class="form-field">
      <label>Estado</label>
      {{if selects_locked}}
      <select name="userest" class="form-select" disabled>
      {{endif selects_locked}}
      {{ifnot selects_locked}}
      <select name="userest" class="form-select">
      {{endifnot selects_locked}}
        {{if est_ACT}}<option value="ACT" selected>Activo</option>{{endif est_ACT}}
        {{ifnot est_ACT}}<option value="ACT">Activo</option>{{endifnot est_ACT}}
        {{if est_INA}}<option value="INA" selected>Inactivo</option>{{endif est_INA}}
        {{ifnot est_INA}}<option value="INA">Inactivo</option>{{endifnot est_INA}}
      </select>
      {{if errorEstado}}<div class="error">{{errorEstado}}</div>{{endif errorEstado}}
      {{if warn_self}}<div class="self-note">&#9888; No puedes cambiar tu propio estado</div>{{endif warn_self}}
    </div>

    <fieldset class="form-field" style="border:0; padding:0; margin-inline:0;">
      <legend style="color:var(--cedro); font-weight:700; font-size:0.9rem; margin-bottom:0.55rem;">Roles</legend>
      <div class="roles-grid">
        {{foreach roles}}
        <article class="role-option">
          <label class="role-option-select">
            <input class="role-checkbox" type="checkbox" name="role_ids[]" value="{{rolId}}" {{if selected}}checked{{endif selected}} {{if locked}}disabled{{endif locked}}>
            <span>
              <strong>{{rolNombre}}</strong>
              <small>{{rolDescripcion}}</small>
            </span>
          </label>
          <details class="role-access">
            <summary>
              <span>Ver accesos</span>
              <span class="role-access-count">{{permission_count}}</span>
            </summary>
            <div class="role-access-body">
              {{if automatic_access}}
              <div class="role-access-note">Acceso total automático a todas las funciones activas.</div>
              {{endif automatic_access}}
              {{if has_permissions}}
              <ul class="role-access-list">
                {{foreach permissions}}
                <li class="role-access-item">
                  <span class="access-kind access-kind--{{accessClass}}">{{accessType}}</span>
                  <span>{{funcionDescripcion}}</span>
                </li>
                {{endfor permissions}}
              </ul>
              {{endif has_permissions}}
              {{ifnot has_permissions}}
              <p class="role-access-empty">Este rol no tiene accesos activos.</p>
              {{endifnot has_permissions}}
            </div>
          </details>
        </article>
        {{endfor roles}}
      </div>
      {{if errorRoles}}<div class="error">{{errorRoles}}</div>{{endif errorRoles}}
      {{if warn_self}}<div class="self-note">&#9888; No puedes cambiar tus propios roles</div>{{endif warn_self}}
    </fieldset>

    <fieldset class="form-field" style="border:0; padding:0; margin-inline:0;">
      <legend style="color:var(--cedro); font-weight:700; font-size:0.9rem; margin-bottom:0.55rem;">Vincular con un registro existente (opcional)</legend>
      <p class="links-hint">Conecta esta cuenta con un médico, paciente o enfermera YA registrado. No crea ningún registro nuevo, y cada uno solo puede estar vinculado a una cuenta a la vez.</p>

      <div class="links-grid">
        <div class="form-field sc-combo" data-sc-combo>
          <label for="medico_search">Médico vinculado</label>
          <input type="text" id="medico_search" class="form-input sc-combo-input" autocomplete="off"
                 placeholder="Buscar médico por nombre, especialidad o colegiatura..."
                 value="{{medicoNombreSeleccionado}}" data-sc-combo-input data-options="{{medicosJsonAttr}}"
                 {{if links_locked}}disabled{{endif links_locked}}>
          <input type="hidden" id="medico_id" name="medico_id" data-sc-combo-hidden value="{{medicoIdSeleccionadoValue}}">
          <div class="sc-combo-results" data-sc-combo-results hidden></div>
          {{if errorMedico}}<div class="error">{{errorMedico}}</div>{{endif errorMedico}}
        </div>

        <div class="form-field sc-combo" data-sc-combo>
          <label for="paciente_search">Paciente vinculado</label>
          <input type="text" id="paciente_search" class="form-input sc-combo-input" autocomplete="off"
                 placeholder="Buscar paciente por nombre o identidad..."
                 value="{{pacienteNombreSeleccionado}}" data-sc-combo-input data-options="{{pacientesJsonAttr}}"
                 {{if links_locked}}disabled{{endif links_locked}}>
          <input type="hidden" id="paciente_id" name="paciente_id" data-sc-combo-hidden value="{{pacienteIdSeleccionadoValue}}">
          <div class="sc-combo-results" data-sc-combo-results hidden></div>
          {{if errorPaciente}}<div class="error">{{errorPaciente}}</div>{{endif errorPaciente}}
        </div>

        <div class="form-field sc-combo" data-sc-combo>
          <label for="enfermera_search">Enfermera vinculada</label>
          <input type="text" id="enfermera_search" class="form-input sc-combo-input" autocomplete="off"
                 placeholder="Buscar enfermera por nombre o colegiatura..."
                 value="{{enfermeraNombreSeleccionado}}" data-sc-combo-input data-options="{{enfermerasJsonAttr}}"
                 {{if links_locked}}disabled{{endif links_locked}}>
          <input type="hidden" id="enfermera_id" name="enfermera_id" data-sc-combo-hidden value="{{enfermeraIdSeleccionadoValue}}">
          <div class="sc-combo-results" data-sc-combo-results hidden></div>
          {{if errorEnfermera}}<div class="error">{{errorEnfermera}}</div>{{endif errorEnfermera}}
        </div>
      </div>
    </fieldset>

    <div class="form-actions">
      {{if show_commit}}
        {{if is_delete}}
        <button type="submit" class="btn-eliminar-confirm">Eliminar</button>
        {{endif is_delete}}
        {{ifnot is_delete}}
        <button type="submit" class="btn-confirmar">Guardar</button>
        {{endifnot is_delete}}
      {{endif show_commit}}
      <a href="index.php?page=Security_Users" class="btn-cancelar">Cancelar</a>
    </div>

  </form>
</div>
