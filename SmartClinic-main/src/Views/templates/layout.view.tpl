<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{SITE_TITLE}}</title>
  <link rel="stylesheet" href="{{~BASE_DIR}}/public/css/main.css" />
  {{if FONT_AWESOME_KIT}}
  <script src="https://kit.fontawesome.com/{{FONT_AWESOME_KIT}}.js" crossorigin="anonymous"></script>
  {{endif FONT_AWESOME_KIT}}
  {{foreach SiteLinks}}
  <link rel="stylesheet" href="{{~BASE_DIR}}/{{this}}" />
  {{endfor SiteLinks}}
  {{foreach BeginScripts}}
  <script src="{{~BASE_DIR}}/{{this}}"></script>
  {{endfor BeginScripts}}
</head>

<body>
  <nav id="sc-navbar"{{if navDark}} class="nav-dark"{{endif navDark}}>
    <div class="container nav-inner">
      <a href="index.php?page=Landing" class="nav-logo">
        <img src="{{~BASE_DIR}}/public/img/logo.png" alt="" width="32" height="32" />
        <span class="nav-logo-name">Smart<span class="nav-logo-accent">Clinic</span></span>
      </a>

      <ul class="nav-links">
        <li><a href="index.php?page=Landing"{{if nav_Landing}} class="active"{{endif nav_Landing}}>Inicio</a></li>
        <li><a href="index.php?page=Nosotros"{{if nav_Nosotros}} class="active"{{endif nav_Nosotros}}>Nosotros</a></li>
        <li><a href="index.php?page=Servicios"{{if nav_Servicios}} class="active"{{endif nav_Servicios}}>Servicios</a></li>
        <li><a href="index.php?page=Contacto"{{if nav_Contacto}} class="active"{{endif nav_Contacto}}>Contacto</a></li>
      </ul>

      <a href="index.php?page=Sec_Login" class="btn btn--light nav-login-btn desktop-only">Iniciar sesión</a>

      <button class="nav-toggle" id="navToggle" aria-label="Menú">
        <span></span><span></span><span></span>
      </button>
    </div>

    <div class="nav-mobile" id="navMobile">
      <ul>
        <li><a href="index.php?page=Landing">Inicio</a></li>
        <li><a href="index.php?page=Nosotros">Nosotros</a></li>
        <li><a href="index.php?page=Servicios">Servicios</a></li>
        <li><a href="index.php?page=Contacto">Contacto</a></li>
        <li><a href="index.php?page=Sec_Login">Iniciar sesión</a></li>
      </ul>
    </div>
  </nav>

  <main>
    {{{page_content}}}
  </main>

  <footer id="sc-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a href="index.php?page=Landing" class="nav-logo footer-brand-logo">
            <img src="{{~BASE_DIR}}/public/img/logo.png" alt="" width="32" height="32" />
            <span class="nav-logo-name">Smart<span class="nav-logo-accent">Clinic</span></span>
          </a>
          <p>Sistema de gestión de citas médicas para centros de salud modernos en Honduras.</p>
        </div>

        <div class="footer-col">
          <h5>Navegación</h5>
          <ul>
            <li><a href="index.php?page=Landing">Inicio</a></li>
            <li><a href="index.php?page=Nosotros">Nosotros</a></li>
            <li><a href="index.php?page=Servicios">Servicios</a></li>
            <li><a href="index.php?page=Contacto">Contacto</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h5>Sistema</h5>
          <ul>
            {{if login}}
            <li><a href="index.php?page=Home">Panel admin</a></li>
            <li><a href="index.php?page=Security_Perfil">Mi perfil</a></li>
            <li><a href="index.php?page=Sec_Logout">Cerrar sesión</a></li>
            {{else}}
            <li><a href="index.php?page=Sec_Login">Iniciar sesión</a></li>
            <li><a href="index.php?page=Sec_Register">Crear cuenta</a></li>
            {{endif login}}
            <li><a href="index.php?page=Contacto">Soporte</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h5>Contacto</h5>
          <ul>
            <li><a href="mailto:grupo3.smartclinic@unicah.edu.hn">grupo3.smartclinic@unicah.edu.hn</a></li>
            <li><a href="tel:+50422345678">+504 2234-5678</a></li>
            <li><span>Tegucigalpa, Honduras</span></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <span>&copy; {{~CURRENT_YEAR}} SmartClinic — IF361-1801 · Seminario-Taller de Software · UNICAH</span>
        <span>Grupo 3</span>
      </div>
    </div>
  </footer>

  {{foreach EndScripts}}
  <script src="{{~BASE_DIR}}/{{this}}"></script>
  {{endfor EndScripts}}
</body>

</html>
