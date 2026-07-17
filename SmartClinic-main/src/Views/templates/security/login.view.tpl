<section class="auth-page">
  <div class="auth-card">
    <a href="index.php?page=Landing" class="auth-brand">
      <span class="auth-brand-row">
        <img src="{{~BASE_DIR}}/public/img/logo.png" alt="" width="48" height="48" class="auth-brand-icon" />
        <span class="auth-brand-name">Smart<span class="auth-brand-accent">Clinic</span></span>
      </span>
      <span class="auth-brand-tag">Sistema de gestión de citas médicas</span>
    </a>
    <h1>Iniciar sesión</h1>

    <form class="auth-form" method="post"
      action="index.php?page=Sec_Login{{if redirto}}&redirto={{redirto}}{{endif redirto}}" novalidate>
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <div class="form-group">
        <label for="txtEmail">Correo electrónico</label>
        <input class="input" type="email" id="txtEmail" name="txtEmail" value="{{txtEmail}}" required autocomplete="email"
          placeholder="usuario@dominio.com" />
        {{if errorEmail}}
        <div class="error">{{errorEmail}}</div>
        {{endif errorEmail}}
      </div>

      <div class="form-group">
        <label for="txtPswd">Contraseña</label>
        <input class="input" type="password" id="txtPswd" name="txtPswd" value="{{txtPswd}}" required
          autocomplete="current-password" placeholder="********" />
        {{if errorPswd}}
        <div class="error">{{errorPswd}}</div>
        {{endif errorPswd}}
      </div>

      {{if generalError}}
      <div class="error general-error">{{generalError}}</div>
      {{endif generalError}}

      <div class="actions">
        <button class="btn-primary" id="btnLogin" type="submit">Iniciar sesión</button>
      </div>

      <p class="note"><a href="index.php?page=PasswordRecoveryController">¿Olvidó su contraseña?</a></p>
    </form>
  </div>

  <a href="index.php?page=Landing" class="auth-back">← Volver al inicio</a>
</section>
