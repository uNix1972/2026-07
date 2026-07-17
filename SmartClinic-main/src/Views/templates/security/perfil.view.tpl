<style>
  .perfil-header {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 4px 20px rgba(92,64,51,0.08);
    padding: 1.6rem 1.8rem;
    margin-bottom: 1.5rem;
  }
  .perfil-header h2 {
    color: var(--cedro);
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 0.4rem;
  }
  .perfil-header p {
    color: #777;
    line-height: 1.6;
  }
  .perfil-card {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 4px 20px rgba(92,64,51,0.08);
    overflow: hidden;
    max-width: 480px;
  }
  .perfil-card .card-head {
    padding: 1rem 1.3rem;
    background: var(--cedro);
    color: #fff;
    font-weight: 700;
  }
  .perfil-card .card-body {
    padding: 1.3rem;
    display: grid;
    gap: 0.9rem;
  }
  .dato {
    padding-bottom: 0.85rem;
    border-bottom: 1px solid #f0ece8;
  }
  .dato span {
    display: block;
    font-size: 0.82rem;
    color: #777;
    margin-bottom: 0.2rem;
  }
  .dato strong {
    color: var(--cedro);
    font-size: 0.98rem;
  }
  .name-form {
    display: grid;
    gap: 0.55rem;
  }
  .name-form input {
    width: 100%;
    border: 1px solid #d9d1ca;
    border-radius: 10px;
    padding: 0.6rem 0.7rem;
    font-size: 0.95rem;
    color: #3c2d23;
  }
  .name-form button {
    border: none;
    background: #0b4bb8;
    color: #fff;
    font-weight: 700;
    border-radius: 10px;
    padding: 0.6rem 0.9rem;
    cursor: pointer;
  }
  .profile-msg-ok {
    background: #e6f4ea;
    color: #1f6f34;
    padding: 0.6rem 0.75rem;
    border-radius: 10px;
    font-size: 0.9rem;
  }
  .profile-msg-err {
    background: #fdecea;
    color: #b53325;
    padding: 0.6rem 0.75rem;
    border-radius: 10px;
    font-size: 0.9rem;
  }
</style>

<div class="perfil-header">
  <h2>Mi perfil</h2>
  <p>Desde aquí podés revisar y actualizar tu información personal.</p>
</div>

<section class="perfil-card">
  <div class="card-head">Información del usuario</div>
  <div class="card-body">
    {{if hasProfileSuccess}}
    <div class="profile-msg-ok">{{profileSuccess}}</div>
    {{endif hasProfileSuccess}}
    {{if hasProfileError}}
    <div class="profile-msg-err">{{profileError}}</div>
    {{endif hasProfileError}}
    <div class="dato">
      <span>Nombre</span>
      <form method="post" action="index.php?page=Security_Perfil" class="name-form">
        <input type="hidden" name="csrf_token" value="{{csrf_token}}">
        <input type="text" name="userNombre" value="{{userNombre}}" maxlength="80" required>
        <button type="submit">Guardar nombre</button>
      </form>
    </div>
    <div class="dato">
      <span>Email</span>
      <strong>{{userEmail}}</strong>
    </div>
    <div class="dato">
      <span>Estado</span>
      <strong>{{userStatus}}</strong>
    </div>
    <div class="dato">
      <span>Tipo</span>
      <strong>{{userTipo}}</strong>
    </div>
  </div>
</section>
