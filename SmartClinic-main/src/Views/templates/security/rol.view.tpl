<style>
  .role-form-card { background:#fff; border:1px solid #dbe5f1; border-radius:8px; box-shadow:0 6px 24px rgba(6,54,112,.08); padding:2rem; max-width:980px; margin:0 auto; }
  .role-form-card h2 { color:#082b5c; font-size:1.75rem; font-weight:800; margin:0 0 .35rem; }
  .role-form-subtitle { color:#617086; margin:0 0 1.6rem; }
  .role-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
  .role-field { display:flex; flex-direction:column; gap:.4rem; margin-bottom:1rem; }
  .role-field--wide { grid-column:1 / -1; }
  .role-field label { color:#17365f; font-weight:700; font-size:.9rem; }
  .role-input,.role-select { width:100%; box-sizing:border-box; border:1px solid #cbd8e8; border-radius:8px; padding:.72rem .85rem; background:#fff; color:#172033; font:inherit; }
  .role-input:focus,.role-select:focus { outline:none; border-color:#0b5fc6; box-shadow:0 0 0 3px rgba(11,95,198,.12); }
  .role-input[readonly],.role-select:disabled { background:#f3f6f9; color:#6b7788; cursor:not-allowed; }
  .role-error { color:#b42318; background:#fff1f0; border:1px solid #ffc9c5; border-radius:8px; padding:.55rem .7rem; font-size:.84rem; }
  .access-heading { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding-top:1rem; margin-top:.25rem; border-top:1px solid #dbe5f1; }
  .access-heading h3 { margin:0; color:#082b5c; font-size:1.1rem; }
  .access-count { display:inline-flex; align-items:center; min-height:28px; padding:0 .65rem; border-radius:999px; background:#eaf2ff; color:#084aa4; font-size:.78rem; font-weight:800; white-space:nowrap; }
  .automatic-access { margin:.8rem 0 0; border-left:3px solid #0b7a75; background:#ecfbf8; color:#145b58; padding:.7rem .85rem; font-size:.86rem; }
  .permission-groups { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1.25rem; margin-top:1rem; }
  .permission-group { min-width:0; }
  .permission-group h4 { color:#174f95; font-size:.82rem; text-transform:uppercase; margin:0 0 .55rem; padding-bottom:.45rem; border-bottom:2px solid #d9e7f8; }
  .permission-list { display:grid; gap:.4rem; }
  .permission-option { display:grid; grid-template-columns:18px minmax(0,1fr); gap:.55rem; align-items:start; padding:.55rem .35rem; border-bottom:1px solid #edf1f6; cursor:pointer; }
  .permission-option:last-child { border-bottom:0; }
  .permission-option:has(input:checked) { background:#f2f7ff; }
  .permission-option:has(input:disabled) { cursor:not-allowed; opacity:.78; }
  .permission-option input { width:17px; height:17px; margin:.12rem 0 0; accent-color:#0b5fc6; }
  .permission-option strong { display:block; color:#183153; font-size:.84rem; line-height:1.3; }
  .permission-option small { display:block; color:#718096; font-size:.73rem; line-height:1.3; margin-top:.12rem; overflow-wrap:anywhere; }
  .role-form-actions { display:flex; justify-content:flex-end; gap:.75rem; margin-top:1.6rem; padding-top:1rem; border-top:1px solid #dbe5f1; }
  .role-btn { display:inline-flex; align-items:center; justify-content:center; min-height:42px; padding:0 1.2rem; border-radius:8px; font-weight:750; text-decoration:none; border:1px solid transparent; cursor:pointer; }
  .role-btn--primary { background:#0b4fb3; color:#fff; }
  .role-btn--danger { background:#c9362b; color:#fff; }
  .role-btn--secondary { background:#fff; color:#0b4fb3; border-color:#0b4fb3; }
  @media (max-width:820px) {
    .permission-groups { grid-template-columns:1fr; }
  }
  @media (max-width:640px) {
    .role-form-card { padding:1.2rem; }
    .role-form-grid { grid-template-columns:1fr; }
    .role-field--wide { grid-column:auto; }
    .access-heading { align-items:flex-start; flex-direction:column; }
    .role-form-actions { flex-wrap:wrap; }
    .role-btn { flex:1 1 130px; }
  }
</style>

<section class="role-form-card">
  <h2>{{FormTitle}}</h2>
  <p class="role-form-subtitle">Defina la identidad del rol y los accesos que concede.</p>

  <form method="post" action="index.php?page=Security_Rol&mode={{mode}}&id={{role_id_url}}">
    <input type="hidden" name="csrf_token" value="{{csrf_token}}">
    <input type="hidden" name="rolescod_original" value="{{rolescod_original}}">

    <div class="role-form-grid">
      <div class="role-field">
        <label for="rolescod">Nombre del rol</label>
        <input id="rolescod" type="text" name="rolescod" value="{{rolescod}}" maxlength="50" class="role-input" {{if name_readonly}}readonly{{endif name_readonly}}>
        {{if rolescod_error}}<div class="role-error">{{rolescod_error}}</div>{{endif rolescod_error}}
      </div>

      <div class="role-field">
        <label for="rolesest">Estado</label>
        <select id="rolesest" name="rolesest" class="role-select" {{if status_locked}}disabled{{endif status_locked}}>
          <option value="ACT" {{rolesest_act}}>Activo</option>
          <option value="INA" {{rolesest_ina}}>Inactivo</option>
        </select>
        {{if rolesest_error}}<div class="role-error">{{rolesest_error}}</div>{{endif rolesest_error}}
      </div>

      <div class="role-field role-field--wide">
        <label for="rolesdsc">Descripción</label>
        <input id="rolesdsc" type="text" name="rolesdsc" value="{{rolesdsc}}" maxlength="150" class="role-input" {{if description_readonly}}readonly{{endif description_readonly}}>
        {{if rolesdsc_error}}<div class="role-error">{{rolesdsc_error}}</div>{{endif rolesdsc_error}}
      </div>
    </div>

    <div class="access-heading">
      <h3>Accesos del rol</h3>
      <span class="access-count">{{selected_permission_count}} seleccionados</span>
    </div>

    {{if automatic_access}}
    <div class="automatic-access">El rol Administrador tiene acceso automático a todas las funciones activas del sistema.</div>
    {{endif automatic_access}}

    {{if permissions_error}}<div class="role-error" style="margin-top:.8rem;">{{permissions_error}}</div>{{endif permissions_error}}

    <div class="permission-groups">
      {{foreach functionGroups}}
      <section class="permission-group">
        <h4>{{groupName}}</h4>
        <div class="permission-list">
          {{foreach functions}}
          <label class="permission-option">
            <input type="checkbox" name="permission_ids[]" value="{{funcionId}}" {{if selected}}checked{{endif selected}} {{if locked}}disabled{{endif locked}}>
            <span>
              <strong>{{funcionDescripcion}}</strong>
              <small>{{funcionNombre}}</small>
            </span>
          </label>
          {{endfor functions}}
        </div>
      </section>
      {{endfor functionGroups}}
    </div>

    <div class="role-form-actions">
      {{if show_commit}}
        {{if is_delete}}
        <button type="submit" class="role-btn role-btn--danger">Desactivar rol</button>
        {{endif is_delete}}
        {{ifnot is_delete}}
        <button type="submit" class="role-btn role-btn--primary">Guardar cambios</button>
        {{endifnot is_delete}}
      {{endif show_commit}}
      <a href="index.php?page=Security_Roles" class="role-btn role-btn--secondary">Cancelar</a>
    </div>
  </form>
</section>
