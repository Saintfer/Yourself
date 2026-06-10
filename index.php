<?php
session_start();
$logueado = isset($_SESSION['usuario_id']);
$nombre   = $_SESSION['nombre'] ?? '';

require_once __DIR__ . '/php/crisis_config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Yourself – Tu diario emocional. Registra cómo te sientes, habla con Nix y visualiza tu bienestar. ODS 3: Salud y Bienestar.">
  <title>Yourself – Tu diario que te escucha</title>
  <link rel="icon" href="img/nix-face.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/estilos.css?v=<?= filemtime(__DIR__ . '/css/estilos.css') ?>">
</head>
<body data-page="home">


<nav class="navbar">
  <a class="navbar-brand" href="index.php">
    <img src="img/nix-face.png" alt="Yourself logo">
    <span>Yourself</span>
  </a>
  <input type="checkbox" id="navMenuToggle" class="nav-menu-cb" aria-hidden="true">
  <ul class="navbar-links" id="nav-links">
    <li><a href="index.php" class="active">Inicio</a></li>
    <li><a href="#features">Características</a></li>
    <li><a href="#ayuda-profesional">Ayuda</a></li>
    <?php if ($logueado): ?>
      <li><a href="pages/diario.php">Mi Diario</a></li>
      <li><a href="pages/chat.php">Hablar con Nix</a></li>
    <?php endif; ?>
  </ul>
  <div class="navbar-actions">
    <?php if ($logueado): ?>
      <span style="color:var(--text-l);font-size:.9rem;font-weight:600">Hola, <?= htmlspecialchars($nombre) ?> 👋</span>
      <a href="php/logout.php" class="btn btn-outline btn-sm">Salir</a>
    <?php else: ?>
      <a href="pages/login.php"   class="btn btn-outline btn-sm">Iniciar sesión</a>
      <a href="pages/registro.php" class="btn btn-primary btn-sm">Comenzar gratis</a>
    <?php endif; ?>
  </div>
  <label for="navMenuToggle" class="navbar-burger" aria-label="Abrir menú">☰</label>
</nav>


<section class="hero">
  <div class="hero-content">
    <div class="hero-top-wrapper">
      <img class="hero-logo animate-in" src="img/nix.png" alt="Nix el búho de Yourself">
      <div class="hero-badge animate-in delay-1">🌿 ODS 3 · Salud y Bienestar</div>
    </div>
    <h1 class="animate-in delay-2"><span>Yourself</span></h1>
    <p class="hero-sub animate-in delay-3">Tu diario que te escucha</p>
    <p class="hero-desc animate-in delay-4">
      Un espacio seguro para expresar tus emociones. Nix, tu compañero búho,
      te acompaña en tu bienestar emocional día a día — sin juicios, siempre disponible.
    </p>
    <div class="hero-actions animate-in delay-5">
      <?php if ($logueado): ?>
        <a href="pages/diario.php" class="btn btn-primary btn-lg">📝 Ir a mi diario</a>
        <a href="pages/chat.php"   class="btn btn-outline btn-lg">💬 Hablar con Nix</a>
      <?php else: ?>
        <a href="pages/registro.php" class="btn btn-primary btn-lg">Empieza ahora ✨</a>
        <a href="#features"    class="btn btn-outline btn-lg">Conoce más</a>
      <?php endif; ?>
    </div>
  </div>
</section>


<section class="features" id="features">
  <h2 class="section-title">¿Cómo te ayuda <span>Yourself</span>?</h2>
  <p class="section-sub">Diseñado especialmente para jóvenes que buscan apoyo emocional accesible y sin barreras</p>
  <div class="features-grid">
    <div class="feature-card animate-in">
      <span class="feature-icon">📝</span>
      <h3>Check-ins Diarios</h3>
      <p>Registra cómo te sientes cada día. Selecciona tu estado de ánimo y escribe lo que llevas en el corazón.</p>
    </div>
    <div class="feature-card animate-in delay-1">
      <span class="feature-icon">💬</span>
      <h3>Conversación con Nix</h3>
      <p>Habla con Nix cuando lo necesites. Tu asistente virtual está siempre disponible, sin juicios y con empatía.</p>
    </div>
    <div class="feature-card animate-in delay-2">
      <span class="feature-icon">📅</span>
      <h3>Calendario Emocional</h3>
      <p>Visualiza tu bienestar mes a mes. Cada día registrado muestra tu estado de ánimo con colores y emojis.</p>
    </div>
    <div class="feature-card animate-in delay-1">
      <span class="feature-icon">💜</span>
      <h3>Espacio Seguro</h3>
      <p>Tus emociones y conversaciones son completamente privadas. Nadie más tiene acceso a tu diario personal.</p>
    </div>
    <div class="feature-card animate-in delay-2">
      <span class="feature-icon">🛡️</span>
      <h3>Recursos de Ayuda</h3>
      <p>Accede a contactos de emergencia y apoyo profesional directamente desde tu diario cuando los necesites.</p>
    </div>
    <div class="feature-card animate-in delay-3">
      <span class="feature-icon">🌱</span>
      <h3>Asistente Empático</h3>
      <p>Nix te acompaña con respuestas cálidas. No es un psicólogo, pero siempre está ahí para escucharte.</p>
    </div>
  </div>

  <div class="aviso-card" style="max-width:700px;margin:44px auto 0">
    <strong>⚠️ Importante:</strong> Yourself no reemplaza la ayuda profesional.
    Si estás en crisis, contacta a un adulto de confianza o llama a la
    <strong>Línea 106 (Jóvenes)</strong> o la <strong>Línea Nacional 123</strong>.
  </div>
</section>


<section class="help-section" id="ayuda-profesional">
  <h2 class="section-title">Tu <span class="gradient-text">bienestar</span> importa</h2>
  <p class="section-sub text-muted">
    Nix es un asistente diseñado para escucharte y acompañarte en tu día a día, pero <strong>no reemplaza la atención de un profesional de la salud mental.</strong>
    Si sientes que no puedes más, buscar ayuda profesional es la decisión más valiente que puedes tomar.
  </p>

  <div class="help-cards-grid">
    <div class="help-card">
      <span class="help-card-icon">🧠</span>
      <h3>Escucha Profesional</h3>
      <p>Un psicólogo puede darte herramientas reales y personalizadas para superar lo que sientes.</p>
    </div>
    <div class="help-card delay-1">
      <span class="help-card-icon">🛡️</span>
      <h3>Espacio Seguro</h3>
      <p>Las terapias son totalmente confidenciales. Es un lugar donde puedes ser tú mismo/a sin prejuicios.</p>
    </div>
    <div class="help-card delay-2">
      <span class="help-card-icon">🏥</span>
      <h3>Recursos a tu alcance</h3>
      <p>Existen líneas gratuitas, fundaciones y centros dispuestos a apoyarte en todo momento.</p>
    </div>
  </div>

  <div class="help-actions" style="position: relative; z-index: 10;">
    <?php $btnBuscar = YOURSELF_HELP_LINKS['buscar_profesional']; ?>
    <a href="<?= htmlspecialchars($btnBuscar['url']) ?>" class="btn btn-primary btn-lg" target="_blank" rel="noopener noreferrer">
      <?= $btnBuscar['icon'] ?> <?= htmlspecialchars($btnBuscar['label']) ?>
    </a>
    <?php $btnRecursos = YOURSELF_HELP_LINKS['recursos_salud']; ?>
    <a href="<?= htmlspecialchars($btnRecursos['url']) ?>" class="btn btn-outline btn-lg">
      <?= $btnRecursos['icon'] ?> <?= htmlspecialchars($btnRecursos['label']) ?>
    </a>
  </div>
</section>


<section class="resources-section" id="recursos-salud-mental">
  <h2 class="section-title">Recursos de <span class="gradient-text">Salud Mental</span></h2>
  <p class="section-sub text-muted">Directorio de líneas de atención, centros de apoyo y organizaciones dispuestas a ayudarte cuando más lo necesitas.</p>
  
  <div class="resources-grid">
    <?= renderCrisisResourcesHTML() ?>
  </div>
</section>


<footer class="footer">
  <div class="footer-bottom">
    Hecho con <span class="heart">❤️</span> por Natalia y Juan Fernando —
    Proyecto Colegio Comfandi 11.1 · 2024–2026
  </div>
</footer>

</body>
</html>
