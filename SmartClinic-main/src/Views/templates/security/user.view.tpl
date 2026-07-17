<style>
  .form-card { background: #fff; border-radius: 18px; box-shadow: 0 4px 20px rgba(92,64,51,0.08); padding: 2rem; max-width: 650px; margin: 0 auto; }
  .form-card h2 { color: var(--cedro); font-size: 1.6rem; font-weight: 800; margin-bottom: 1.5rem; }
  .form-field { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1.2rem; }
  .form-field label { color: var(--cedro); font-weight: 700; font-size: 0.9rem; }
  .form-input, .form-select { border: 1px solid #ddd; border-radius: 10px; padding: 0.7rem 0.9rem; font-size: 0.95rem; background: #fff; outline: none; width: 100%; font-family: inherit; transition: border-color 0.2s; box-sizing: border-box; }
  .form-input:focus, .form-select:focus { border-color: var(--dorado); box-shadow: 0 0 0 3px rgba(197,160,89,0.15); }
  .form-input[readonly], .form-select:disabled { background: #f5f5f5; color: #999; cursor: not-allowed; border-color: #e0e0e0; }
  .error { color: #c0392b; font-size: 0.85rem; background: #fdecea; padding: 0.4rem 0.7rem; border-radius: 8px; }
  .self-note { color: #7a6033; font-size: 0.82rem; background: #fdf6e3; border: 1px solid #e8d5a0; padding: 0.35rem 0.7rem; border-radius: 8px; }
  .form-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; }
  .btn-confirmar { background: var(--dorado); color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 999px; font-weight: 700; cursor: pointer; font-size: 0.95rem; transition: background 0.2s; }
  .btn-confirmar:hover { background: var(--cedro); }
  .btn-eliminar-confirm { background: #c0392b; color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 999px; font-weight: 700; cursor: pointer; font-size: 0.95rem; transition: background 0.2s; }
  .btn-eliminar-confirm:hover { background: #922b21; }
  .btn-cancelar { background: #fff; color: var(--cedro); border: 1px solid var(--cedro); padding: 0.75rem 1.5rem; border-radius: 999px; font-weight: 700; text-decoration: none; font-size: 0.95rem; transition: all 0.2s; display: inline-flex; align-items: center; }
  .btn-cancelar:hover { background: var(--cedro); color: #fff; }
</style>

<div class="form-card">
  <h2>{{FormTitle}}</h2>

  <form method="post" autocomplete="off" action="index.php?page=Security_User&mode={{mode}}&id={{u_usercod}}">
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

    <div class="form-field">
      <label>Tipo</label>
      {{if selects_locked}}
      <select name="usertipo" class="form-select" disabled>
      {{endif selects_locked}}
      {{ifnot selects_locked}}
      <select name="usertipo" class="form-select">
      {{endifnot selects_locked}}
        {{if tipo_NOR}}<option value="NOR" selected>Normal</option>{{endif tipo_NOR}}
        {{ifnot tipo_NOR}}<option value="NOR">Normal</option>{{endifnot tipo_NOR}}
        {{if tipo_ADM}}<option value="ADM" selected>Administrador</option>{{endif tipo_ADM}}
        {{ifnot tipo_ADM}}<option value="ADM">Administrador</option>{{endifnot tipo_ADM}}
        {{if tipo_CON}}<option value="CON" selected>Consultor</option>{{endif tipo_CON}}
        {{ifnot tipo_CON}}<option value="CON">Consultor</option>{{endifnot tipo_CON}}
      </select>
      {{if errorTipo}}<div class="error">{{errorTipo}}</div>{{endif errorTipo}}
      {{if warn_self}}<div class="self-note">&#9888; No puedes cambiar tu propio tipo</div>{{endif warn_self}}
    </div>

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