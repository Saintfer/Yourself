<?php

require_once __DIR__ . '/config.php';

if (!defined('DB_HOST'))    define('DB_HOST',    env('DB_HOST', 'localhost'));
if (!defined('DB_USER'))    define('DB_USER',    env('DB_USER', 'root'));
if (!defined('DB_PASS'))    define('DB_PASS',    env('DB_PASS', ''));
if (!defined('DB_NAME'))    define('DB_NAME',    env('DB_NAME', 'yourself_db'));
if (!defined('DB_PORT'))    define('DB_PORT',    (int) env('DB_PORT', 3306));
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

function conectar(): mysqli {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        error_log('Error DB: ' . $conn->connect_error);
        throw new RuntimeException('No se pudo conectar a la base de datos.');
    }
    $conn->set_charset(DB_CHARSET);
    return $conn;
}

function queryDB(mysqli $conn, string $sql, array $params = [], string $mode = 'select') {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Error prepare: ' . $conn->error . ' | SQL: ' . $sql);
        return $mode === 'select' ? [] : false;
    }

    if (!empty($params)) {
        $types = '';
        $refs = [];
        foreach ($params as $k => $p) {
            if (is_int($p)) {
                $types .= 'i';
            } elseif (is_float($p)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $refs[$k] = $params[$k];
        }
        $stmt->bind_param($types, ...$refs);
    }

    if (!$stmt->execute()) {
        error_log('Error execute: ' . $stmt->error . ' | SQL: ' . $sql);
        $stmt->close();
        return $mode === 'select' ? [] : false;
    }

    if ($mode === 'ejecutar') {
        $ok = true;
        $stmt->close();
        return $ok;
    }

    $result = $stmt->get_result();
    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    $stmt->close();
    return $rows;
}

function iniciarSesion(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

/**
 * Devuelve la URL base de la aplicación (sin barra final), detectada
 * dinámicamente. Funciona tanto si Yourself está en la raíz del htdocs
 * como dentro de una subcarpeta (ej. /Yourself), y sin importar si quien
 * llama está en /pages o en /php (ambos cuelgan un nivel bajo la raíz).
 */
function urlBase(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir    = str_replace('\\', '/', dirname(dirname($script)));
    return ($dir === '/' || $dir === '.' || $dir === '') ? '' : rtrim($dir, '/');
}

/** URL absoluta (desde la raíz del sitio) hacia el login. */
function urlLogin(string $msg = ''): string {
    $url = urlBase() . '/pages/login.php';
    return $msg !== '' ? $url . '?msg=' . rawurlencode($msg) : $url;
}

function verificarAuth(bool $redirect = true): bool {
    iniciarSesion();
    $auth = isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
    if (!$auth && $redirect) {
        header('Location: ' . urlLogin('sesion_expirada'));
        exit();
    }
    return $auth;
}

function getUsuarioId(): ?int {
    iniciarSesion();
    return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
}

function getUsuarioNombre(): string {
    iniciarSesion();
    return $_SESSION['usuario_nombre'] ?? $_SESSION['nombre'] ?? 'Usuario';
}

function limpiar(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function sanitizar(string $str): string {
    return limpiar($str);
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function responderJSON(array $data, int $code = 200): void {
    jsonResponse($data, $code);
}

/* ─────────────────────────────────────────────
 * CSRF · Token de seguridad anti-falsificación
 * ───────────────────────────────────────────── */

/** Devuelve (y crea si hace falta) el token CSRF de la sesión actual. */
function csrfToken(): string {
    iniciarSesion();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Devuelve el HTML de un campo oculto listo para incluir en formularios:
 *   <?= csrfCampo() ?>
 */
function csrfCampo(): string {
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/** Valida un token enviado por el cliente. Usa hash_equals (timing-safe). */
function csrfValidar(?string $tokenRecibido): bool {
    iniciarSesion();
    if (!$tokenRecibido || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string) $tokenRecibido);
}

/* ─────────────────────────────────────────────
 * Rate limiting · Anti fuerza bruta y spam
 *
 *  Se guarda un pequeño contador en $_SESSION (sin DB, simple y rápido).
 *  Para login, además, se trackea por correo en $_SESSION para que un
 *  atacante no pueda evadir simplemente borrando cookies (la cookie
 *  borrada significa que tampoco puede entrar a una sesión válida).
 * ───────────────────────────────────────────── */

/**
 * Rate-limit genérico por clave. Devuelve true si SE PERMITE la acción.
 *
 * @param string $clave        Nombre del contador (ej. 'login', 'chat').
 * @param int    $maxIntentos  Máximo de eventos permitidos en la ventana.
 * @param int    $ventanaSeg   Tamaño de la ventana en segundos.
 */
function rateLimit(string $clave, int $maxIntentos, int $ventanaSeg): bool {
    iniciarSesion();
    $ahora = time();
    $key   = 'rl_' . $clave;

    if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    // Quitar timestamps fuera de la ventana
    $_SESSION[$key] = array_values(array_filter(
        $_SESSION[$key],
        fn($t) => ($ahora - $t) < $ventanaSeg
    ));

    if (count($_SESSION[$key]) >= $maxIntentos) {
        return false;
    }
    $_SESSION[$key][] = $ahora;
    return true;
}

/** Login: máximo 5 intentos cada 10 minutos. */
function rateLimitLogin(): bool {
    return rateLimit('login', 5, 600);
}

/** Chat: máximo 20 mensajes por minuto. */
function rateLimitChat(int $usuarioId): bool {
    return rateLimit('chat_' . $usuarioId, 20, 60);
}

/** Resetear el contador (ej. tras un login exitoso). */
function rateLimitReset(string $clave): void {
    iniciarSesion();
    unset($_SESSION['rl_' . $clave]);
}
?>
