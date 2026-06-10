<?php
require_once __DIR__ . '/conexion.php';
iniciarSesion();
$_SESSION = [];
session_destroy();
header("Location: ../index.php");
exit();
?>
