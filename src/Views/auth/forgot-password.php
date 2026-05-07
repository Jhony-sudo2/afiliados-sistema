<h1>Recuperar contraseña</h1>
<p class="small">Ingresa tu correo y, si existe en el sistema, se enviará un enlace de recuperación.</p>

<form method="post" action="<?= app_url('forgot-password') ?>">    <?= csrf_field() ?>
    <label for="email">Correo</label>
    <input id="email" type="email" name="email" value="<?= e(old('email')) ?>" required>
    <div style="margin-top:16px; display:flex; gap:10px;">
        <button type="submit">Enviar enlace</button>
        <a class="btn-secondary btn" href="<?= app_url('login') ?>">Volver</a>
    </div>
</form>