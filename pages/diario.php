<?php
require_once __DIR__ . '/../php/conexion.php';
verificarAuth();
$usuario_id     = getUsuarioId();
$usuario_nombre = getUsuarioNombre();
$usuario_letra  = strtoupper(substr($usuario_nombre, 0, 1));

$success_msg = '';
$error_msg   = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mood'])) {
    if (!csrfValidar($_POST['csrf'] ?? '')) {
        $error_msg = '⚠️ Sesión expirada. Recarga la página e intenta de nuevo.';
    } else {
        $mood  = limpiar($_POST['mood'] ?? '');
        $texto = limpiar($_POST['texto'] ?? '');
        $moods_validos = ['genial','bien','normal','triste','muymal'];
        if (in_array($mood, $moods_validos)) {
            try {
                $conn  = conectar();
                $fecha = date('Y-m-d');
                $existe = queryDB($conn,"SELECT id FROM diario WHERE usuario_id=? AND fecha=?",[$usuario_id,$fecha]);
                if (!empty($existe)) {
                    queryDB($conn,"UPDATE diario SET mood=?,texto=?,hora=NOW() WHERE usuario_id=? AND fecha=?",[$mood,$texto,$usuario_id,$fecha],'ejecutar');
                } else {
                    queryDB($conn,"INSERT INTO diario (usuario_id,mood,texto,fecha,hora) VALUES (?,?,?,?,NOW())",[$usuario_id,$mood,$texto,$fecha],'ejecutar');
                    queryDB($conn,"UPDATE usuarios SET dias_activos=dias_activos+1 WHERE id=?",[$usuario_id],'ejecutar');
                }
                $val = array_search($mood,['muymal','triste','normal','bien','genial'])+1;
                queryDB($conn,"INSERT INTO emociones (usuario_id,valor,mood_label,fecha) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE valor=?,mood_label=?",[$usuario_id,$val,$mood,$fecha,$val,$mood],'ejecutar');
                $conn->close();
                $success_msg = '¡Check-in guardado! Nix está orgulloso de ti 🦉';
            } catch(RuntimeException $e){ $error_msg='Error al guardar. Intenta de nuevo.'; }
        } else { $error_msg='Selecciona un estado de ánimo.'; }
    }
}


$stats         = ['checkins'=>0,'conversaciones'=>0,'dias_activos'=>0];
$entradas      = [];
$datos_grafica = [];
try {
    $conn = conectar();
    $row  = queryDB($conn,"SELECT dias_activos FROM usuarios WHERE id=?",[$usuario_id]);
    if($row){ $stats['dias_activos']=$row[0]['dias_activos']; }
    $stats['conversaciones'] = count(queryDB($conn,"SELECT DISTINCT sesion_id FROM conversaciones WHERE usuario_id=?",[$usuario_id]));
    $stats['checkins'] = count(queryDB($conn,"SELECT id FROM diario WHERE usuario_id=? AND fecha>=CURDATE()-INTERVAL 7 DAY",[$usuario_id]));
    $entradas = queryDB($conn,"SELECT mood,texto,fecha,hora FROM diario WHERE usuario_id=? ORDER BY fecha DESC,hora DESC LIMIT 10",[$usuario_id]);

    $nivel_map2 = ['genial'=>100,'bien'=>80,'normal'=>55,'triste'=>30,'muymal'=>10];
    $dias_es2   = ['Mon'=>'Lu','Tue'=>'Ma','Wed'=>'Mi','Thu'=>'Ju','Fri'=>'Vi','Sat'=>'Sa','Sun'=>'Do'];
    for($i=6;$i>=0;$i--){
        $f   = date('Y-m-d',strtotime("-$i days"));
        $key = date('D',strtotime("-$i days"));
        $rd  = queryDB($conn,'SELECT mood FROM diario WHERE usuario_id=? AND fecha=? LIMIT 1',[$usuario_id,$f]);
        if(!empty($rd)){
            $m = $rd[0]['mood'];
            $datos_grafica[] = ['dia'=>($dias_es2[$key]??$key),'mood'=>$m,'nivel'=>($nivel_map2[$m]??50),'empty'=>false];
        } else {
            $datos_grafica[] = ['dia'=>($dias_es2[$key]??$key),'mood'=>'','nivel'=>0,'empty'=>true];
        }
    }
    $conn->close();
} catch(RuntimeException $e){}


$mood_cfg = [
    'genial' => ['emoji'=>'😄','label'=>'Genial', 'color'=>'var(--mood-genial)','bar'=>'#4ade80'],
    'bien'   => ['emoji'=>'😊','label'=>'Bien',   'color'=>'var(--mood-bien)',  'bar'=>'#a78bfa'],
    'normal' => ['emoji'=>'😐','label'=>'Normal', 'color'=>'var(--mood-normal)','bar'=>'#94a3b8'],
    'triste' => ['emoji'=>'😢','label'=>'Triste', 'color'=>'var(--mood-triste)','bar'=>'#60a5fa'],
    'muymal' => ['emoji'=>'😭','label'=>'Muy mal','color'=>'var(--mood-muymal)','bar'=>'#f87171'],
];


$tips = [
    'Respirar profundo 4-7-8 ayuda a calmar el sistema nervioso. 🌬️ Inhala 4 s, aguanta 7 s, exhala 8 s.',
    'Escribir sobre lo que sientes, aunque sea una línea, ya es un gran paso. ✍️',
    'Salir a caminar 10 minutos puede cambiar tu estado de ánimo. 🌿',
    'Habla con alguien de confianza hoy. No tienes que cargar todo solo/a. 💬',
    'Tu progreso no siempre es visible, pero siempre está ocurriendo. 🌱',
    'Celebra los pequeños logros — levantarte ya es una victoria. 🏆',
    'Nix siempre está disponible si quieres conversar. ¡Saluda! 🦉',
];
$tip = $tips[date('N') - 1]; 

$today = fechaEspanol();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="description" content="Tu diario emocional personal en Yourself. Registra tu estado de ánimo hoy.">
  <title>Mi Diario · Yourself</title>
  <link rel="icon" href="../img/nix-face.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/estilos.css?v=<?= filemtime(__DIR__ . '/../css/estilos.css') ?>">
  <style>
    /* Gráfica semanal con colores por mood */
    .mood-chart {
      display:flex; align-items:flex-end; justify-content:space-between;
      gap:6px; height:130px; padding:0 4px;
      border-bottom:1px solid rgba(255,255,255,.06);
    }
    .mood-chart-bar-wrap {
      display:flex; flex-direction:column; align-items:center;
      justify-content:flex-end; gap:5px; flex:1; max-width:48px; height:100%;
    }
    .mood-chart-bar {
      width:100%; border-radius:6px 6px 0 0; min-height:3px;
      transition: height .55s cubic-bezier(.4,0,.2,1);
    }
    .mood-chart-bar.empty {
      background:rgba(255,255,255,.05); border-radius:4px; height:6px !important;
    }
    .mood-chart-bar:not(.empty):hover { filter:brightness(1.3); cursor:default }
    .mood-chart-label { font-size:10px; color:var(--text-muted); font-weight:700; white-space:nowrap }
  </style>
</head>
<body class="page-diario">

<input type="checkbox" id="navToggle" class="nav-toggle-cb" aria-hidden="true">
<label for="navToggle" id="sidebarOverlay" class="sidebar-overlay" aria-label="Cerrar menú"></label>
<div class="app-layout">


<aside class="sidebar">
  <div class="sidebar-header">
    <img src="../img/nix-face.png" alt="Nix">
    <span>Yourself</span>
  </div>
  <nav class="sidebar-nav">
    <p class="sidebar-section-title">Principal</p>
    <a href="diario.php"   class="sidebar-nav-item active"><span class="nav-icon">📓</span> Mi Diario</a>
    <a href="chat.php"     class="sidebar-nav-item"><span class="nav-icon">🦉</span> Hablar con Nix</a>
    <a href="progreso.php" class="sidebar-nav-item"><span class="nav-icon">📈</span> Mi Progreso</a>
    <p class="sidebar-section-title">Recursos</p>
    <a href="../index.php#ayuda-profesional" class="sidebar-nav-item"><span class="nav-icon">🤝</span> Ayuda Profesional</a>
    <a href="#emergencia"  class="sidebar-nav-item"><span class="nav-icon">🆘</span> Emergencia</a>
    <a href="../index.php"    class="sidebar-nav-item"><span class="nav-icon">🏠</span> Inicio</a>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar"><?= $usuario_letra ?></div>
      <div class="user-info">
        <div class="user-name"><?= limpiar($usuario_nombre) ?></div>
        <div class="user-status">● Activo</div>
      </div>
    </div>
    <a href="../php/logout.php" style="display:block;text-align:center;font-size:12px;color:var(--text-muted);padding:8px;margin-top:6px;border-radius:var(--radius-sm)">
      Cerrar sesión →
    </a>
    <form method="POST" action="../php/borrar_cuenta.php" onsubmit="return confirm('¿Estás seguro/a de que quieres borrar tu cuenta? Esta acción es irreversible y perderás todo tu diario, progreso y conversaciones con Nix.');" style="margin-top: 5px;">
      <?= csrfCampo() ?>
      <button type="submit" style="display:block; width: 100%; text-align:center; font-size:12px; color:#f87171; background: rgba(248,113,113,0.1); border: none; padding:8px; border-radius:var(--radius-sm); cursor: pointer; transition: background 0.2s;">
        🗑️ Borrar cuenta
      </button>
    </form>
  </div>
</aside>


<main class="main-content">
  <div class="topbar">
    <div class="topbar-left">
      <label for="navToggle" class="menu-btn" aria-label="Abrir menú">☰</label>
      <span class="topbar-title">Mi Diario</span>
    </div>
    <div class="topbar-actions">
      <span class="topbar-date"><?= $today ?></span>
      <a href="chat.php" class="btn btn-primary btn-sm">🦉 Hablar con Nix</a>
    </div>
  </div>

  <div class="content-area">

    <?php if($success_msg): ?><div class="alert alert-success">✅ <?= $success_msg ?></div><?php endif; ?>
    <?php if($error_msg):   ?><div class="alert alert-error"  >❌ <?= $error_msg   ?></div><?php endif; ?>

    
    <div class="greeting-banner">
      <div class="greeting-text">
        <h2 class="greeting-name">Hola, <?= limpiar($usuario_nombre) ?> 👋</h2>
        <p class="greeting-msg">¿Cómo te sientes hoy?</p>
      </div>
      <img src="../img/nix.png" alt="Nix" style="width:72px;height:72px;object-fit:contain;animation:float-slow 4s ease-in-out infinite;flex-shrink:0">
    </div>

    
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-card-label">Check-ins esta semana</div>
        <span class="stat-card-value gradient-text"><?= $stats['checkins'] ?></span>
      </div>
      <div class="stat-card">
        <div class="stat-card-label">Conversaciones con Nix</div>
        <span class="stat-card-value" style="color:var(--mood-bien)"><?= $stats['conversaciones'] ?></span>
      </div>
      <div class="stat-card">
        <div class="stat-card-label">Días activos</div>
        <span class="stat-card-value" style="color:var(--accent)"><?= $stats['dias_activos'] ?></span>
      </div>
    </div>

    
    <div class="tip-card">
      <div class="tip-card-icon">💡</div>
      <div class="tip-card-text">
        <strong>Consejo de Nix para hoy</strong>
        <?= $tip ?>
      </div>
    </div>

    
    <div class="checkin-card">
      <div class="checkin-header">
        <h3 class="checkin-title">📅 Check-in de hoy</h3>
        <span class="checkin-date"><?= date('d M Y') ?></span>
      </div>
      <form method="POST" action="diario.php">
        <?= csrfCampo() ?>
        <p style="font-size:13px;font-weight:600;color:var(--text-muted);margin-bottom:16px">¿Cómo te sientes?</p>
        <div class="mood-grid">
          <?php
          $moods_form = [
            ['key'=>'genial','emoji'=>'😄','label'=>'Genial'],
            ['key'=>'bien',  'emoji'=>'😊','label'=>'Bien'],
            ['key'=>'normal','emoji'=>'😐','label'=>'Normal'],
            ['key'=>'triste','emoji'=>'😢','label'=>'Triste'],
            ['key'=>'muymal','emoji'=>'😭','label'=>'Muy mal'],
          ];
          foreach($moods_form as $m): ?>
          <label class="mood-btn" title="<?= $m['label'] ?>">
            <input type="radio" name="mood" value="<?= $m['key'] ?>">
            <span class="mood-emoji"><?= $m['emoji'] ?></span>
            <span class="mood-label"><?= $m['label'] ?></span>
          </label>
          <?php endforeach; ?>
        </div>

        <div class="input-group">
          <label for="diario_text">¿Quieres compartir algo más? <span style="color:var(--text-muted);font-weight:400">(opcional)</span></label>
          <textarea id="diario_text" name="texto" class="input-field"
                    placeholder="Escribe aquí lo que sientes..." style="min-height:90px" maxlength="1000"></textarea>
        </div>


        <button type="submit" class="btn btn-primary btn-lg btn-block">💾 Guardar check-in</button>
      </form>
    </div>

    
    <div class="two-col">
      <a href="chat.php"     class="quick-action">
        <div class="quick-icon">🦉</div>
        <div><div class="quick-info-title">Habla con Nix</div><div class="quick-info-desc">Conversa sobre cómo te sientes</div></div>
      </a>
      <a href="progreso.php" class="quick-action">
        <div class="quick-icon">📈</div>
        <div><div class="quick-info-title">Ver mi progreso</div><div class="quick-info-desc">Revisa tu historial emocional</div></div>
      </a>
    </div>

    
    <div class="checkin-card">
      <h3 class="checkin-title" style="margin-bottom:18px">📊 Emociones esta semana</h3>
      <div class="mood-chart">
        <?php foreach($datos_grafica as $d):
          $barClass = $d['empty'] ? 'mood-chart-bar empty' : 'mood-chart-bar';
          $barColor = $d['empty'] ? '' : ($mood_cfg[$d['mood']]['bar'] ?? '#a78bfa');
          $barStyle = $d['empty'] ? '' : 'height:'.$d['nivel'].'%;background:'.$barColor;
          $barTitle = $d['empty'] ? $d['dia'].': Sin registro' : $d['dia'].': '.($mood_cfg[$d['mood']]['label'] ?? ucfirst($d['mood']));
        ?>
        <div class="mood-chart-bar-wrap">
          <div class="<?= $barClass ?>" style="<?= $barStyle ?>" title="<?= htmlspecialchars($barTitle) ?>"></div>
          <span class="mood-chart-label"><?= htmlspecialchars($d['dia']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    
    <?php if(!empty($entradas)): ?>
    <div style="margin-top:4px">
      <h3 style="font-size:17px;font-weight:700;margin-bottom:14px">📖 Entradas recientes</h3>
      <div class="entries-list">
        <?php foreach($entradas as $e):
          $cfg       = $mood_cfg[$e['mood']] ?? $mood_cfg['normal'];
          $fecha_fmt = date('d M Y', strtotime($e['fecha']));
        ?>
        <div class="entry-item mood-<?= $e['mood'] ?>">
          <span class="entry-mood"><?= $cfg['emoji'] ?></span>
          <div class="entry-content">
            <div class="entry-header">
              <span class="entry-title"><?= $cfg['label'] ?></span>
              <span class="entry-date"><?= $fecha_fmt ?></span>
            </div>
            <div class="entry-preview"><?= $e['texto'] ? limpiar($e['texto']) : '<em style="opacity:.6">Sin nota adicional</em>' ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    
    <div id="emergencia" class="warning-card" style="margin-top:30px">
      <strong>🆘 ¿Necesitas ayuda urgente?</strong><br>
      Llama a la <strong>Línea 106</strong> (Jóvenes) o la <strong>Línea 123</strong> (Emergencias).
      Yourself no reemplaza la ayuda profesional.
      <div style="margin-top: 14px;">
        <a href="../index.php#recursos-salud-mental" class="btn btn-outline btn-sm" style="background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.15); color: inherit;">
          Ver todos los recursos de salud mental
        </a>
      </div>
    </div>

  </div>
</main>
</div>
</body>
</html>
