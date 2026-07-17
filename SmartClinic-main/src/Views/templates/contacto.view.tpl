<div class="page-hero">
  <div class="container">
    <div class="section-label">Estamos para ayudarte</div>
    <h1>Contacto</h1>
    <p>¿Tienes preguntas sobre SmartClinic? Escríbenos y te responderemos a la brevedad.</p>
  </div>
</div>

<section class="section-pad">
  <div class="container">
    <div class="contacto-layout">
      <div class="contacto-info reveal">
        <div>
          <div class="section-label">Información de contacto</div>
          <h2 style="margin-bottom:8px;">Encuéntranos aquí</h2>
          <p style="font-size:14px;margin-bottom:24px;">Proyecto académico desarrollado en UNICAH, Tegucigalpa.</p>
        </div>
        <div class="info-item"><div class="info-icon">📍</div><div class="info-text"><h4>Dirección</h4><p>Universidad Católica de Honduras<br>Tegucigalpa, Honduras</p></div></div>
        <div class="info-item"><div class="info-icon">✉️</div><div class="info-text"><h4>Correo electrónico</h4><p>grupo3.smartclinic@unicah.edu.hn</p></div></div>
        <div class="info-item"><div class="info-icon">🕐</div><div class="info-text"><h4>Horario de atención</h4><p>Lunes a Viernes: 7:00 AM – 5:00 PM<br>Sábados: 8:00 AM – 12:00 PM</p></div></div>
        <div class="info-item"><div class="info-icon">🐙</div><div class="info-text"><h4>Repositorio GitHub</h4><p><a href="https://github.com/jrhdezesp/SmartClinic.git" style="color:var(--sc-blue-700);font-weight:500;">jrhdezesp/SmartClinic</a></p></div></div>
        <div class="mapa-placeholder"><div style="font-size:32px;margin-bottom:8px;">🗺️</div><p>Universidad Católica de Honduras<br>Tegucigalpa, Francisco Morazán</p></div>
      </div>

      <div class="contacto-form reveal reveal-delay-1">
        <h3 style="margin-bottom:4px;">Envíanos un mensaje</h3>
        <p style="font-size:14px;margin-bottom:24px;">Completa el formulario y te responderemos pronto.</p>
        <form id="contactoForm" method="POST" action="index.php?page=Contacto" novalidate>
          <input type="hidden" name="csrf_token" value="{{csrf_token}}">
          <div class="form-row">
            <div class="form-group"><label for="nombre">Nombre completo *</label><input type="text" id="nombre" name="nombre" placeholder="Juan Pérez" required></div>
            <div class="form-group"><label for="email">Correo electrónico *</label><input type="email" id="email" name="email" placeholder="juan@correo.com" required></div>
          </div>
          <div class="form-group"><label for="asunto">Asunto *</label><select id="asunto" name="asunto" required><option value="">Selecciona un asunto</option><option>Consulta sobre el sistema</option><option>Reporte de error</option><option>Solicitud de acceso</option><option>Colaboración académica</option><option>Otro</option></select></div>
          <div class="form-group"><label for="mensaje">Mensaje *</label><textarea id="mensaje" name="mensaje" placeholder="Describe tu consulta o mensaje..." required></textarea></div>
          <button type="submit" class="btn-submit" id="btnSubmit">Enviar mensaje</button>
          <div class="form-alert success" id="alertSuccess">Mensaje recibido correctamente. Te contactaremos pronto.</div>
          <div class="form-alert error" id="alertError">Por favor completa todos los campos requeridos.</div>
        </form>
      </div>
    </div>
  </div>
</section>
