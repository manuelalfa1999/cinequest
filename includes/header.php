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
    <script src="/cinequest/assets/js/recomendador.js" defer></script>
</head>
<body>
<nav>
    <a href="/cinequest/index.php">
        <img src="/cinequest/assets/img/logo.svg" alt="CineQuest" style="height:60px; vertical-align:middle;">
    </a>
    <?php if (isset($_SESSION['usuario_id'])): ?>
        <div class="nav-links">
            <span> 👤 <?= htmlspecialchars($_SESSION['nombre']) ?></span>
            <a href="/cinequest/index.php">Inicio</a>
            <a href="/cinequest/pages/peliculas.php">Catálogo</a>
            <a href="/cinequest/pages/recomendador.php">¿Qué veo hoy?</a>
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <a href="/cinequest/pages/admin.php">Panel Admin</a>
            <?php endif; ?>
            <a href="/cinequest/pages/perfil.php">Mi Perfil</a>
            <a href="/cinequest/pages/logout.php">Cerrar sesión</a>
        </div>
    <?php else: ?>
        <div class="nav-links">
            <a href="/cinequest/pages/login.php">Iniciar sesión</a>
            <a href="/cinequest/pages/register.php">Registrarse</a>
        </div>
    <?php endif; ?>
</nav>