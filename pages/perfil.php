<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';
require_once '../api/tmdb.php';

$usuario_id = $_SESSION['usuario_id'];

// Datos del usuario
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

// Historial de películas vistas
$stmt = $pdo->prepare('SELECT pelicula_id, fecha, estado_animo, compania FROM historial_visto WHERE usuario_id = ? ORDER BY fecha DESC');
$stmt->execute([$usuario_id]);
$historial = $stmt->fetchAll();

// Retos del usuario
$stmt = $pdo->prepare('SELECT r.titulo, r.descripcion, r.objetivo, r.puntos_xp, 
                              COALESCE(ur.progreso, 0) as progreso,
                              COALESCE(ur.completado, 0) as completado
                       FROM retos r
                       LEFT JOIN usuario_retos ur ON ur.reto_id = r.id AND ur.usuario_id = ?
                       ORDER BY ur.completado ASC, r.id ASC');
$stmt->execute([$usuario_id]);
$retos = $stmt->fetchAll();

// Estadísticas
$total_vistas = count($historial);
$retos_completados = count(array_filter($retos, fn($r) => $r['completado']));

include '../includes/header.php';
?>

<main class="perfil-container">

    <div class="perfil-header">
        <div class="perfil-avatar">👤</div>
        <div class="perfil-datos">
            <h1><?= htmlspecialchars($usuario['nombre']) ?></h1>
            <p class="perfil-email"><?= htmlspecialchars($usuario['email']) ?></p>
            <p class="perfil-fecha">Miembro desde <?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></p>
        </div>
        <div class="perfil-xp">
            <span class="xp-numero"><?= $usuario['puntos_xp'] ?></span>
            <span class="xp-label">puntos XP</span>
        </div>
    </div>

    <div class="perfil-stats">
        <div class="stat-card">
            <span class="stat-numero"><?= $total_vistas ?></span>
            <span class="stat-label">🎬 Películas vistas</span>
        </div>
        <div class="stat-card">
            <span class="stat-numero"><?= $retos_completados ?></span>
            <span class="stat-label">🏆 Retos completados</span>
        </div>
        <div class="stat-card">
            <span class="stat-numero"><?= $usuario['puntos_xp'] ?></span>
            <span class="stat-label">⭐ Puntos XP</span>
        </div>
    </div>

    <div class="perfil-seccion">
        <h2>🏆 Mis Retos</h2>
        <div class="retos-lista">
            <?php foreach ($retos as $reto): ?>
                <div class="reto-card <?= $reto['completado'] ? 'completado' : '' ?>">
                    <div class="reto-header">
                        <h3><?= htmlspecialchars($reto['titulo']) ?></h3>
                        <span class="reto-xp">+<?= $reto['puntos_xp'] ?> XP</span>
                    </div>
                    <p class="reto-desc"><?= htmlspecialchars($reto['descripcion']) ?></p>
                    <div class="progreso-container">
                        <div class="progreso-barra">
                            <div class="progreso-fill" style="width: <?= min(100, ($reto['progreso'] / $reto['objetivo']) * 100) ?>%"></div>
                        </div>
                        <span class="progreso-texto">
                            <?php if ($reto['completado']): ?>
                                ✅ Completado
                            <?php else: ?>
                                <?= $reto['progreso'] ?> / <?= $reto['objetivo'] ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="perfil-seccion">
        <h2>🎬 Historial de películas</h2>
        <?php if (empty($historial)): ?>
            <p class="vacio">Aún no has marcado ninguna película como vista.</p>
        <?php else: ?>
            <div class="historial-lista">
                <?php foreach ($historial as $item): ?>
                    <?php $pelicula = obtener_pelicula($item['pelicula_id']); ?>
                    <?php if (!$pelicula || isset($pelicula['status_code'])) continue; ?>
                    <a href="pelicula.php?id=<?= $item['pelicula_id'] ?>" class="historial-item">
                        <img src="<?= TMDB_IMG_URL . ($pelicula['poster_path'] ?? '') ?>"
                             alt="<?= htmlspecialchars($pelicula['title']) ?>">
                        <div class="historial-info">
                            <h4><?= htmlspecialchars($pelicula['title']) ?></h4>
                            <p>📅 <?= date('d/m/Y', strtotime($item['fecha'])) ?></p>
                            <?php if ($item['estado_animo']): ?>
                                <p>😊 <?= htmlspecialchars($item['estado_animo']) ?></p>
                            <?php endif; ?>
                            <?php if ($item['compania']): ?>
                                <p>👥 <?= htmlspecialchars($item['compania']) ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</main>

<?php include '../includes/footer.php'; ?>