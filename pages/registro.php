<?php

require_once __DIR__ . '/../php/conexion.php';
iniciarSesion();
if (isset($_SESSION['usuario_id'])) { header('Location: diario.php'); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF
    if (!csrfValidar($_POST['csrf'] ?? '')) {
        $error = '⚠️ Sesión expirada. Recarga la página e intenta de nuevo.';
    } else {
        $nombre   = limpiar($_POST['nombre']   ?? '');
        $email    = limpiar($_POST['email']    ?? '');
        $telefono = limpiar($_POST['telefono'] ?? '');
        $password = $_POST['password']         ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if      (empty($nombre) || mb_strlen($nombre) < 2)            { $error = 'Ingresa tu nombre completo (mínimo 2 caracteres).'; }
        elseif  (!filter_var($email, FILTER_VALIDATE_EMAIL))           { $error = 'El correo no tiene un formato válido.'; }
        elseif  (!empty($telefono) && !preg_match('/^\d{7,15}$/', $telefono)) { $error = 'El número de celular no es válido.'; }
        elseif  (mb_strlen($password) < 8)                             { $error = 'La contraseña debe tener al menos 8 caracteres.'; }
        elseif  ($password !== $confirm)                               { $error = 'Las contraseñas no coinciden.'; }
        else {
            try {
                $conn   = conectar();
                $existe = queryDB($conn, 'SELECT id FROM usuarios WHERE email=?', [$email]);
                if (!empty($existe)) {
                    $error = 'Este correo ya está registrado.';
                } else {
                    $hash  = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $letra = mb_strtoupper(mb_substr($nombre, 0, 1));
                    $ok    = queryDB($conn, 'INSERT INTO usuarios (nombre,email,telefono,password_hash,avatar_letra) VALUES (?,?,?,?,?)',
                                     [$nombre, $email, $telefono ?: null, $hash, $letra], 'ejecutar');
                    $conn->close();
                    if ($ok) { header('Location: login.php?msg=registro_ok'); exit(); }
                    else     { $error = 'Error al crear la cuenta. Intenta de nuevo.'; }
                }
            } catch (RuntimeException $e) { $error = 'Error de conexión. Intenta más tarde.'; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="description" content="Crea tu cuenta en Yourself – empieza tu diario emocional hoy.">
  <title>Crear cuenta · Yourself</title>
  <link rel="icon" href="../img/nix-face.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/estilos.css">
  <style>
    /* Animaciones escalonadas de campos */
    .input-group:nth-child(1) { animation: fadeInUp .4s ease .05s both }
    .input-group:nth-child(2) { animation: fadeInUp .4s ease .12s both }
    .input-group:nth-child(3) { animation: fadeInUp .4s ease .19s both }
    .input-group:nth-child(4) { animation: fadeInUp .4s ease .26s both }
    .input-group:nth-child(5) { animation: fadeInUp .4s ease .33s both }
    .privacy-note             { animation: fadeInUp .4s ease .38s both }
    .form-submit              { animation: fadeInUp .4s ease .43s both }
  </style>
</head>
<body class="page-registro" data-page="registro">

<div class="auth-page">
  <div class="auth-bg"></div>
  <div class="auth-container" style="max-width:480px">

    <div class="auth-header animate-in">
      <div class="auth-owl">
        <img src="../img/nix-face.png" alt="Nix – Yourself">
      </div>
      <h1 class="auth-title">Crea tu cuenta</h1>
      <p class="auth-subtitle">Únete a Yourself y empieza tu journey ✨</p>
    </div>

    <div class="auth-card">
      <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= $error ?></div>
      <?php endif; ?>

      <form id="registroForm" method="POST" action="registro.php" novalidate>
        <?= csrfCampo() ?>

        <div class="input-group">
          <label for="nombre">Nombre completo</label>
          <input type="text" id="nombre" name="nombre" class="input-field"
                 placeholder="Tu nombre" required
                 value="<?= limpiar($_POST['nombre'] ?? '') ?>">
        </div>

        <div class="input-group">
          <label for="email">Correo electrónico</label>
          <input type="email" id="email" name="email" class="input-field"
                 placeholder="tu@email.com" required
                 value="<?= limpiar($_POST['email'] ?? '') ?>">
        </div>

        <div class="input-group">
          <label for="telefono">Celular <span style="color:var(--text-muted);font-weight:400">(opcional)</span></label>
          <input type="tel" id="telefono" name="telefono" class="input-field"
                 placeholder="3001234567"
                 value="<?= limpiar($_POST['telefono'] ?? '') ?>">
        </div>

        <div class="input-group">
          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" class="input-field"
                 placeholder="Mínimo 8 caracteres" required>
        </div>

        <div class="input-group">
          <label for="confirm_password">Confirmar contraseña</label>
          <input type="password" id="confirm_password" name="confirm_password" class="input-field"
                 placeholder="Repite tu contraseña" required>
        </div>

        <div class="privacy-note" style="background:rgba(124,58,237,.07);border:1px solid rgba(124,58,237,.2);border-radius:var(--radius-md);padding:12px 16px;font-size:12px;color:var(--text-muted);margin-bottom:20px">
          🔒 Tus datos están protegidos. Nunca compartiremos tu información.
        </div>

        <div class="form-submit">
          <button type="submit" class="btn btn-primary btn-block btn-lg">
            Crear cuenta gratis ✨
          </button>
        </div>
      </form>
    </div>

    <p class="auth-footer-text">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
    <p class="auth-footer-text" style="margin-top:8px">
      <a href="../index.php" style="color:var(--text-muted);font-size:12px">← Volver al inicio</a>
    </p>

  </div>
</div>

</body>
</html>
