<style>
  .crud-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
  .crud-header h2 { color: var(--cedro); font-size: 1.8rem; font-weight: 800; }
  .btn-nuevo { background: var(--dorado); color: #fff; border: none; padding: 0.7rem 1.4rem; border-radius: 999px; font-weight: 700; cursor: pointer; text-decoration: none; font-size: 0.95rem; transition: background 0.2s; }
  .btn-nuevo:hover { background: var(--cedro); color: #fff; }
  .filtros { background: #fff; border-radius: 8px; padding: 1.2rem 1.5rem; box-shadow: 0 2px 12px rgba(6,54,112,0.07); display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; margin-bottom: 1.5rem; }
  .filtro-group { display: flex; flex-direction: column; gap: 0.3rem; }
  .filtro-group label { color: var(--cedro); font-weight: 700; font-size: 0.9rem; }
  .filtro-group input, .filtro-group select { border: 1px solid #ddd; border-radius: 10px; padding: 0.6rem 0.8rem; font-size: 0.95rem; background: #fff; outline: none; }
  .filtro-group input:focus, .filtro-group select:focus { border-color: var(--dorado); }
  .btn-filtrar { background: var(--cedro); color: #fff; border: none; padding: 0.65rem 1.2rem; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 0.95rem; transition: background 0.2s; }
  .btn-filtrar:hover { background: var(--dorado); }
  .tabla-wrapper { background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(6,54,112,0.07); overflow-x: auto; }
  .tabla-wrapper table { width: 100%; min-width: 980px; border-collapse: collapse; color: #222; }
  .tabla-wrapper thead { background: var(--cedro); color: #fff; }
  .tabla-wrapper thead th { padding: 1rem 1.2rem; text-align: left; font-size: 0.9rem; }
  .tabla-wrapper tbody tr { border-bottom: 1px solid #f0ece8; transition: background 0.15s; }
  .tabla-wrapper tbody tr:hover { background: var(--arena); }
  .tabla-wrapper tbody td { padding: 0.9rem 1.2rem; font-size: 0.95rem; color: #222; }
  .tabla-wrapper tbody td a { color: #1a56db; text-decoration: none; }
  .tabla-wrapper tbody td a:hover { text-decoration: underline; }
  .badge-act { background: #e6f4ea; color: #2d7a3a; padding: 3px 10px; border-radius: 999px; font-size: 0.82rem; font-weight: 700; }
  .badge-ina { background: #fdecea; color: #c0392b; padding: 3px 10px; border-radius: 999px; font-size: 0.82rem; font-weight: 700; }
  .badge-tipo { background: #f0ece8; color: var(--cedro); padding: 3px 10px; border-radius: 999px; font-size: 0.82rem; font-weight: 700; }
  .acciones { display: flex; gap: 0.5rem; }
  .btn-ver, .btn-editar, .btn-eliminar { padding: 0.4rem 0.9rem; border-radius: 999px; font-size: 0.82rem; font-weight: 700; text-decoration: none; transition: 0.2s; }
  .btn-ver { background: #e8f0fe; color: #1a56db; border: 1px solid #1a56db; }
  .btn-ver:hover { background: #1a56db; color: #fff; }
  .btn-editar { background: var(--arena); color: var(--cedro); border: 1px solid var(--cedro); }
  .btn-editar:hover { background: var(--cedro); color: #fff; }
  .btn-eliminar { background: #fdecea; color: #c0392b; border: 1px solid #c0392b; }
  .btn-eliminar:hover { background: #c0392b; color: #fff; }
  .role-permissions { min-width:250px; }
  .role-permissions summary { display:flex; align-items:center; justify-content:space-between; gap:.55rem; color:#0b4fb3; font-weight:750; font-size:.82rem; cursor:pointer; list-style:none; }
  .role-permissions summary::-webkit-details-marker { display:none; }
  .role-permissions summary::after { content:"+"; font-size:1rem; }
  .role-permissions[open] summary::after { content:"−"; }
  .permission-count { display:inline-flex; align-items:center; min-height:24px; padding:0 .5rem; border-radius:999px; background:#eaf2ff; color:#084aa4; font-size:.7rem; }
  .permission-list { list-style:none; padding:.6rem 0 0; margin:0; display:grid; gap:.35rem; }
  .permission-list li { display:grid; grid-template-columns:auto minmax(0,1fr); gap:.42rem; align-items:start; color:#4d5d73; font-size:.75rem; line-height:1.3; }
  .permission-type { display:inline-flex; align-items:center; min-height:18px; padding:0 .35rem; border-radius:4px; font-size:.6rem; font-weight:800; text-transform:uppercase; }
  .permission-type--menu { background:#e9f7f4; color:#0b6b63; }
  .permission-type--module { background:#edf2ff; color:#264db5; }
  .permission-type--action { background:#fff4df; color:#8a5a00; }
  .automatic-note { display:block; color:#17655f; font-size:.72rem; margin-top:.45rem; }
  .empty-state { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(92,64,51,0.07); padding: 3rem; text-align: center; color: #aaa; }
  .empty-state p { font-size: 1rem; font-weight: 600; }
</style>

<div class="crud-header">
  <h2>Roles</h2>
  <a href="index.php?page=Security_Rol&mode=INS" class="btn-nuevo">+ Nuevo Rol</a>
</div>

<form class="filtros" action="index.php" method="get">
  <input type="hidden" name="page" value="Security_Roles">
  <div class="filtro-group">
    <label for="partialName">Nombre o descripción</label>
    <input type="text" name="partialName" id="partialName" value="{{partialName}}" placeholder="Buscar...">
  </div>
  <div class="filtro-group">
    <label for="status">Estado</label>
    <select name="status" id="status">
      <option value="EMP" {{status_EMP}}>Todos</option>
      <option value="ACT" {{status_ACT}}>Activo</option>
      <option value="INA" {{status_INA}}>Inactivo</option>
    </select>
  </div>
  <button type="submit" class="btn-filtrar">Filtrar</button>
</form>

{{if total}}
<div class="tabla-wrapper">
  <table>
    <thead>
      <tr>
        <th>
          {{ifnot OrderByRolescod}}
          <a href="index.php?page=Security_Roles&orderBy=rolescod&orderDescending=0">Nombre <i class="fas fa-sort"></i></a>
          {{endifnot OrderByRolescod}}
          {{if OrderByRolescodDesc}}
          <a href="index.php?page=Security_Roles&orderBy=clear&orderDescending=0">Nombre <i class="fas fa-sort-down"></i></a>
          {{endif OrderByRolescodDesc}}
          {{if OrderByRolescod}}
          <a href="index.php?page=Security_Roles&orderBy=rolescod&orderDescending=1">Nombre <i class="fas fa-sort-up"></i></a>
          {{endif OrderByRolescod}}
        </th>
        <th>Descripción</th>
        <th>Accesos</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      {{foreach roles}}
      <tr>
        <td>{{rolescod}}</td>
        <td>{{rolesdsc}}</td>
        <td>
          <details class="role-permissions">
            <summary>
              <span>Ver accesos</span>
              <span class="permission-count">{{permission_count}}</span>
            </summary>
            {{if automatic_access}}<span class="automatic-note">Acceso total automático</span>{{endif automatic_access}}
            {{if has_permissions}}
            <ul class="permission-list">
              {{foreach permissions}}
              <li>
                <span class="permission-type permission-type--{{accessClass}}">{{accessType}}</span>
                <span>{{funcionDescripcion}}</span>
              </li>
              {{endfor permissions}}
            </ul>
            {{endif has_permissions}}
            {{ifnot has_permissions}}<span class="automatic-note">Sin accesos activos</span>{{endifnot has_permissions}}
          </details>
        </td>
        <td><span class="badge-status" data-status="{{rolesest}}">{{rolesest}}</span></td>
        <td>
          <div class="acciones">
            <a href="index.php?page=Security_Rol&mode=DSP&id={{role_id_url}}" class="btn btn--outline" style="padding:6px 12px; font-size:12px;">Ver</a>
            <a href="index.php?page=Security_Rol&mode=UPD&id={{role_id_url}}" class="btn btn--outline" style="padding:6px 12px; font-size:12px;">Editar</a>
            {{if can_deactivate}}
            <a href="index.php?page=Security_Rol&mode=DEL&id={{role_id_url}}" class="btn btn--outline" style="padding:6px 12px; font-size:12px;">Desactivar</a>
            {{endif can_deactivate}}
          </div>
        </td>
      </tr>
      {{endfor roles}}
    </tbody>
  </table>
</div>
{{pagination}}
{{endif total}}

{{ifnot total}}
<div class="empty-state">
  <p>No se encontraron roles.</p>
</div>
{{endifnot total}}

<script>
document.querySelectorAll('.badge-status').forEach(badge => {
  badge.classList.add(badge.dataset.status === 'ACT' ? 'badge-act' : 'badge-ina');
});
</script>
