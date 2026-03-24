<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';
require_once '../api/tmdb.php';

$usuario_id = $_SESSION['usuario_id'];

// Películas populares
$populares = obtener_peliculas_populares(1);
$peliculas_populares = $populares['results'] ?? [];

// Últimas visualizaciones del usuario
$stmt = $pdo->prepare('SELECT pelicula_id FROM historial_visto WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 10');
$stmt->execute([$usuario_id]);
$historial = $stmt->fetchAll();

$ultimas_vistas = [];
foreach ($historial as $item) {
    $pelicula = obtener_pelicula($item['pelicula_id']);
    if ($pelicula && !isset($pelicula['status_code'])) {
        $ultimas_vistas[] = $pelicula;
    }
}

// Película destacada (la más popular)
$destacada = $peliculas_populares[0] ?? null;
$destacada_detalle = $destacada ? obtener_pelicula($destacada['id']) : null;

include '../includes/header.php';
?>

<main class="home-container">

    <?php if ($destacada_detalle): ?>
    <div class="hero-banner" style="background-image: url('https://image.tmdb.org/t/p/original<?= $destacada_detalle['backdrop_path'] ?>')">
        <div class="hero-overlay">
            <div class="hero-content">
                <h1><?= htmlspecialchars($destacada_detalle['title']) ?></h1>
                <p class="hero-sinopsis"><?= htmlspecialchars(substr($destacada_detalle['overview'] ?? '', 0, 200)) ?>...</p>
                <div class="hero-botones">
                    <a href="pelicula.php?id=<?= $destacada_detalle['id'] ?>" class="btn-hero-ver">▶ Ver detalles</a>
                    <a href="recomendador.php" class="btn-hero-recomendar">🎯 ¿Qué veo hoy?</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($ultimas_vistas)): ?>
    <section class="fila-peliculas">
        <h2>🕐 Últimas películas vistas</h2>
        <div class="carrusel">
            <?php foreach ($ultimas_vistas as $pelicula): ?>
                <?php if (empty($pelicula['poster_path'])) continue; ?>
                <a href="pelicula.php?id=<?= $pelicula['id'] ?>" class="carrusel-item">
                    <img src="<?= TMDB_IMG_URL . $pelicula['poster_path'] ?>"
                         alt="<?= htmlspecialchars($pelicula['title']) ?>">
                    <div class="carrusel-info">
                        <p><?= htmlspecialchars($pelicula['title']) ?></p>
                        <span>⭐ <?= number_format($pelicula['vote_average'], 1) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="fila-peliculas">
        <h2>🔥 Populares ahora</h2>
        <div class="carrusel">
            <?php foreach (array_slice($peliculas_populares, 0, 10) as $pelicula): ?>
                <?php if (empty($pelicula['poster_path'])) continue; ?>
                <a href="pelicula.php?id=<?= $pelicula['id'] ?>" class="carrusel-item">
                    <img src="<?= TMDB_IMG_URL . $pelicula['poster_path'] ?>"
                         alt="<?= htmlspecialchars($pelicula['title']) ?>">
                    <div class="carrusel-info">
                        <p><?= htmlspecialchars($pelicula['title']) ?></p>
                        <span>⭐ <?= number_format($pelicula['vote_average'], 1) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="fila-peliculas">
        <h2>🎬 Mejor valoradas</h2>
        <div class="carrusel">
            <?php
            $mejor_valoradas = tmdb_get('/movie/top_rated');
            foreach (array_slice($mejor_valoradas['results'] ?? [], 0, 10) as $pelicula):
            ?>
                <?php if (empty($pelicula['poster_path'])) continue; ?>
                <a href="pelicula.php?id=<?= $pelicula['id'] ?>" class="carrusel-item">
                    <img src="<?= TMDB_IMG_URL . $pelicula['poster_path'] ?>"
                         alt="<?= htmlspecialchars($pelicula['title']) ?>">
                    <div class="carrusel-info">
                        <p><?= htmlspecialchars($pelicula['title']) ?></p>
                        <span>⭐ <?= number_format($pelicula['vote_average'], 1) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="fila-peliculas">
        <h2>🆕 Estrenos recientes</h2>
        <div class="carrusel">
            <?php
            $estrenos = tmdb_get('/movie/now_playing');
            foreach (array_slice($estrenos['results'] ?? [], 0, 10) as $pelicula):
            ?>
                <?php if (empty($pelicula['poster_path'])) continue; ?>
                <a href="pelicula.php?id=<?= $pelicula['id'] ?>" class="carrusel-item">
                    <img src="<?= TMDB_IMG_URL . $pelicula['poster_path'] ?>"
                         alt="<?= htmlspecialchars($pelicula['title']) ?>">
                    <div class="carrusel-info">
                        <p><?= htmlspecialchars($pelicula['title']) ?></p>
                        <span>⭐ <?= number_format($pelicula['vote_average'], 1) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

</main>

<?php include '../includes/footer.php'; ?>