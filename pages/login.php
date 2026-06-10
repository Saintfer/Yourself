<?php
require_once __DIR__ . '/../php/conexion.php';
iniciarSesion();
if (isset($_SESSION['usuario_id'])) { header('Location: diario.php'); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF
    if (!csrfValidar($_POST['csrf'] ?? '')) {
        $error = '⚠️ Sesión expirada o token inválido. Recarga la página e intenta de nuevo.';
    }
    // Rate limit: máx 5 intentos cada 10 min
    elseif (!rateLimitLogin()) {
        $error = '🛡️ Demasiados intentos fallidos. Espera unos minutos antes de volver a intentar.';
    } else {
        $email    = trim($_POST['correo'] ?? '');
        $password = $_POST['contrasena'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $error = 'Ingresa un correo y contraseña válidos.';
        } else {
            try {
                $conn = conectar();
                $rows = queryDB($conn, 'SELECT id,nombre,email,password_hash FROM usuarios WHERE email=? LIMIT 1', [$email]);
                if (empty($rows) || !password_verify($password, $rows[0]['password_hash'])) {
                    $error = 'Correo o contraseña incorrectos.';
                } else {
                    $u = $rows[0];
                    // Regenerar el ID de sesión tras login (anti session-fixation)
                    session_regenerate_id(true);
                    rateLimitReset('login');
                    $_SESSION['usuario_id']     = (int)$u['id'];
                    $_SESSION['usuario_nombre'] = $u['nombre'];
                    $_SESSION['usuario_email']  = $u['email'];
                    $_SESSION['nombre']         = $u['nombre'];
                    queryDB($conn,'UPDATE usuarios SET ultimo_acceso=NOW() WHERE id=?',[(int)$u['id']],'ejecutar');
                    $conn->close();
                    header('Location: diario.php'); exit;
                }
                $conn->close();
            } catch(RuntimeException $e){ $error = 'Error de conexión. Intenta más tarde.'; }
        }
    }
}

$msg = $_GET['msg'] ?? '';
if ($msg === 'registro_ok')     $success = '✅ Cuenta creada correctamente. Ya puedes iniciar sesión.';
if ($msg === 'sesion_expirada') $error   = '⚠️ Tu sesión expiró. Inicia sesión nuevamente.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="description" content="Inicia sesión en Yourself – tu diario emocional.">
  <title>Iniciar sesión – Yourself</title>
  <link rel="icon" href="../img/nix-face.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/estilos.css">
  <style>

    /*Entrada escalonada de campos*/
    .input-group:nth-child(1) { animation: fadeInUp .4s ease .1s both }
    .input-group:nth-child(2) { animation: fadeInUp .4s ease .2s both }
    .form-submit              { animation: fadeInUp .4s ease .3s both }
  </style>
</head>
<body data-page="login">

<div class="auth-page">
  <div class="auth-bg"></div>
  <div class="auth-container" style="max-width:440px">

    <div class="auth-header animate-in">
      <div class="auth-owl">
        <img src="../img/nix-face.png" alt="Nix – Logo Yourself">
      </div>
      <h1 class="auth-title">Bienvenido de vuelta</h1>
      <p class="auth-subtitle">Inicia sesión para continuar con Yourself</p>
    </div>

    <div class="auth-card">
      <?php if($error):   ?><div class="alert alert-error"  >❌ <?= $error   ?></div><?php endif; ?>
      <?php if($success): ?><div class="alert alert-success">    <?= $success ?></div><?php endif; ?>

      <form method="POST" action="login.php" autocomplete="on">
        <?= csrfCampo() ?>
        <div class="input-group">
          <label for="correo">Correo electrónico</label>
          <input type="email" id="correo" name="correo" class="input-field"
                 placeholder="tu@email.com" required autocomplete="email"
                 value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
        </div>

        <div class="input-group">
          <label for="contrasena">Contraseña</label>
          <input type="password" id="contrasena" name="contrasena" class="input-field"
                 placeholder="••••••••" required autocomplete="current-password">
        </div>
        

        <div class="form-submit">
          <button type="submit" class="btn btn-primary btn-block btn-lg">
            Iniciar sesión →
          </button>
        </div>
      </form>
    </div>

    <p class="auth-footer-text">¿No tienes cuenta? <a href="registro.php">Regístrate gratis</a></p>
    <p class="auth-footer-text" style="margin-top:8px">
      <a href="../index.php" style="color:var(--text-muted);font-size:12px">← Volver al inicio</a>
    </p>

  </div>
</div>
</body>
</html>
