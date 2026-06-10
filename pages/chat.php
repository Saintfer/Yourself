<?php

require_once __DIR__ . '/../php/conexion.php';
require_once __DIR__ . '/../php/nix_ai.php';
require_once __DIR__ . '/../php/crisis_detector.php';

verificarAuth();
$usuario_id     = getUsuarioId();
$usuario_nombre = getUsuarioNombre();
$usuario_letra  = strtoupper(substr($usuario_nombre, 0, 1));


$sesion_id = trim($_GET['sesion'] ?? '');
if ($sesion_id === '' || !preg_match('/^[a-f0-9]{32}$/', $sesion_id)) {
    $sesion_id = bin2hex(random_bytes(16));
    header("Location: chat.php?sesion=$sesion_id");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $es_ajax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

    // Protección CSRF
    if (!csrfValidar($_POST['csrf'] ?? '')) {
        if ($es_ajax) { responderJSON(['error' => 'Sesión expirada. Recarga la página.'], 403); }
        else { header("Location: chat.php?sesion=$sesion_id"); exit; }
    }
    // Rate limit: máx 20 mensajes por minuto por usuario
    if (!rateLimitChat($usuario_id)) {
        if ($es_ajax) { responderJSON(['error' => 'Estás enviando mensajes muy rápido.'], 429); }
        else { header("Location: chat.php?sesion=$sesion_id&aviso=ritmo"); exit; }
    }

    $sesion_post = trim($_POST['sesion'] ?? '');
    $mensaje     = trim($_POST['mensaje'] ?? '');
    $mensaje     = mb_substr($mensaje, 0, 500);

    if ($mensaje !== '' && preg_match('/^[a-f0-9]{32}$/', $sesion_post)) {
        try {
            $conn = conectar();
            $hist_rows = queryDB($conn,
                "SELECT rol, mensaje FROM conversaciones
                  WHERE usuario_id=? AND sesion_id=?
                  ORDER BY fecha_hora ASC LIMIT 20",
                [$usuario_id, $sesion_post]);

            queryDB($conn, 'INSERT INTO conversaciones (usuario_id, sesion_id, rol, mensaje) VALUES (?,?,?,?)',
                [$usuario_id, $sesion_post, 'user', $mensaje], 'ejecutar');
            
            // Liberar la sesión rápido para que otras pestañas no se bloqueen
            session_write_close();

            $respuesta_texto = nixResponder($mensaje, $hist_rows);

            // Analizar crisis en el backend
            $analisis_crisis = detectarCrisis($mensaje);
            if ($analisis_crisis['is_crisis']) {
                $respuesta_texto .= "\n\n" . getMensajeCrisis($analisis_crisis['level']);
            }

            queryDB($conn, 'INSERT INTO conversaciones (usuario_id, sesion_id, rol, mensaje) VALUES (?,?,?,?)',
                [$usuario_id, $sesion_post, 'assistant', $respuesta_texto], 'ejecutar');
            $conn->close();

            if ($es_ajax) {
                responderJSON([
                    'status' => 'ok',
                    'respuesta' => nl2br(limpiar($respuesta_texto)),
                    'hora' => date('H:i'),
                    'crisis' => $analisis_crisis['is_crisis'],
                    'crisis_level' => $analisis_crisis['level']
                ]);
            }
        } catch (RuntimeException $e) {
            error_log('Chat error: ' . $e->getMessage());
            if ($es_ajax) { responderJSON(['error' => 'Error al procesar el mensaje.'], 500); }
        }
    }
    
    if ($es_ajax) { responderJSON(['error' => 'Petición inválida.'], 400); }
    header("Location: chat.php?sesion=$sesion_post");
    exit;
}


$historial      = [];
$sesiones_lista = [];

try {
    $conn = conectar();
    $historial = queryDB($conn,
        "SELECT rol, mensaje, DATE_FORMAT(fecha_hora,'%H:%i') AS hora
          FROM conversaciones WHERE usuario_id=? AND sesion_id=?
          ORDER BY fecha_hora ASC",
        [$usuario_id, $sesion_id]);

    $sesiones_lista = queryDB($conn,
        "SELECT sesion_id, MIN(fecha_hora) AS fecha_inicio,
                (SELECT mensaje FROM conversaciones c2
                  WHERE c2.sesion_id=c.sesion_id AND c2.usuario_id=? AND c2.rol='user'
                  ORDER BY c2.fecha_hora ASC LIMIT 1) AS primer_msg,
                COUNT(*) AS total_msg
          FROM conversaciones c
         WHERE usuario_id=? AND sesion_id <> ''
         GROUP BY sesion_id ORDER BY fecha_inicio DESC LIMIT 40",
        [$usuario_id, $usuario_id]);
    $conn->close();
} catch (RuntimeException $e) {}

$es_nueva_sesion = empty($historial);

$hoy   = date('Y-m-d');
$ayer  = date('Y-m-d', strtotime('-1 day'));
$semana = date('Y-m-d', strtotime('-7 days'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Nix · Yourself</title>
  <link rel="icon" href="../img/nix-face.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/estilos.css?v=<?= filemtime(__DIR__ . '/../css/estilos.css') ?>">
</head>
<style>

*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

html, body {
  height: 100%;
  height: 100dvh;
  width: 100%;
  overflow: hidden;
  font-family: 'Nunito', sans-serif;
  background: #12141f;
  color: #f0f0ff;
}

::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 4px; }


.chat-app {
  display: flex;
  flex-direction: row;
  width: 100vw;
  height: 100vh;
  height: 100dvh;
  overflow: hidden;
}


.c-sidebar {
  width: 260px;
  min-width: 260px;
  max-width: 260px;
  height: 100vh;
  height: 100dvh;
  background: #0b0d17;
  border-right: 1px solid rgba(255,255,255,.07);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  flex-shrink: 0;
}

/* Brand */
.c-sidebar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 20px 18px 14px;
  border-bottom: 1px solid rgba(255,255,255,.06);
  flex-shrink: 0;
}
.c-sidebar-brand img { width: 32px; height: 32px; border-radius: 10px; }
.c-sidebar-brand-name {
  font-size: 1.05rem; font-weight: 800;
  background: linear-gradient(135deg, #a78bfa, #ec4899);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}

/* Botón nueva conversación */
.c-btn-new {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 14px 14px 0;
  padding: 10px 14px;
  background: rgba(124,58,237,.13);
  border: 1px solid rgba(124,58,237,.28);
  border-radius: 12px;
  color: rgba(255,255,255,.85);
  font-family: 'Nunito', sans-serif;
  font-size: .88rem; font-weight: 700;
  text-decoration: none;
  cursor: pointer;
  transition: all .22s ease;
  flex-shrink: 0;
}
.c-btn-new-ico {
  width: 26px; height: 26px;
  background: linear-gradient(135deg, #7c3aed, #ec4899);
  border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  font-size: .9rem; flex-shrink: 0;
}
.c-btn-new:hover {
  background: rgba(124,58,237,.25);
  border-color: rgba(124,58,237,.55);
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 4px 18px rgba(124,58,237,.22);
}

/* Lista de sesiones */
.c-sessions {
  flex: 1;
  overflow-y: auto;
  padding: 10px 8px;
}
.c-sessions-group {
  font-size: .64rem; font-weight: 800;
  color: rgba(255,255,255,.28);
  letter-spacing: .12em; text-transform: uppercase;
  padding: 12px 10px 5px;
}
.c-session-empty {
  text-align: center;
  padding: 32px 16px;
  color: rgba(255,255,255,.28);
  font-size: .84rem;
  line-height: 1.7;
}
.c-session-item {
  display: flex; align-items: center; gap: 9px;
  padding: 9px 10px;
  border-radius: 9px;
  text-decoration: none;
  color: rgba(255,255,255,.6);
  transition: all .18s ease;
  margin-bottom: 1px;
  position: relative;
}
.c-session-item:hover {
  background: rgba(255,255,255,.06);
  color: rgba(255,255,255,.9);
}
.c-session-item.active {
  background: rgba(124,58,237,.18);
  color: #c4a4ff;
}
.c-session-item.active::before {
  content: '';
  position: absolute; left: 0; top: 20%; bottom: 20%;
  width: 2.5px;
  background: linear-gradient(180deg, #7c3aed, #ec4899);
  border-radius: 0 2px 2px 0;
}

/* Link al chat (sin el botón) */
.c-session-link {
  display: flex; align-items: center; gap: 9px;
  flex: 1; min-width: 0;
  text-decoration: none; color: inherit;
}
.c-session-ico { font-size: .9rem; opacity: .5; flex-shrink: 0; }
.c-session-item:hover .c-session-ico,
.c-session-item.active .c-session-ico { opacity: 1; }
.c-session-title {
  font-size: .82rem; font-weight: 600;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  flex: 1;
}

/* Botón eliminar */
.c-del-btn {
  display: flex; align-items: center; justify-content: center;
  width: 24px; height: 24px; border-radius: 6px;
  border: none; cursor: pointer;
  background: transparent;
  color: rgba(255,255,255,.2);
  font-size: 1rem; line-height: 1;
  flex-shrink: 0;
  transition: all .18s ease;
  visibility: hidden;
  padding: 0;
}
.c-session-item:hover .c-del-btn { visibility: visible; }
.c-del-btn:hover {
  background: rgba(239,68,68,.2);
  color: #f87171;
  transform: scale(1.1);
}

/* Footer usuario */
.c-sidebar-footer {
  flex-shrink: 0;
  padding: 12px 12px 14px;
  border-top: 1px solid rgba(255,255,255,.06);
}
.c-user {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 10px;
  background: rgba(255,255,255,.04);
  border-radius: 10px;
  margin-bottom: 6px;
}
.c-user-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  background: linear-gradient(135deg, #7c3aed, #ec4899);
  display: flex; align-items: center; justify-content: center;
  font-size: .85rem; font-weight: 800; color: #fff;
  flex-shrink: 0;
}
.c-user-name { font-size: .84rem; font-weight: 700; color: rgba(255,255,255,.85); line-height: 1.2; }
.c-user-status { font-size: .7rem; color: #4ade80; font-weight: 600; }
.c-back-link {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  font-size: .82rem; font-weight: 700;
  color: rgba(255,255,255,.65);
  background: rgba(255,255,255,.03);
  border: 1px solid rgba(255,255,255,.07);
  padding: 10px; border-radius: 10px;
  text-decoration: none; transition: all .22s ease;
}
.c-back-link:hover { 
  color: #fff; 
  background: rgba(124,58,237,.15); 
  border-color: rgba(124,58,237,.35);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(124,58,237,.15);
}


.c-main {
  flex: 1;
  min-width: 0;
  height: 100vh;
  height: 100dvh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #12141f;
}

/* Header */
.c-header {
  display: flex; align-items: center; gap: 14px;
  padding: 13px 24px;
  background: rgba(12,14,24,.8);
  border-bottom: 1px solid rgba(255,255,255,.07);
  backdrop-filter: blur(18px);
  flex-shrink: 0;
  z-index: 5;
}
.c-header-avatar {
  width: 40px; height: 40px; border-radius: 12px;
  object-fit: cover;
  box-shadow: 0 4px 16px rgba(124,58,237,.4);
}
.c-header-info { flex: 1 }
.c-header-name { font-size: .96rem; font-weight: 800; color: #f0f0ff; letter-spacing: -.2px; }
.c-header-status {
  display: flex; align-items: center; gap: 5px;
  font-size: .73rem; color: #4ade80; font-weight: 600; margin-top: 1px;
}
.c-status-dot {
  width: 7px; height: 7px; border-radius: 50%; background: #4ade80;
  animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse { 0%,100%{ opacity:1 } 50%{ opacity:.35 } }
.c-user-badge {
  width: 36px; height: 36px; border-radius: 50%;
  background: linear-gradient(135deg, #7c3aed, #ec4899);
  display: flex; align-items: center; justify-content: center;
  font-size: .85rem; font-weight: 800; color: #fff;
  box-shadow: 0 4px 14px rgba(124,58,237,.4);
  flex-shrink: 0;
}

/* ─── PANTALLA DE BIENVENIDA ─── */
.c-welcome {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 26px; padding: 40px 24px; text-align: center;
  overflow-y: auto; min-height: 0;
}
.c-welcome-owl {
  width: 80px; height: 80px;
  background: rgba(124,58,237,.14);
  border: 1px solid rgba(124,58,237,.25);
  border-radius: 22px;
  display: flex; align-items: center; justify-content: center;
  font-size: 2.5rem;
  animation: float 3.5s ease-in-out infinite;
  box-shadow: 0 8px 32px rgba(124,58,237,.18);
}
@keyframes float { 0%,100%{ transform:translateY(0) } 50%{ transform:translateY(-10px) } }
.c-welcome h2 {
  font-size: 1.65rem; font-weight: 900;
  background: linear-gradient(135deg, #c4b5fd, #f472b6);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  margin-bottom: 6px;
}
.c-welcome p { color: rgba(255,255,255,.42); font-size: .95rem; line-height: 1.7; }

/* Tarjetas de sugerencia */
.c-suggestions {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 10px; max-width: 580px; width: 100%;
}
.c-sug-btn {
  display: flex; align-items: center; gap: 12px;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.09);
  border-radius: 14px;
  padding: 14px 16px;
  text-align: left; cursor: pointer;
  transition: all .22s ease;
  font-family: 'Nunito', sans-serif;
  color: rgba(255,255,255,.75);
}
.c-sug-btn:hover {
  background: rgba(124,58,237,.15);
  border-color: rgba(124,58,237,.38);
  color: #fff; transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(124,58,237,.18);
}
.c-sug-emoji { font-size: 1.6rem; flex-shrink: 0; }
.c-sug-title { font-size: .88rem; font-weight: 700; color: rgba(255,255,255,.9); display: block; }
.c-sug-sub   { font-size: .75rem; color: rgba(255,255,255,.38); font-weight: 400; }

/* ─── CHIPS RÁPIDOS ─── */
.c-chips {
  display: flex; gap: 8px;
  padding: 9px 24px 8px;
  border-bottom: 1px solid rgba(255,255,255,.06);
  overflow-x: auto; flex-shrink: 0;
}
.c-chips::-webkit-scrollbar { height: 0 }
.c-chip {
  display: inline-flex; align-items: center; gap: 5px;
  white-space: nowrap;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 50px;
  color: rgba(255,255,255,.6);
  font-family: 'Nunito', sans-serif;
  font-size: .79rem; font-weight: 700;
  padding: 5px 13px 5px 9px;
  cursor: pointer; transition: all .2s;
}
.c-chip:hover {
  background: rgba(124,58,237,.18);
  border-color: rgba(124,58,237,.45);
  color: #d4b8ff; transform: translateY(-1px);
}

/* ─── MENSAJES ─── */
.c-messages {
  flex: 1; overflow-y: auto;
  padding: 28px 80px 16px;
  display: flex; flex-direction: column; gap: 18px;
}
@keyframes fadeUp { from{ opacity:0; transform:translateY(12px) } to{ opacity:1; transform:translateY(0) } }
.c-msg {
  display: flex; gap: 12px;
  animation: fadeUp .28s ease;
  max-width: 820px; width: 100%;
}
.c-msg.nix  { align-self: flex-start; }
.c-msg.user { align-self: flex-end; flex-direction: row-reverse; }

.c-msg-avatar {
  width: 34px; height: 34px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; flex-shrink: 0; align-self: flex-start;
  background: rgba(124,58,237,.18); border: 1px solid rgba(124,58,237,.28);
}
.c-msg.user .c-msg-avatar {
  background: linear-gradient(135deg,#7c3aed,#ec4899);
  border-radius: 50%;
  border: none; font-size: .82rem; font-weight: 800; color: #fff;
}

.c-msg-bubble {
  padding: 11px 16px; border-radius: 18px;
  font-size: .92rem; line-height: 1.5;
  max-width: 100%;
  word-break: break-word;
  white-space: pre-wrap;
  display: inline-block;
  box-shadow: 0 4px 14px rgba(0,0,0,.15);
}
.c-msg.nix .c-msg-bubble {
  background: rgba(255,255,255,.055);
  border: 1px solid rgba(255,255,255,.09);
  border-bottom-left-radius: 4px;
  color: rgba(255,255,255,.9);
  box-shadow: none;
}
.c-msg.user .c-msg-bubble {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  color: #fff; border-bottom-right-radius: 4px;
}
.c-msg-time {
  font-size: .68rem; color: rgba(255,255,255,.25);
  margin-top: 6px; display: block; padding: 0 4px;
}
.c-msg.user .c-msg-time { text-align: right; }

/* ─── TYPING INDICATOR ─── */
.c-typing {
  display: flex; align-items: center; gap: 5px; padding: 14px 18px !important;
}
.typing-dot {
  width: 6px; height: 6px; border-radius: 50%; background: rgba(255,255,255,.5);
  animation: typingDot 1.4s infinite ease-in-out both;
}
.typing-dot:nth-child(1) { animation-delay: -0.32s; }
.typing-dot:nth-child(2) { animation-delay: -0.16s; }
@keyframes typingDot {
  0%, 80%, 100% { transform: scale(0); opacity: .5; }
  40% { transform: scale(1); opacity: 1; }
}

/* ─── DISCLAIMER ─── */
.c-disclaimer {
  text-align: center; font-size: .7rem;
  color: rgba(255,255,255,.22);
  padding: 7px 24px; flex-shrink: 0;
  border-top: 1px solid rgba(255,255,255,.05);
}

/* ─── INPUT ─── */
.c-input-wrap {
  padding: 12px 30px 16px;
  flex-shrink: 0;
}
.c-input-row {
  display: flex; align-items: flex-end; gap: 10px;
  background: rgba(255,255,255,.055);
  border: 1.5px solid rgba(255,255,255,.11);
  border-radius: 20px;
  padding: 10px 10px 10px 20px;
  transition: all .22s ease;
}
.c-input-row:focus-within {
  border-color: rgba(124,58,237,.6);
  background: rgba(255,255,255,.08);
  box-shadow: 0 0 0 4px rgba(124,58,237,.1);
}
.c-textarea {
  flex: 1; background: transparent; border: none;
  color: #f0f0ff; font-family: 'Nunito', sans-serif;
  font-size: .93rem; padding: 2px 0; outline: none;
  resize: none; max-height: 130px; overflow-y: auto;
  line-height: 1.55;
}
.c-textarea::placeholder { color: rgba(255,255,255,.25) }
.c-send-btn {
  width: 40px; height: 40px; border-radius: 50%;
  background: linear-gradient(135deg, #7c3aed, #9333ea);
  border: none; cursor: pointer; color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: .95rem; flex-shrink: 0;
  box-shadow: 0 4px 16px rgba(124,58,237,.45);
  transition: all .22s ease;
}
.c-send-btn:hover { transform: scale(1.12); box-shadow: 0 6px 20px rgba(124,58,237,.65); }
.c-input-hint {
  font-size: .67rem; color: rgba(255,255,255,.18);
  text-align: right; padding: 4px 6px 0;
}

/* ─── Menú móvil del chat ─── */
.c-nav-toggle { display: none; }
.c-menu-btn {
  display: none; align-items: center; justify-content: center;
  width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
  background: rgba(124,58,237,.15); border: 1px solid rgba(124,58,237,.3);
  color: #d4b8ff; font-size: 1.2rem; line-height: 1; cursor: pointer;
  -webkit-tap-highlight-color: transparent; transition: all .2s ease;
}
.c-menu-btn:hover { background: rgba(124,58,237,.28); color: #fff; }
.c-overlay {
  display: none; position: fixed; inset: 0; z-index: 90;
  background: rgba(0,0,0,.80); cursor: pointer;
  border: none; -webkit-tap-highlight-color: transparent;
}
@media (max-width: 860px) {
  .c-sidebar {
    position: fixed; left: 0; top: 0; z-index: 100;
    width: 82vw; min-width: unset; max-width: 320px;
    height: 100vh; height: 100dvh;
    transform: translateX(-100%);
    transition: transform .3s cubic-bezier(.4,0,.2,1);
    box-shadow: 8px 0 40px rgba(0,0,0,.7);
  }
  .c-menu-btn { display: inline-flex; }
  .c-nav-toggle:checked ~ .chat-app .c-sidebar { transform: translateX(0); }
  .c-nav-toggle:checked ~ .c-overlay { display: block; }
  .c-messages { padding: 22px 16px 12px; }
  .c-input-wrap { padding: 10px 14px 14px; }
}
@media (max-width: 560px) {
  .c-suggestions { grid-template-columns: 1fr; }
  .c-messages { padding: 16px 10px 10px; }
  .c-welcome { padding: 28px 16px; gap: 20px; }
  .c-header { padding: 11px 16px; }
}
</style>
</head>
<body data-crisis-config='<?= getCrisisResourcesJSON() ?>'>

<input type="checkbox" id="cNavToggle" class="c-nav-toggle" aria-hidden="true">
<label for="cNavToggle" class="c-overlay" aria-label="Cerrar menú"></label>

<div class="chat-app">

  
  <aside class="c-sidebar">

    
    <div class="c-sidebar-brand">
      <img src="../img/nix-face.png" alt="Nix" style="width:32px;height:32px;object-fit:contain;border-radius:10px">
      <span class="c-sidebar-brand-name">Yourself</span>
    </div>

    
    <a href="chat.php" class="c-btn-new" title="Nueva conversación">
      <span class="c-btn-new-ico">✏️</span>
      <span>Nueva conversación</span>
    </a>

    
    <div class="c-sessions">
      <?php if (empty($sesiones_lista)): ?>
        <div class="c-session-empty">
          <div style="font-size:2rem;margin-bottom:8px">🦉</div>
          Aún no hay conversaciones.<br>¡Empieza aquí!
        </div>
      <?php else:
        $grupos = ['Hoy'=>[], 'Ayer'=>[], 'Esta semana'=>[], 'Anteriores'=>[]];
        foreach ($sesiones_lista as $s) {
            $d = substr($s['fecha_inicio'] ?? '', 0, 10);
            if ($d === $hoy)       $grupos['Hoy'][] = $s;
            elseif ($d === $ayer)  $grupos['Ayer'][] = $s;
            elseif ($d >= $semana) $grupos['Esta semana'][] = $s;
            else                   $grupos['Anteriores'][] = $s;
        }
        foreach ($grupos as $label => $items):
          if (empty($items)) continue; ?>
          <div class="c-sessions-group"><?= $label ?></div>
          <?php foreach ($items as $s):
            $activa = $s['sesion_id'] === $sesion_id ? 'active' : '';
            $raw    = $s['primer_msg'] ?? '';
            $titulo = $raw ? mb_substr(htmlspecialchars($raw), 0, 36) . (mb_strlen($raw) > 36 ? '…' : '') : 'Nueva conversación';
          ?>
          <div class="c-session-item <?= $activa ?>">
            <a href="chat.php?sesion=<?= htmlspecialchars($s['sesion_id']) ?>"
               class="c-session-link" title="<?= $titulo ?>">
              <span class="c-session-ico">💬</span>
              <span class="c-session-title"><?= $titulo ?></span>
            </a>
            
            <form method="POST" action="../php/eliminar_sesion.php"
                  onsubmit="return confirm('\u00bfEliminar esta conversación?\n\nEsta acción no se puede deshacer.')">
              <?= csrfCampo() ?>
              <input type="hidden" name="sesion_id" value="<?= htmlspecialchars($s['sesion_id']) ?>">
              <button type="submit" class="c-del-btn" title="Eliminar conversación">
                <svg width="13" height="14" viewBox="0 0 13 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M1 3.5h11M4.5 3.5V2.5a1 1 0 011-1h2a1 1 0 011 1v1M10.5 3.5l-.8 8a1 1 0 01-1 .9H4.3a1 1 0 01-1-.9l-.8-8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M5.5 6.5v3M7.5 6.5v3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                </svg>
              </button>
            </form>
          </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    
    <div class="c-sidebar-footer">
      <div style="margin-bottom: 12px;">
        <button type="button" class="btn-new-chat" style="justify-content:center; background: rgba(248,113,113,.1); border-color: rgba(248,113,113,.3); color: #f87171;" onclick="YourselfCrisis.activateProtocol('warning')">
          <span class="nc-icon">🆘</span> Recursos de Ayuda
        </button>
      </div>
      <div class="c-user">
        <div class="c-user-avatar"><?= htmlspecialchars($usuario_letra) ?></div>
        <div>
          <div class="c-user-name"><?= limpiar($usuario_nombre) ?></div>
          <div class="c-user-status">● En línea</div>
        </div>
      </div>
      <a href="diario.php" class="c-back-link">← Volver al Diario</a>
    </div>

  </aside>

  
  <div class="c-main">

    
    <div class="c-header">
      <label for="cNavToggle" class="c-menu-btn" aria-label="Abrir menú">☰</label>
      <img src="../img/nix-face.png" alt="Nix" class="c-header-avatar">
      <div class="c-header-info">
        <div class="c-header-name">Nix · Asistente emocional 🦉</div>
        <div class="c-header-status">
          Disponible
        </div>
      </div>
      <div class="c-user-badge"><?= htmlspecialchars($usuario_letra) ?></div>
    </div>

    <?php if ($es_nueva_sesion): ?>
    
    <div class="c-welcome">
      <div class="c-welcome-owl" style="background:transparent;border:none;box-shadow:none;width:100px;height:100px">
        <img src="../img/nix.png" alt="Nix" style="width:100px;height:100px;object-fit:contain">
      </div>
      <div>
        <h2>Hola, <?= limpiar($usuario_nombre) ?> 👋</h2>
        <?php if (count($sesiones_lista) === 0): ?>
          <p style="text-align: left; max-width: 600px; margin: 0 auto; line-height: 1.5; font-size: 15px;">
            Soy <strong>Nix</strong>, tu asistente y espacio seguro para hablar. 💜<br><br>
            Estoy aquí para escucharte sin juzgarte, ayudarte a entender tus emociones y darte herramientas de bienestar mental. 
            Puedes escribirme sobre cualquier cosa: si tienes un problema, si sientes ansiedad por el estudio, o simplemente si tuviste un buen día. Todo lo que hablemos es 100% privado.<br><br>
            ¿De qué quieres conversar hoy?
          </p>
        <?php else: 
            $datos_nix = [
                "¿Sabías que escribir sobre tus emociones por 15 minutos ayuda a reducir el estrés?",
                "Recuerda que está bien no estar bien. Aquí estoy para escucharte sin juzgar.",
                "Dato curioso: respirar profundo 3 veces le envía una señal de calma directa a tu cerebro.",
                "Tu espacio seguro está listo. Ningún problema es 'demasiado pequeño' para hablarlo.",
                "¿Sabías que nombrar tus emociones ('me siento frustrado') reduce su intensidad a la mitad?",
                "Un paso a la vez. ¿De qué te gustaría hablar en esta nueva sesión?"
            ];
            $dato_random = $datos_nix[array_rand($datos_nix)];
        ?>
          <p>Soy Nix. 🦉<br><br><em style="color: var(--text-muted); display: block; margin: 8px 0; font-size: 14px;">"<?= $dato_random ?>"</em><br>¿De qué quieres conversar hoy?</p>
        <?php endif; ?>
      </div>
      <div class="c-suggestions">
        <?php
        $sugs = [
          ['😢','Me siento triste','Necesito hablar','Me siento triste hoy'],
          ['😰','Estrés / Ansiedad','Estoy agobiado/a','Siento mucha ansiedad y estrés'],
          ['📚','Problemas escolares','Colegio / tareas','Tengo problemas en el colegio'],
          ['😊','Contar algo bueno','Tuve un buen día','Tuve un buen día y quiero compartirlo'],
        ];
        foreach($sugs as [$em,$tit,$sub,$msg]): ?>
        <form method="POST" action="chat.php?sesion=<?= htmlspecialchars($sesion_id) ?>" style="display:contents">
          <?= csrfCampo() ?>
          <input type="hidden" name="sesion"  value="<?= htmlspecialchars($sesion_id) ?>">
          <input type="hidden" name="mensaje" value="<?= htmlspecialchars($msg,ENT_QUOTES) ?>">
          <button type="submit" class="c-sug-btn">
            <span class="c-sug-emoji"><?= $em ?></span>
            <span>
              <span class="c-sug-title"><?= $tit ?></span>
              <span class="c-sug-sub"><?= $sub ?></span>
            </span>
          </button>
        </form>
        <?php endforeach; ?>
      </div>
    </div>

    <?php else: ?>
    
    <div class="c-chips">
      <?php
      $chips = [
        ['😢','Triste','Me siento triste hoy'],
        ['😰','Ansiedad','Siento mucha ansiedad'],
        ['📚','Estrés escolar','El colegio me está estresando mucho'],
        ['💡','Consejos','Dame consejos para mejorar mi estado de ánimo'],
        ['😊','Buen día','Tuve un buen día y quiero contarte'],
        ['🌙','Sin dormir','No he podido dormir bien últimamente'],
        ['💜','Solo/a','Me siento solo/a y quiero hablar'],
      ];
      foreach($chips as [$em,$lab,$msg]): ?>
      <form method="POST" action="chat.php?sesion=<?= htmlspecialchars($sesion_id) ?>" style="display:inline">
        <?= csrfCampo() ?>
        <input type="hidden" name="sesion"  value="<?= htmlspecialchars($sesion_id) ?>">
        <input type="hidden" name="mensaje" value="<?= htmlspecialchars($msg,ENT_QUOTES) ?>">
        <button type="submit" class="c-chip"><span><?= $em ?></span><?= htmlspecialchars($lab) ?></button>
      </form>
      <?php endforeach; ?>
    </div>

    <div class="c-messages" id="messages">
      <?php foreach($historial as $msg):
        $clase  = $msg['rol']==='user' ? 'user' : 'nix';
        $avatar = $msg['rol']==='user' ? htmlspecialchars($usuario_letra) : '🦉'; ?>
      <div class="c-msg <?= $clase ?>">
        <div class="c-msg-avatar"><?= $avatar ?></div>
        <div>
          <div class="c-msg-bubble"><?= nl2br(limpiar($msg['mensaje'])) ?></div>
          <div class="c-msg-time"><?= htmlspecialchars($msg['hora']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>

    
    <div class="c-disclaimer">
      🔒 Nix no reemplaza ayuda profesional · Emergencias: Línea 106 (jóvenes) · 123
    </div>

    
    <div class="c-input-wrap">
      <form method="POST" action="chat.php?sesion=<?= htmlspecialchars($sesion_id) ?>" style="display:contents">
        <?= csrfCampo() ?>
        <input type="hidden" name="sesion" value="<?= htmlspecialchars($sesion_id) ?>">
        <div class="c-input-row">
          <textarea name="mensaje" id="chat-input" class="c-textarea"
            placeholder="Escríbele a Nix…" rows="1" maxlength="500" required></textarea>
          <button type="submit" class="c-send-btn" title="Enviar">➤</button>
        </div>
      </form>
      <p class="c-input-hint">Enter para enviar · Shift+Enter nueva línea</p>
    </div>

  </div>

</div>

<script src="../js/crisis-detector.js"></script>
<script>
(function(){
  YourselfCrisis.init();

  var m = document.getElementById('messages');
  if(m) m.scrollTop = m.scrollHeight;
  var ta = document.getElementById('chat-input');
  var form = ta ? ta.closest('form') : null;

  if (ta) {
    ta.addEventListener('input', function(){
      this.style.height='auto';
      this.style.height=Math.min(this.scrollHeight, 130)+'px';
    });
    ta.addEventListener('keydown', function(e){
      if(e.key==='Enter' && !e.shiftKey) { 
        e.preventDefault(); 
        if(form && ta.value.trim() !== '') {
          var btn = form.querySelector('.c-send-btn');
          if (btn) btn.click();
          else form.dispatchEvent(new Event('submit', {cancelable: true, bubbles: true}));
        }
      }
    });
    ta.focus();
  }

  var ajaxForms = document.querySelectorAll('form[action^="chat.php"]');
  ajaxForms.forEach(function(f) {
    if (f.querySelector('.c-del-btn')) return; // No interceptar forms de borrar
    
    f.addEventListener('submit', function(e) {
      if (!m) return; // Si es pantalla de bienvenida, dejar recargar la pág
      e.preventDefault();
      
      var formInput = f.querySelector('input[name="mensaje"]') || ta;
      var msgText = formInput ? formInput.value.trim() : '';
      if (!msgText) return;

      var fd = new FormData(f);
      fd.append('ajax', '1');
      if (formInput === ta) {
        fd.set('mensaje', msgText);
        ta.value = '';
        ta.style.height = 'auto';
        ta.disabled = true;
      }

      // Detección de crisis en frontend
      var analisisFront = YourselfCrisis.analyze(msgText);
      if (analisisFront.isCrisis) {
        YourselfCrisis.activateProtocol(analisisFront.level);
      }

      var userHtml = `
      <div class="c-msg user">
        <div class="c-msg-avatar"><?= htmlspecialchars($usuario_letra) ?></div>
        <div>
          <div class="c-msg-bubble">${escapeHtml(msgText).replace(/\n/g, '<br>')}</div>
          <div class="c-msg-time">Ahora</div>
        </div>
      </div>`;
      m.insertAdjacentHTML('beforeend', userHtml);
      
      var typingHtml = `
      <div class="c-msg nix" id="typing-indicator">
        <div class="c-msg-avatar">🦉</div>
        <div>
          <div class="c-msg-bubble c-typing">
            <div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>
          </div>
        </div>
      </div>`;
      m.insertAdjacentHTML('beforeend', typingHtml);
      m.scrollTo({ top: m.scrollHeight, behavior: 'smooth' });

      var btns = document.querySelectorAll('.c-send-btn, .c-sug-btn, .c-chip');
      btns.forEach(b => { b.style.pointerEvents = 'none'; b.style.opacity = '0.5'; });

      fetch(f.action, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(res => {
         if(ta) ta.disabled = false;
         btns.forEach(b => { b.style.pointerEvents = 'auto'; b.style.opacity = '1'; });
         var ti = document.getElementById('typing-indicator');
         if(ti) ti.remove();

         if (res.error) {
             alert(res.error);
         } else if (res.status === 'ok') {
             if (res.crisis) {
                 YourselfCrisis.activateProtocol(res.crisis_level);
             }
             var nixHtml = `
             <div class="c-msg nix">
               <div class="c-msg-avatar">🦉</div>
               <div>
                 <div class="c-msg-bubble">${res.respuesta}</div>
                 <div class="c-msg-time">${escapeHtml(res.hora)}</div>
               </div>
             </div>`;
             m.insertAdjacentHTML('beforeend', nixHtml);
             m.scrollTo({ top: m.scrollHeight, behavior: 'smooth' });
         }
         if(ta) ta.focus();
      })
      .catch(err => {
         if(ta) ta.disabled = false;
         btns.forEach(b => { b.style.pointerEvents = 'auto'; b.style.opacity = '1'; });
         var ti = document.getElementById('typing-indicator');
         if(ti) ti.remove();
         alert('Error de conectividad temporal. Nix no pudo responder.');
      });
    });
  });

  function escapeHtml(text) {
     var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
     return text.replace(/[&<>"']/g, function(k) { return map[k]; });
  }
})();
</script>
</body>
</html>
