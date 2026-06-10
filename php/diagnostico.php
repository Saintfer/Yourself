<?php
/**
 * Yourself · Diagnóstico de instalación (versión corregida)
 */

// Cargar configuración directamente desde el .env
$env_path = __DIR__ . '/../.env';
$env = [];
if (is_readable($env_path)) {
    foreach (file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) continue;
        [$k, $v] = explode('=', $linea, 2);
        $env[trim($k)] = trim($v);
    }
}

$DB_HOST = $env['DB_HOST'] ?? 'localhost';
$DB_PORT = (int)($env['DB_PORT'] ?? 3306);
$DB_USER = $env['DB_USER'] ?? 'root';
$DB_PASS = $env['DB_PASS'] ?? '';
$DB_NAME = $env['DB_NAME'] ?? 'yourself_db';
$GROQ_KEY   = $env['GROQ_API_KEY']   ?? '';
$GEMINI_KEY = $env['GEMINI_API_KEY'] ?? '';

function fila(string $nombre, bool $ok, string $detalle = ''): string {
    $icono = $ok ? '✅' : '❌';
    $clase = $ok ? 'ok' : 'fail';
    $det   = $detalle !== '' ? "<span class=\"det\">".htmlspecialchars($detalle)."</span>" : '';
    return "<tr class=\"$clase\"><td>$icono</td><td>".htmlspecialchars($nombre)."</td><td>$det</td></tr>";
}

$filas = [];

// PHP
$php_ok = version_compare(PHP_VERSION, '8.0.0', '>=');
$filas[] = fila('Versión de PHP (se requiere 8.0+)', $php_ok, 'Detectada: ' . PHP_VERSION);

// Extensiones
foreach (['mysqli' => 'Base de datos MySQL', 'curl' => 'Llamadas a la IA (Nix)', 'mbstring' => 'Texto en español/UTF-8'] as $ext => $para) {
    $ok = extension_loaded($ext);
    $filas[] = fila("Extensión $ext ($para)", $ok, $ok ? 'Activa' : 'Actívala en php.ini');
}

// .env
$env_ok = is_readable($env_path);
$filas[] = fila('Archivo .env', $env_ok, $env_ok ? 'Encontrado' : 'Copia .env.example como .env');

// Conexión MySQL (directo, sin require de conexion.php)
mysqli_report(MYSQLI_REPORT_OFF);
$db_ok = false;
$db_det = '';
$tablas_ok = false;
$tab_det = '';

$conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($conn->connect_error) {
    $db_det = 'No se pudo conectar: ' . $conn->connect_error;
    $tab_det = 'Sin conexión a la base de datos.';
} else {
    $db_ok  = true;
    $db_det = "Conectado a \"$DB_NAME\" en $DB_HOST:$DB_PORT";
    $requeridas = ['usuarios', 'diario', 'conversaciones', 'emociones'];
    $existentes = [];
    $res = $conn->query("SHOW TABLES");
    if ($res) while ($r = $res->fetch_array()) $existentes[] = $r[0];
    $faltan = array_diff($requeridas, $existentes);
    $tablas_ok = empty($faltan);
    $tab_det   = $tablas_ok ? 'Las 4 tablas existen' : 'Faltan: ' . implode(', ', $faltan) . ' — importa database/yourself_db.sql';
    $conn->close();
}
$filas[] = fila('Conexión a MySQL', $db_ok, $db_det);
$filas[] = fila('Tablas de la base de datos', $tablas_ok, $tab_det);

// IA
$groq_ok   = $GROQ_KEY   !== '' && $GROQ_KEY   !== 'tu_groq_key_aqui';
$gemini_ok = $GEMINI_KEY !== '' && $GEMINI_KEY !== 'tu_gemini_key_aqui';
$ia_ok  = $groq_ok || $gemini_ok;
$ia_det = $ia_ok
    ? 'API key configurada' . ($groq_ok ? ' · Groq ✓' : '') . ($gemini_ok ? ' · Gemini ✓' : '')
    : 'Sin API key: Nix funcionará en modo offline (respuestas predefinidas). Opcional.';
$filas[] = fila('Inteligencia artificial de Nix', $ia_ok, $ia_det);

$todo_ok = $php_ok && $db_ok && $tablas_ok && extension_loaded('mysqli');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Diagnóstico · Yourself</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',system-ui,sans-serif;background:#0e1019;color:#f0f0ff;
         min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
    .card{background:#181b2a;border:1px solid #272b45;border-radius:18px;
          padding:32px;max-width:680px;width:100%;box-shadow:0 16px 48px rgba(0,0,0,.5)}
    h1{font-size:1.5rem;margin-bottom:6px;
       background:linear-gradient(135deg,#7c3aed,#ec4899);-webkit-background-clip:text;
       -webkit-text-fill-color:transparent}
    .sub{color:#8b8fa8;font-size:.92rem;margin-bottom:22px}
    table{width:100%;border-collapse:collapse}
    td{padding:11px 8px;border-bottom:1px solid #272b45;vertical-align:top;font-size:.92rem}
    td:first-child{width:30px;text-align:center;font-size:1.1rem}
    .det{display:block;color:#8b8fa8;font-size:.82rem;margin-top:2px}
    .banner{margin-top:22px;padding:16px 18px;border-radius:12px;font-weight:600}
    .banner.ok{background:rgba(74,222,128,.12);border:1px solid rgba(74,222,128,.35);color:#86efac}
    .banner.no{background:rgba(248,113,113,.12);border:1px solid rgba(248,113,113,.35);color:#fca5a5}
    a{color:#9f5ff5;font-weight:700;text-decoration:none}
    .foot{margin-top:18px;font-size:.8rem;color:#8b8fa8;line-height:1.6}
  </style>
</head>
<body>
  <div class="card">
    <h1>🦉 Diagnóstico de Yourself</h1>
    <p class="sub">Verificación rápida de que el proyecto está listo para ejecutarse.</p>
    <table><?php foreach ($filas as $f) echo $f; ?></table>
    <?php if ($todo_ok): ?>
      <div class="banner ok">🎉 ¡Todo listo! <a href="../index.php">Ir a Yourself →</a></div>
    <?php else: ?>
      <div class="banner no">Aún faltan cosas. Revisa las filas con ❌ de arriba.</div>
    <?php endif; ?>
    <p class="foot">🔒 Recuerda borrar este archivo antes de entregar el proyecto.</p>
  </div>
</body>
</html>
