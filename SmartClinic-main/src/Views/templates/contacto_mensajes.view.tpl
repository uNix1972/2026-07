<div class="container section-pad contact-inbox">
  <header class="contact-inbox__header">
    <div>
      <span class="contact-inbox__eyebrow">Atención al usuario</span>
      <h2>Mensajes de contacto</h2>
      <p>Consultas recibidas desde el formulario público de SmartClinic.</p>
    </div>
    <a class="btn btn--outline" href="index.php?page=HomeController">Volver</a>
  </header>

  {{if success}}
  <div class="alert alert--success" role="status">{{success}}</div>
  {{endif success}}
  {{if error}}
  <div class="alert alert--error" role="alert">{{error}}</div>
  {{endif error}}

  <section class="contact-inbox__summary" aria-label="Resumen de mensajes">
    <div><span>Total</span><strong>{{totalMensajes}}</strong></div>
    <div class="is-pending"><span>Pendientes</span><strong>{{totalPendientes}}</strong></div>
    <div class="is-read"><span>Leídos</span><strong>{{totalLeidos}}</strong></div>
    <div class="is-resolved"><span>Resueltos</span><strong>{{totalResueltos}}</strong></div>
  </section>

  <form class="contact-inbox__filters" method="GET" action="index.php">
    <input type="hidden" name="page" value="ContactoMensajesController">
    <div class="form-group">
      <label for="contact-search">Buscar</label>
      <input id="contact-search" type="search" name="search" value="{{searchValue}}" placeholder="Nombre, correo, asunto o texto">
    </div>
    <div class="form-group">
      <label for="contact-status">Estado</label>
      <select id="contact-status" name="estado">
        <option value="" {{if filterAll}}selected{{endif filterAll}}>Todos</option>
        <option value="PEN" {{if filterPending}}selected{{endif filterPending}}>Pendientes</option>
        <option value="LEI" {{if filterRead}}selected{{endif filterRead}}>Leídos</option>
        <option value="RES" {{if filterResolved}}selected{{endif filterResolved}}>Resueltos</option>
      </select>
    </div>
    <button class="btn" type="submit">Aplicar</button>
    <a class="btn btn--outline" href="index.php?page=ContactoMensajesController">Limpiar</a>
  </form>

  <section class="contact-inbox__table-wrap">
    {{if mensajes}}
    <table class="contact-inbox__table">
      <thead>
        <tr>
          <th>Remitente</th>
          <th>Asunto y mensaje</th>
          <th>Fecha</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        {{foreach mensajes}}
        <tr>
          <td>
            <div class="contact-inbox__cell-stack">
              <strong>{{nombre}}</strong>
              <a href="mailto:{{email}}">{{email}}</a>
            </div>
          </td>
          <td>
            <div class="contact-inbox__cell-stack">
              <strong>{{asunto}}</strong>
              <details>
                <summary>Ver mensaje</summary>
                <p>{{mensaje}}</p>
              </details>
            </div>
          </td>
          <td>{{fecha_creacion_texto}}</td>
          <td>
            <span class="contact-status contact-status--{{estado}}">{{estado_texto}}</span>
          </td>
          <td>
            <div class="contact-inbox__actions">
              {{if estado_pendiente}}
              <form method="POST" action="index.php?page=ContactoMensajesController&action=status">
                <input type="hidden" name="csrf_token" value="{{csrf_token}}">
                <input type="hidden" name="id" value="{{id}}">
                <input type="hidden" name="estado" value="LEI">
                <button class="btn btn--outline" type="submit">Marcar leído</button>
              </form>
              {{endif estado_pendiente}}
              {{if estado_leido}}
              <form method="POST" action="index.php?page=ContactoMensajesController&action=status">
                <input type="hidden" name="csrf_token" value="{{csrf_token}}">
                <input type="hidden" name="id" value="{{id}}">
                <input type="hidden" name="estado" value="PEN">
                <button class="btn btn--outline" type="submit">Reabrir</button>
              </form>
              {{endif estado_leido}}
              {{if estado_resuelto}}
              <form method="POST" action="index.php?page=ContactoMensajesController&action=status">
                <input type="hidden" name="csrf_token" value="{{csrf_token}}">
                <input type="hidden" name="id" value="{{id}}">
                <input type="hidden" name="estado" value="LEI">
                <button class="btn btn--outline" type="submit">Reabrir</button>
              </form>
              {{endif estado_resuelto}}
              {{ifnot estado_resuelto}}
              <form method="POST" action="index.php?page=ContactoMensajesController&action=status">
                <input type="hidden" name="csrf_token" value="{{csrf_token}}">
                <input type="hidden" name="id" value="{{id}}">
                <input type="hidden" name="estado" value="RES">
                <button class="btn" type="submit">Resolver</button>
              </form>
              {{endifnot estado_resuelto}}
            </div>
          </td>
        </tr>
        {{endfor mensajes}}
      </tbody>
    </table>
    {{endif mensajes}}
    {{ifnot mensajes}}
    <div class="contact-inbox__empty">
      <strong>No se encontraron mensajes</strong>
      <span>Pruebe con otros filtros o espere una nueva consulta.</span>
    </div>
    {{endifnot mensajes}}
  </section>
</div>
