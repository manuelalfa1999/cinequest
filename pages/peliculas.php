<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../api/tmdb.php';

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$genero_id = isset($_GET['genero']) ? (int)$_GET['genero'] : null;

if ($busqueda) {
    $resultado = buscar_pelicula($busqueda);
} elseif ($genero_id) {
    $resultado = obtener_peliculas_por_genero($genero_id);
} else {
    $resultado = obtener_peliculas_populares($pagina);
}

$peliculas = $resultado['results'] ?? [];
$total_paginas = $resultado['total_pages'] ?? 1;
if ($total_paginas > 500) $total_paginas = 500;

$generos = [
    28 => 'Acción', 12 => 'Aventura', 16 => 'Animación',
    35 => 'Comedia', 80 => 'Crimen', 99 => 'Documental',
    18 => 'Drama', 10751 => 'Familia', 14 => 'Fantasía',
    27 => 'Terror', 10749 => 'Romance', 878 => 'Ciencia ficción',
    53 => 'Thriller', 37 => 'Western'
];
?>
<?php include '../includes/header.php'; ?>

<main class="peliculas-container">
    <h1>🎬 Catálogo de Películas</h1>

    <form method="GET" class="filtros">
        <input type="text" name="buscar" placeholder="Buscar película..." 
               value="<?= htmlspecialchars($busqueda) ?>">
        <select name="genero">
            <option value="">Todos los géneros</option>
            <?php foreach ($generos as $id => $nombre): ?>
                <option value="<?= $id ?>" <?= $genero_id == $id ? 'selected' : '' ?>>
                    <?= $nombre ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Buscar</button>
        <a href="peliculas.php" class="btn-limpiar">Limpiar</a>
    </form>

    <div class="grid-peliculas">
        <?php foreach ($peliculas as $pelicula): ?>
            <?php if (empty($pelicula['poster_path'])) continue; ?>
            <div class="tarjeta-pelicula">
                <img src="<?= TMDB_IMG_URL . $pelicula['poster_path'] ?>" 
                     alt="<?= htmlspecialchars($pelicula['title']) ?>">
                <div class="tarjeta-info">
                    <h3><?= htmlspecialchars($pelicula['title']) ?></h3>
                    <p class="fecha"><?= substr($pelicula['release_date'] ?? '', 0, 4) ?></p>
                    <p class="puntuacion">⭐ <?= number_format($pelicula['vote_average'], 1) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$busqueda && !$genero_id): ?>
    <div class="paginacion">
        <?php if ($pagina > 1): ?>
            <a href="?pagina=<?= $pagina - 1 ?>">← Anterior</a>
        <?php endif; ?>
        <span>Página <?= $pagina ?> de <?= $total_paginas ?></span>
        <?php if ($pagina < $total_paginas): ?>
            <a href="?pagina=<?= $pagina + 1 ?>">Siguiente →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>