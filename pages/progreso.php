<?php
require_once __DIR__ . '/../php/conexion.php';
verificarAuth(false);
iniciarSesion();
if (!isset($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }

$usuario_id = (int)$_SESSION['usuario_id'];
$nombre     = getUsuarioNombre();
$inicial    = mb_strtoupper(mb_substr($nombre, 0, 1));


$estados = [
    'genial' => ['emoji'=>'😄','label'=>'Genial', 'color'=>'#4ade80'],
    'bien'   => ['emoji'=>'😊','label'=>'Bien',   'color'=>'#a78bfa'],
    'normal' => ['emoji'=>'😐','label'=>'Normal', 'color'=>'#94a3b8'],
    'triste' => ['emoji'=>'😔','label'=>'Triste', 'color'=>'#60a5fa'],
    'muymal' => ['emoji'=>'😢','label'=>'Muy mal','color'=>'#f87171'],
];
$nivel_map = ['genial'=>100,'bien'=>75,'normal'=>50,'triste'=>30,'muymal'=>10];


$cal_year         = (int)date('Y');
$cal_month        = (int)date('m');
$cal_days         = (int)date('t');
$totales          = ['checkins'=>0,'conversaciones'=>0,'dias'=>0];
$dist_map         = [];
$cal_data         = [];
$racha            = 0;
$dias_con_registro = 0;

try {
    $conn = conectar();
    $r1 = queryDB($conn,'SELECT COUNT(*) AS total FROM diario WHERE usuario_id=?',[$usuario_id]);
    $r2 = queryDB($conn,'SELECT COUNT(*) AS total FROM conversaciones WHERE usuario_id=? AND rol=?',[$usuario_id,'assistant']);
    $r3 = queryDB($conn,'SELECT COUNT(DISTINCT fecha) AS total FROM diario WHERE usuario_id=?',[$usuario_id]);
    $totales['checkins']       = (int)($r1[0]['total'] ?? 0);
    $totales['conversaciones'] = (int)($r2[0]['total'] ?? 0);
    $totales['dias']           = (int)($r3[0]['total'] ?? 0);

    
    $dist = queryDB($conn,'SELECT mood,COUNT(*) AS total FROM diario WHERE usuario_id=? GROUP BY mood ORDER BY total DESC',[$usuario_id]);
    foreach($dist as $d){ $dist_map[$d['mood']] = (int)$d['total']; }

    
    $mes_ini = sprintf('%04d-%02d-01', $cal_year, $cal_month);
    $mes_fin = sprintf('%04d-%02d-%02d', $cal_year, $cal_month, $cal_days);
    $rows = queryDB($conn,'SELECT fecha,mood FROM diario WHERE usuario_id=? AND fecha BETWEEN ? AND ? ORDER BY fecha',[$usuario_id,$mes_ini,$mes_fin]);
    foreach($rows as $row){ $cal_data[$row['fecha']] = $row['mood']; }
    $dias_con_registro = count($cal_data);

    
    $fecha_check = date('Y-m-d');
    while(true){
        $tiene = queryDB($conn,'SELECT id FROM diario WHERE usuario_id=? AND fecha=? LIMIT 1',[$usuario_id,$fecha_check]);
        if(empty($tiene)) break;
        $racha++;
        $fecha_check = date('Y-m-d', strtotime($fecha_check.' -1 day'));
    }
    $conn->close();
} catch(RuntimeException $e){}

$max_dist = max(array_values($dist_map) ?: [1]);


$first_dow_raw = (int)date('w', mktime(0,0,0,$cal_month,1,$cal_year));
$first_dow     = ($first_dow_raw === 0) ? 6 : $first_dow_raw - 1;
$mes_nombres   = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$today_str     = date('Y-m-d');


$mood_top_key  = !empty($dist_map) ? array_key_first($dist_map) : null;
$mood_top_info = $mood_top_key ? ($estados[$mood_top_key] ?? $estados['normal']) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="description" content="Visualiza tu progreso emocional en Yourself. Calendario mensual y estadísticas.">
  <title>Mi Progreso – Yourself</title>
  <link rel="icon" href="../img/nix-face.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/estilos.css">
</head>
<body class="page-progreso">

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
    <a href="diario.php"   class="sidebar-nav-item"><span class="nav-icon">📓</span> Mi Diario</a>
    <a href="chat.php"     class="sidebar-nav-item"><span class="nav-icon">🦉</span> Hablar con Nix</a>
    <a href="progreso.php" class="sidebar-nav-item active"><span class="nav-icon">📈</span> Mi Progreso</a>
    <p class="sidebar-section-title">Recursos</p>
    <a href="diario.php#emergencia" class="sidebar-nav-item"><span class="nav-icon">🆘</span> Emergencia</a>
    <a href="../index.php"    class="sidebar-nav-item"><span class="nav-icon">🏠</span> Inicio</a>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar"><?= htmlspecialchars($inicial) ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($nombre) ?></div>
        <div class="user-status">● Activo</div>
      </div>
    </div>
    <a href="../php/logout.php" style="display:block;text-align:center;font-size:12px;color:var(--text-muted);padding:8px;margin-top:6px;border-radius:var(--radius-sm)">
      Cerrar sesión →
    </a>
  </div>
</aside>


<main class="main-content">
  <div class="topbar">
    <div class="topbar-left">
      <label for="navToggle" class="menu-btn" aria-label="Abrir menú">☰</label>
      <span class="topbar-title">Mi Progreso</span>
    </div>
    <div class="topbar-actions">
      <span class="topbar-date"><?= date('l, j \d\e F \d\e Y') ?></span>
      <a href="chat.php" class="btn btn-primary btn-sm">🦉 Hablar con Nix</a>
    </div>
  </div>

  <div class="content-area">

    
    <div class="greeting-banner" style="margin-bottom:22px">
      <div class="greeting-text">
        <h2 class="greeting-name">Mi Progreso 📈</h2>
        <p class="greeting-msg">Visualiza cómo ha evolucionado tu bienestar emocional</p>
      </div>
      <img src="../img/nix.png" alt="Nix" style="width:68px;height:68px;object-fit:contain;animation:float-slow 4s ease-in-out infinite;flex-shrink:0">
    </div>

    
    <div class="stats-row" style="grid-template-columns:repeat(4,1fr);margin-bottom:22px">
      <div class="stat-card">
        <div class="stat-label">Check-ins totales</div>
        <div class="stat-value gradient-text"><?= $totales['checkins'] ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Respuestas de Nix</div>
        <div class="stat-value" style="color:var(--mood-bien)"><?= $totales['conversaciones'] ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Días con registro</div>
        <div class="stat-value" style="color:var(--mood-triste)"><?= $totales['dias'] ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Racha actual</div>
        <div class="stat-value" style="color:var(--mood-genial)"><?= $racha ?>🔥</div>
      </div>
    </div>

    
    <div class="card" style="margin-bottom:18px">
      <div class="card-title">
        <span class="icon">📅</span>
        Calendario emocional – <?= $mes_nombres[$cal_month-1].' '.$cal_year ?>
        <span style="margin-left:auto;font-size:.78rem;font-weight:600;color:var(--text-muted)"><?= $dias_con_registro ?> días registrados este mes</span>
      </div>
      <div class="mood-calendar">
        <div class="cal-grid">
          <?php foreach(['Lu','Ma','Mi','Ju','Vi','Sá','Do'] as $dn): ?>
            <div class="cal-day-name"><?= $dn ?></div>
          <?php endforeach; ?>

          <?php for($b=0;$b<$first_dow;$b++): ?>
            <div class="cal-cell empty"></div>
          <?php endfor; ?>

          <?php for($d=1;$d<=$cal_days;$d++):
            $date_str = sprintf('%04d-%02d-%02d',$cal_year,$cal_month,$d);
            $mood     = $cal_data[$date_str] ?? null;
            $is_today = ($date_str === $today_str);
            $classes  = 'cal-cell';
            if($mood)     $classes .= ' has-mood mood-'.$mood;
            if($is_today) $classes .= ' today';
            $emoji = $mood ? ($estados[$mood]['emoji'] ?? '') : '';
            $title = $mood ? ($estados[$mood]['label'] ?? $mood) : '';
          ?>
          <div class="<?= $classes ?>"
               <?= $mood ? 'onclick="location.href=\'diario.php\'"' : '' ?>
               <?= $title ? 'title="'.$title.'"' : '' ?>>
            <span class="cal-num"><?= $d ?></span>
            <?php if($emoji): ?><span class="cal-emoji"><?= $emoji ?></span><?php endif; ?>
          </div>
          <?php endfor; ?>
        </div>

        
        <div class="cal-legend">
          <?php foreach($estados as $key=>$info): ?>
          <div class="cal-legend-item">
            <div class="cal-legend-dot" style="background:<?= $info['color'] ?>"></div>
            <?= $info['emoji'] ?> <?= $info['label'] ?>
          </div>
          <?php endforeach; ?>
          <div class="cal-legend-item">
            <div class="cal-legend-dot" style="background:rgba(124,58,237,.35);border:1.5px solid var(--primary)"></div>
            Hoy
          </div>
        </div>
      </div>
    </div>

    
    <div class="progress-grid">

      
      <div class="card">
        <div class="card-title"><span class="icon">🎭</span> Mis emociones frecuentes</div>
        <?php if(empty($dist_map)): ?>
          <p style="text-align:center;color:var(--text-muted);padding:2rem 0">🌱 Aún no hay datos suficientes.</p>
        <?php else: ?>
          <div class="mood-history-chart">
            <?php foreach($estados as $key=>$info):
              $count = $dist_map[$key] ?? 0;
              $pct   = $max_dist > 0 ? round(($count/$max_dist)*100) : 0;
            ?>
            <div class="mood-bar-item">
              <span class="mood-bar-label"><?= $info['emoji'] ?> <?= $info['label'] ?></span>
              <div class="mood-bar-track">
                <div class="mood-bar-fill" style="width:<?= $pct ?>%;background:<?= $info['color'] ?>"></div>
              </div>
              <span class="mood-bar-count"><?= $count ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      
      <div class="card">
        <div class="card-title"><span class="icon">🦉</span> Insight de Nix</div>
        <div style="display:flex;flex-direction:column;gap:12px">
          <?php if(empty($dist_map)): ?>
            <div class="insight-card">
              Empieza a registrar cómo te sientes para que Nix pueda mostrarte tendencias útiles.
            </div>
          <?php else: ?>
            <div class="insight-card">
              Tu emoción más frecuente ha sido
              <strong><?= $mood_top_info['label'] ?> <?= $mood_top_info['emoji'] ?></strong>.
              <?php if($mood_top_key === 'genial' || $mood_top_key === 'bien'): ?>
                ¡Eso es fantástico! Sigue así 🌟
              <?php elseif($mood_top_key === 'triste' || $mood_top_key === 'muymal'): ?>
                Recuerda que Nix siempre está aquí para escucharte 💜
              <?php else: ?>
                La constancia en el registro te ayudará a entender mejor tus patrones 🌱
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if($racha >= 3): ?>
          <div class="insight-card" style="border-left-color:var(--mood-genial)">
            🔥 ¡Llevas <strong><?= $racha ?> días</strong> consecutivos registrando! Eso es una racha increíble.
          </div>
          <?php else: ?>
          <div class="insight-card" style="border-left-color:var(--mood-bien)">
            🔁 Entre más constante seas con tus registros, mejor podrás entender tus patrones emocionales.
          </div>
          <?php endif; ?>

          <a href="diario.php" class="btn btn-outline btn-sm" style="width:100%;margin-top:4px">
            ✍️ Hacer check-in de hoy
          </a>
        </div>
      </div>

    </div>

  </div>
</main>
</div>

</body>
</html>
