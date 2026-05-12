<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';
require_once '../includes/niveles.php';

$usuario_id = $_SESSION['usuario_id'];

// Si es domingo, calcular y guardar el ganador de la semana
if (date('N') == 7) {
    $stmt = $pdo->query('SELECT u.id, u.nombre, COUNT(hv.id) as vistas_semana 
                         FROM historial_visto hv 
                         JOIN usuarios u ON u.id = hv.usuario_id 
                         WHERE hv.fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                         GROUP BY hv.usuario_id 
                         ORDER BY vistas_semana DESC 
                         LIMIT 1');
    $ganador = $stmt->fetch();

    if ($ganador) {
        $pdo->query('UPDATE usuarios SET cinefilo_semana = 0, cinefilo_semana_fecha = NULL');
        $pdo->prepare('UPDATE usuarios SET cinefilo_semana = ?, cinefilo_semana_fecha = CURDATE() WHERE id = ?')
            ->execute([$ganador['vistas_semana'], $ganador['id']]);
    }
}

// Mostrar siempre el último ganador guardado
$stmt = $pdo->query('SELECT u.nombre, u.id, u.cinefilo_semana as vistas_semana, u.cinefilo_semana_fecha 
                     FROM usuarios u 
                     WHERE u.cinefilo_semana > 0 
                     ORDER BY u.cinefilo_semana_fecha DESC 
                     LIMIT 1');
$cinefilo_semana = $stmt->fetch();

// Top 10 por XP
$stmt = $pdo->query('SELECT u.id, u.nombre, u.puntos_xp, u.nivel FROM usuarios u ORDER BY u.puntos_xp DESC LIMIT 10');
$ranking_xp = $stmt->fetchAll();

// Top 10 por películas vistas
$stmt = $pdo->query('SELECT u.id, u.nombre, u.nivel, COUNT(hv.id) as peliculas_vistas 
                     FROM usuarios u 
                     LEFT JOIN historial_visto hv ON hv.usuario_id = u.id 
                     GROUP BY u.id 
                     ORDER BY peliculas_vistas DESC 
                     LIMIT 10');
$ranking_vistas = $stmt->fetchAll();

// Posición del usuario actual en XP
$stmt = $pdo->prepare('SELECT COUNT(*) + 1 as posicion FROM usuarios WHERE puntos_xp > (SELECT puntos_xp FROM usuarios WHERE id = ?)');
$stmt->execute([$usuario_id]);
$mi_posicion_xp = $stmt->fetch()['posicion'];

include '../includes/header.php';
?>

<main class="ranking-container">
    <h1>🏆 Ranking CineQuest</h1>

    <?php if ($cinefilo_semana && $cinefilo_semana['vistas_semana'] > 0): ?>
    <div class="cinefilo-semana">
        <div class="cinefilo-semana-icono">🎬</div>
        <div class="cinefilo-semana-info">
            <h2>🌟 Cinéfilo de la semana</h2>
            <p class="cinefilo-nombre"><?= htmlspecialchars($cinefilo_semana['nombre']) ?></p>
            <p class="cinefilo-stats">
                <?= $cinefilo_semana['vistas_semana'] ?> películas vistas —
                Premiado el <?= date('d/m/Y', strtotime($cinefilo_semana['cinefilo_semana_fecha'])) ?>
            </p>
            <?php if ($cinefilo_semana['id'] == $usuario_id): ?>
                <p class="cinefilo-felicita">🎉 ¡Felicidades! ¡Eres el cinéfilo de la semana!</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="mi-posicion">
        <span>Tu posición en el ranking de XP: <strong>#<?= $mi_posicion_xp ?></strong></span>
    </div>

    <div class="ranking-grid">

        <div class="ranking-seccion">
            <h2>⭐ Top XP</h2>
            <div class="ranking-lista">
                <?php foreach ($ranking_xp as $i => $u): ?>
                <div class="ranking-item <?= $u['id'] == $usuario_id ? 'yo' : '' ?>">
                    <span class="ranking-pos <?= $i < 3 ? 'top' : '' ?>">
                        <?= $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : '#'.($i+1))) ?>
                    </span>
                    <span class="ranking-nombre"><?= htmlspecialchars($u['nombre']) ?></span>
                    <span class="ranking-nivel"><?= get_nivel_datos($u['nivel'])['icono'] ?> Nv.<?= $u['nivel'] ?></span>
                    <span class="ranking-valor"><?= number_format($u['puntos_xp']) ?> XP</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="ranking-seccion">
            <h2>🎬 Top Cinéfilos</h2>
            <div class="ranking-lista">
                <?php foreach ($ranking_vistas as $i => $u): ?>
                <div class="ranking-item <?= $u['id'] == $usuario_id ? 'yo' : '' ?>">
                    <span class="ranking-pos <?= $i < 3 ? 'top' : '' ?>">
                        <?= $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : '#'.($i+1))) ?>
                    </span>
                    <span class="ranking-nombre"><?= htmlspecialchars($u['nombre']) ?></span>
                    <span class="ranking-nivel"><?= get_nivel_datos($u['nivel'])['icono'] ?> Nv.<?= $u['nivel'] ?></span>
                    <span class="ranking-valor"><?= $u['peliculas_vistas'] ?> 🎬</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</main>

<?php include '../includes/footer.php'; ?>