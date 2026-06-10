<?php
/**
 * ───────────────────────────────────────────────
 *  Yourself · Borrar Cuenta
 * ───────────────────────────────────────────────
 *  Elimina la cuenta del usuario actual y todas 
 *  sus conversaciones, emociones y diario 
 *  (gracias a ON DELETE CASCADE en la BD).
 */

require_once __DIR__ . '/conexion.php';

if (!verificarAuth(false)) {
    header('Location: ' . urlLogin());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValidar($_POST['csrf'] ?? '')) {
        die('Error de seguridad (CSRF).');
    }
    
    $usuario_id = getUsuarioId();
    if ($usuario_id) {
        $conn = conectar();
        // El borrado en cascada eliminará las tablas relacionadas
        queryDB($conn, 'DELETE FROM usuarios WHERE id = ?', [$usuario_id], 'ejecutar');
        $conn->close();
    }
    
    // Destruir la sesión
    session_destroy();
    session_set_cookie_params([
        'lifetime' => -1,
        'path' => '/',
    ]);
    
    // Redirigir con mensaje de éxito (que puede ser manejado por login.php)
    header('Location: ../pages/login.php?msg=cuenta_borrada');
    exit;
}

// Si se accede por GET, redirigir
header('Location: ../pages/diario.php');
exit;
