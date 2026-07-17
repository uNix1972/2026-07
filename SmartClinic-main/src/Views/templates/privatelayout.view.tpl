<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{SITE_TITLE}} | Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
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

  <style>
    :root {
      --cedro: #033b9f;
      --dorado: #0269cb;
      --arena: #fffefe;
      --blanco: #fffefe;
      --sombra: 0 8px 24px rgba(3, 59, 159, 0.16);
      --radio: 18px;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
    body { background: var(--arena); color: #333; display: flex; flex-direction: column; min-height: 100vh; }


    .skip-link {
      position: absolute;
      left: -999px;
      top: 10px;
      background: var(--cedro);
      color: #fff;
      padding: 10px 14px;
      border-radius: 10px;
      z-index: 2000;
      text-decoration: none;
      font-weight: 700;
    }
    .skip-link:focus { left: 12px; }
    .form-group label::after,
    .toolbar-field label::after { content: ''; }
    input:focus, select:focus, textarea:focus, button:focus, a:focus {
      outline: 3px solid rgba(2, 96, 203, 0.28);
      outline-offset: 2px;
    }
    .sc-info-card,
    .sc-panel-card,
    .sc-report-card {
      background: #fff;
      border: 1px solid #E5E7EB;
      border-radius: 18px;
      box-shadow: 0 8px 24px rgba(3, 59, 159, 0.08);
    }
    .sc-info-card { padding: 18px 20px; color: #334155; }
    .sc-report-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 16px;
    }
    .sc-report-card { padding: 22px; }
    .sc-report-card span { display:block; color:#64748b; font-weight:700; margin-bottom:8px; }
    .sc-report-card strong { display:block; color:#033B9F; font-size:2rem; }
    .sc-two-columns {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 22px;
    }
    .sc-panel-card { padding: 24px; }
    .sc-panel-card h3 { color:#111827; margin-bottom:16px; }
    @media(max-width: 900px) {
      .sc-report-grid, .sc-two-columns { grid-template-columns: 1fr; }
    }

    /* HEADER */
    .header {
      background: rgba(255,255,255,0.97);
      backdrop-filter: blur(8px);
      padding: 14px 5%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: var(--sombra);
      position: sticky;
      top: 0;
      z-index: 1001;
    }
    .logo-box { display: flex; align-items: center; gap: 12px; text-decoration: none; }
    .logo-txt { font-family: 'Montserrat', sans-serif; font-size: 1.25rem; color: var(--cedro); font-weight: 800; letter-spacing: -0.02em; white-space: nowrap; line-height: 1; }
    .logo-txt-accent { color: var(--dorado); }
    .logo-box .admin-logo-icon { flex-shrink: 0; object-fit: contain; }
    .nav-right { display: flex; align-items: center; gap: 18px; }
    .nav-right a { text-decoration: none; color: var(--cedro); font-weight: 700; font-size: 0.95rem; transition: 0.3s; }
    .nav-right a:hover { color: var(--dorado); }
    .username-label { color: var(--cedro); font-weight: 700; font-size: 0.95rem; }
    .profile-link { display: inline-flex; align-items: center; gap: 0.35rem; }
    .inline-icon {
      width: 1rem;
      height: 1rem;
      display: inline-block;
      vertical-align: -0.15rem;
      fill: currentColor;
    }

    /* HAMBURGER */
    .menu_toggle { display: none; }
    .menu_toggle_icon {
      cursor: pointer;
      display: flex; flex-direction: column; gap: 5px;
      width: 36px; padding: 4px; z-index: 1002;
    }
    .hmb { height: 3px; width: 100%; background: var(--cedro); border-radius: 2px; transition: all 0.3s; }
    .menu_toggle:checked ~ .header .menu_toggle_icon .hrz { opacity: 0; }
    .menu_toggle:checked ~ .header .menu_toggle_icon .dgn.pt-1 { transform: rotate(135deg) translate(0, -8px); }
    .menu_toggle:checked ~ .header .menu_toggle_icon .dgn.pt-2 { transform: rotate(-135deg) translate(0, 8px); }

    /* SIDEBAR */
    .sidebar {
      position: fixed; top: 0; left: 0;
      width: 270px; height: 100vh;
      background: var(--cedro);
      transform: translateX(-270px);
      transition: transform 250ms ease-in-out;
      z-index: 1000;
      padding-top: 70px;
      box-shadow: 4px 0 20px rgba(0,0,0,0.15);
    }
    .menu_toggle:checked ~ .sidebar { transform: translateX(0); }
    .sidebar ul { list-style: none; padding: 1rem 0; }
    .sidebar ul li a {
      display: flex; align-items: center; gap: 0.75rem;
      padding: 0.9rem 1.5rem;
      color: rgba(255,255,255,0.88);
      text-decoration: none; font-weight: 700; font-size: 0.95rem;
      transition: background 0.2s, color 0.2s;
      border-left: 3px solid transparent;
    }
    .sidebar ul li a:hover { background: rgba(153,222,252,0.2); color: var(--blanco); border-left-color: var(--blanco); }
    .sidebar .nav-icon {
      width: 1rem;
      height: 1rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 1rem;
    }
    .sidebar .nav-icon svg {
      width: 1rem;
      height: 1rem;
      fill: currentColor;
      display: block;
    }
    .sidebar ul li.divider { border-top: 1px solid rgba(255,255,255,0.15); margin: 0.5rem 0; }

    .table-responsive {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    .table-responsive table {
      width: 100%;
      min-width: 0;
      border-collapse: collapse;
    }
    .table-responsive th,
    .table-responsive td {
      text-align: left;
      vertical-align: middle;
    }

    .container { width: 100%; max-width: 1120px; margin: 0 auto; padding: 0 24px; }
    .section-pad { padding: 88px 0; }
    .form-card {
      width: 100%;
      max-width: 900px;
      margin: 0 auto;
      background: #fff;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(3, 59, 159, 0.12);
      border: 1px solid #EAF5FD;
    }
    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 20px;
    }
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #17203a;
    }
    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 12px;
      border: 1px solid #C7C7CC;
      border-radius: 10px;
      background: #fff;
      font-size: 1rem;
    }
    .form-group textarea { min-height: 120px; resize: vertical; }
    .form-actions {
      margin-top: 30px;
      display: flex;
      flex-wrap: wrap;
      justify-content: flex-end;
      gap: 15px;
    }
    .form-actions a,
    .form-actions button { min-width: 140px; }

    .list-toolbar {
      margin-bottom: 22px;
      padding: 20px;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 10px 30px rgba(3, 59, 159, 0.08);
    }
    .toolbar-form {
      width: 100%;
    }
    .toolbar-row {
      display: flex;
      align-items: flex-start;
      flex-wrap: wrap;
      gap: 18px;
    }
    .toolbar-field {
      flex: 1;
      min-width: 220px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      padding: 18px 18px 16px;
      border: 1px solid #E5E7EB;
      border-radius: 18px;
      background: #F8FAFC;
      box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.06);
    }
    .toolbar-field label {
      font-weight: 700;
      color: #334155;
      font-size: 0.78rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }
    .toolbar-field input,
    .toolbar-field select {
      width: 100%;
      padding: 14px 16px;
      border: 1px solid #c7c7cc;
      border-radius: 14px;
      background: #fff;
      font-size: 0.95rem;
    }
    .toolbar-field input:focus,
    .toolbar-field select:focus {
      outline: none;
      border-color: #033B9F;
      box-shadow: 0 0 0 4px rgba(3, 59, 159, 0.08);
    }
    .toolbar-submit {
      min-width: 160px;
      align-self: flex-end;
    }
    @media (max-width: 900px) {
      .toolbar-row {
        gap: 12px;
      }
    }
    @media (max-width: 760px) {
      .toolbar-row {
        flex-direction: column;
        align-items: stretch;
      }
      .toolbar-field {
        min-width: 0;
        width: 100%;
      }
      .toolbar-submit {
        width: 100%;
        align-self: stretch;
      }
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px 26px;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 700;
      transition: all 0.2s ease;
      text-decoration: none;
    }
    .btn--primary {
      background: var(--cedro);
      color: var(--blanco);
      border: 2px solid var(--cedro);
    }
    .btn--primary:hover {
      background: #0251a0;
      border-color: #0251a0;
      transform: translateY(-1px);
    }
    .btn--outline {
      background: transparent;
      color: var(--cedro);
      border: 2px solid var(--sc-gray-200);
    }
    .btn--outline:hover {
      background: var(--arena);
      border-color: var(--cedro);
    }

    @media (max-width: 760px) {
      .form-grid { grid-template-columns: 1fr; }
      .form-actions { justify-content: stretch; }
      .form-actions a,
      .form-actions button { width: 100%; }
    }

    /* MAIN */
    main { flex: 1; padding: 2.5rem 5%; }
    footer { background: var(--cedro); color: var(--blanco); text-align: center; padding: 28px 20px; margin-top: auto; }
    footer, footer * { color: var(--blanco) !important; }

    @media(max-width: 768px) {
      .header {
        flex-wrap: wrap;
        gap: 10px;
        justify-content: space-between;
      }
      .username-label {
        display: none;
      }
      .nav-right {
        display: none;
      }
      .table-responsive table { min-width: 0; }
      .table-responsive th,
      .table-responsive td { padding: 10px 12px; font-size: 0.95rem; }
    }
  </style>
</head>
<body>
  <a href="#contenido-principal" class="skip-link">Saltar al contenido principal</a>
  <input type="checkbox" class="menu_toggle" id="menu_toggle" />

  <header class="header">
    <label for="menu_toggle" class="menu_toggle_icon" aria-label="Abrir o cerrar menú de navegación">
      <div class="hmb dgn pt-1"></div>
      <div class="hmb hrz"></div>
      <div class="hmb dgn pt-2"></div>
    </label>
    <a href="index.php?page={{PRIVATE_DEFAULT_CONTROLLER}}" class="logo-box">
      <img src="{{~BASE_DIR}}/public/img/logo.png" alt="SmartClinic" width="36" height="36" class="admin-logo-icon" />
      <span class="logo-txt">Smart<span class="logo-txt-accent">Clinic</span></span>
    </a>
    <div class="nav-right">
      {{with login}}
      <a href="index.php?page=Security_Perfil" class="username-label profile-link"><svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5z"/></svg> Hola, {{userName}}</a>
      <a href="index.php?page=Sec_Logout"><svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17v-3H3v-4h7V7l5 5-5 5zm3 4H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h8v2H5v14h8v2zm8-9h-7v-2h7V7l4 5-4 5v-3z"/></svg> Salir</a>
      {{endwith login}}
    </div>
  </header>

  <nav class="sidebar">
    <ul>
      {{foreach NAVIGATION}}
      <li><a href="{{nav_url}}">{{nav_label}}</a></li>
      {{endfor NAVIGATION}}
      <li class="divider"></li>
      <li><a href="index.php?page=sec_logout"><svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17v-3H3v-4h7V7l5 5-5 5zm3 4H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h8v2H5v14h8v2zm8-9h-7v-2h7V7l4 5-4 5v-3z"/></svg> Cerrar Sesión</a></li>
    </ul>
  </nav>

  <main id="contenido-principal" tabindex="-1">
    {{{page_content}}}
  </main>

  <footer>
    <p>© {{~CURRENT_YEAR}} SmartClinic | Gestión de citas médicas</p>
  </footer>

{{foreach EndScripts}}
  <script src="{{~BASE_DIR}}/{{this}}"></script>
{{endfor EndScripts}}
<script src="{{BASE_DIR}}/public/js/modals.js"></script>
<script src="{{BASE_DIR}}/public/js/smartclinic-confirm.js"></script>

</body>
</html>