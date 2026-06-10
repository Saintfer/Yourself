<?php

require_once __DIR__ . '/conexion.php';

verificarAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

// Protección CSRF (acción destructiva: borrar conversación)
if (!csrfValidar($_POST['csrf'] ?? '')) {
    header('Location: ../pages/chat.php?error=csrf');
    exit;
}

$usuario_id = getUsuarioId();
$sesion_id  = trim($_POST['sesion_id'] ?? '');

if ($sesion_id === '' || !preg_match('/^[a-f0-9]{32}$/', $sesion_id)) {
    header('Location: ../pages/chat.php');
    exit;
}

try {
    $conn = conectar();
    
    queryDB($conn,
        'DELETE FROM conversaciones WHERE sesion_id = ? AND usuario_id = ?',
        [$sesion_id, $usuario_id],
        'ejecutar'
    );
    $conn->close();
} catch (RuntimeException $e) {
    error_log('Error eliminando sesión: ' . $e->getMessage());
}


header('Location: ../pages/chat.php');
exit;
