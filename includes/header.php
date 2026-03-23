<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineQuest</title>
    <link rel="stylesheet" href="/cinequest/assets/css/style.css">
</head>
<body>
<nav>
    <a href="/cinequest/index.php"><strong>🎬 CineQuest</strong></a>
    <?php if (isset($_SESSION['usuario_id'])): ?>
        <span>Hola, <?= htmlspecialchars($_SESSION['nombre']) ?></span>
        <a href="/cinequest/pages/peliculas.php">Catálogo</a>
        <a href="/cinequest/pages/recomendador.php">¿Qué veo hoy?</a>
        <?php if ($_SESSION['rol'] === 'admin'): ?>
            <a href="/cinequest/pages/admin.php">Panel Admin</a>
        <?php endif; ?>
        <a href="/cinequest/pages/perfil.php">Mi Perfil</a>
        <a href="/cinequest/pages/logout.php">Cerrar sesión</a>
    <?php else: ?>
        <a href="/cinequest/pages/login.php">Iniciar sesión</a>
        <a href="/cinequest/pages/register.php">Registrarse</a>
    <?php endif; ?>
</nav>