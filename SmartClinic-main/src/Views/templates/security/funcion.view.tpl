<style>
  .form-card { background: #fff; border-radius: 18px; box-shadow: 0 4px 20px rgba(92,64,51,0.08); padding: 2rem; max-width: 720px; margin: 0 auto; }
  .form-card h2 { color: var(--cedro); font-size: 1.8rem; font-weight: 800; margin-bottom: 1.5rem; }
  .form-field { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1.3rem; }
  .form-field label { color: var(--cedro); font-weight: 700; font-size: 0.95rem; }
  .form-input, .form-select { border: 1px solid #ddd; border-radius: 10px; padding: 0.75rem 0.95rem; font-size: 0.95rem; background: #fff; outline: none; width: 100%; font-family: inherit; transition: border-color 0.2s; box-sizing: border-box; }
  .form-input:focus, .form-select:focus { border-color: var(--dorado); box-shadow: 0 0 0 3px rgba(197,160,89,0.15); }
  .form-input[readonly], .form-select:disabled { background: #f5f5f5; color: #999; cursor: not-allowed; border-color: #e0e0e0; }
  .error { color: #c0392b; font-size: 0.85rem; background: #fdecea; padding: 0.45rem 0.75rem; border-radius: 8px; }
  .form-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; flex-wrap: wrap; }
  .btn-confirmar { background: var(--dorado); color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 999px; font-weight: 700; cursor: pointer; font-size: 0.95rem; transition: background 0.2s; }
  .btn-confirmar:hover { background: var(--cedro); }
  .btn-eliminar-confirm { background: #c0392b; color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 999px; font-weight: 700; cursor: pointer; font-size: 0.95rem; transition: background 0.2s; }
  .btn-eliminar-confirm:hover { background: #922b21; }
  .btn-cancelar { background: #fff; color: var(--cedro); border: 1px solid var(--cedro); padding: 0.75rem 1.5rem; border-radius: 999px; font-weight: 700; text-decoration: none; font-size: 0.95rem; transition: all 0.2s; display: inline-flex; align-items: center; }
  .btn-cancelar:hover { background: var(--cedro); color: #fff; }
</style>

<div class="form-card">
  <h2>{{FormTitle}}</h2>

  <form method="post" action="index.php?page=Security_Funcion">
    <input type="hidden" name="csrf_token" value="{{csrf_token}}">
    <input type="hidden" name="fncod_original" value="{{fncod_original}}">
    <input type="hidden" name="fncod" value="{{fncod}}">

    <div class="form-field">
      <label>Código</label>
      <input type="text" name="fncod" value="{{fncod}}" class="form-input" {{if field_readonly}}readonly{{endif field_readonly}} autocomplete="off">
      {{if fncod_error}}<div class="error">{{fncod_error}}</div>{{endif fncod_error}}
    </div>

    <div class="form-field">
      <label>Descripción</label>
      <input type="text" name="fndsc" value="{{fndsc}}" class="form-input" {{if field_readonly}}readonly{{endif field_readonly}} autocomplete="off">
      {{if fndsc_error}}<div class="error">{{fndsc_error}}</div>{{endif fndsc_error}}
    </div>

    <div class="form-field">
      <label>Estado</label>
      {{if field_readonly}}
      <select name="fnest" class="form-select" disabled>
      {{endif field_readonly}}
      {{ifnot field_readonly}}
      <select name="fnest" class="form-select">
      {{endifnot field_readonly}}
        <option value="ACT" {{fnest_act}}>Activo</option>
        <option value="INA" {{fnest_ina}}>Inactivo</option>
      </select>
      {{if fnest_error}}<div class="error">{{fnest_error}}</div>{{endif fnest_error}}
    </div>

    <div class="form-field">
      <label>Tipo</label>
      {{if field_readonly}}
      <select name="fntyp" class="form-select" disabled>
      {{endif field_readonly}}
      {{ifnot field_readonly}}
      <select name="fntyp" class="form-select">
      {{endifnot field_readonly}}
        <option value="MNU" {{fntyp_mnu}}>Menú</option>
        <option value="FNC" {{fntyp_fnc}}>Función</option>
        <option value="CTL" {{fntyp_ctl}}>Controlador</option>
      </select>
      {{if fntyp_error}}<div class="error">{{fntyp_error}}</div>{{endif fntyp_error}}
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
      <a href="index.php?page=Security_Funciones" class="btn-cancelar">Cancelar</a>
    </div>
  </form>
</div>