<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pagina_actual = basename($_SERVER['PHP_SELF']);

function nav_activo($pagina, $pagina_actual) {
    $mapa = [
        'home'        => ['home.php', 'index.php'],
        'peliculas'   => ['peliculas.php'],
        'recomendador'=> ['recomendador.php'],
        'admin'       => ['admin.php'],
        'ranking'     => ['ranking.php'],
        'perfil'      => ['perfil.php'],
        'login'       => ['login.php'],
        'register'    => ['register.php'],
    ];
    return in_array($pagina_actual, $mapa[$pagina] ?? []) ? 'nav-activo' : '';
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
    <a href="/cinequest/index.php" class="nav-logo">
        <img src="/cinequest/assets/img/logo.svg" alt="CineQuest" style="height:60px; vertical-align:middle;">
    </a>

    <?php if (isset($_SESSION['usuario_id'])): ?>
        <button class="hamburger" id="hamburger" onclick="toggleMenu()">☰</button>
        <div class="nav-links" id="nav-links">
            <span>👤 <?= htmlspecialchars($_SESSION['nombre']) ?></span>
            <a href="/cinequest/index.php" class="<?= nav_activo('home', $pagina_actual) ?>">Inicio</a>
            <a href="/cinequest/pages/peliculas.php" class="<?= nav_activo('peliculas', $pagina_actual) ?>">Catálogo</a>
            <a href="/cinequest/pages/recomendador.php" class="<?= nav_activo('recomendador', $pagina_actual) ?>">¿Qué veo hoy?</a>
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <a href="/cinequest/pages/admin.php" class="<?= nav_activo('admin', $pagina_actual) ?>">Panel Admin</a>
            <?php endif; ?>
            <a href="/cinequest/pages/ranking.php" class="<?= nav_activo('ranking', $pagina_actual) ?>">🏆 Ranking</a>
            <a href="/cinequest/pages/perfil.php" class="<?= nav_activo('perfil', $pagina_actual) ?>">Mi Perfil</a>
            <a href="/cinequest/pages/logout.php">Cerrar sesión</a>
        </div>
    <?php else: ?>
        <div class="nav-links">
            <a href="/cinequest/pages/login.php" class="<?= nav_activo('login', $pagina_actual) ?>">Iniciar sesión</a>
            <a href="/cinequest/pages/register.php" class="<?= nav_activo('register', $pagina_actual) ?>">Registrarse</a>
        </div>
    <?php endif; ?>
</nav>

<script>
function toggleMenu() {
    const nav = document.getElementById('nav-links');
    const btn = document.getElementById('hamburger');
    nav.classList.toggle('abierto');
    btn.textContent = nav.classList.contains('abierto') ? '✕' : '☰';
}
</script>

<?php
require_once __DIR__ . '/../api/tmdb.php';
if (!tmdb_disponible()): ?>
    <div style="background:#4fc3f722; border-bottom:1px solid #4fc3f7; color:#4fc3f7; padding:8px 20px; font-size:13px; text-align:center;">
        ⚠️ Conexión con TMDB no disponible — mostrando catálogo local
    </div>
<?php endif; ?>