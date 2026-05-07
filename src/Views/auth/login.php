<div class="login-wrapper">

  <!-- Panel izquierdo: logo -->
  <div class="login-brand">
    <img src="<?= asset('images/Logo.png') ?>" alt="Logo empresa" class="login-logo">
  </div>

  <!-- Panel derecho: formulario -->
  <div class="login-form">
    <h1>Iniciar sesión</h1>

<form method="post" action="<?= app_url('login') ?>">      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="full">
          <label for="email">Correo</label>
          <input id="email" type="email" name="email" value="<?= e(old('email')) ?>" required>
        </div>
        <div class="full">
          <label for="password">Contraseña</label>
          <input id="password" type="password" name="password" required>
        </div>
        <div class="full">
          <button type="submit">Ingresar</button>
        </div>
      </div>
    </form>

    <p class="small" style="margin-top:16px;">
      <a href="<?= app_url('forgot-password') ?>">¿Olvidaste tu contraseña?</a>
    </p>
  </div>

</div>