<div class="auth-shell">
  <div class="auth-card">
    <h1>Recuperar contraseña</h1>
    <p>Flujo seguro con token temporal para demostración académica.</p>
    {{if msg}}<div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:12px; border-radius:12px; margin:12px 0;">{{msg}}</div>{{endif msg}}
    <form method="POST" action="index.php?page=PasswordRecoveryController&action=send" style="margin-bottom:18px;">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <div class="form-group"><label for="email_request">Correo registrado</label><input id="email_request" type="email" name="email" required value="{{email}}"></div>
      <button type="submit" class="btn btn--primary">Generar token</button>
    </form>
    <hr style="margin:18px 0; border:none; border-top:1px solid #E5E7EB;">
    <form method="POST" action="index.php?page=PasswordRecoveryController&action=reset">
      <input type="hidden" name="csrf_token" value="{{csrf_token}}">
      <div class="form-group"><label for="email_reset">Correo</label><input id="email_reset" type="email" name="email" required value="{{email}}"></div>
      <div class="form-group"><label for="token">Token</label><input id="token" name="token" required value="{{token}}"></div>
      <div class="form-group"><label for="password">Nueva contraseña</label><input id="password" type="password" name="password" minlength="8" required></div>
      <button type="submit" class="btn btn--primary">Cambiar contraseña</button>
    </form>
    <p style="margin-top:14px;"><a href="index.php?page=Sec_Login">Volver al login</a></p>
  </div>
</div>
