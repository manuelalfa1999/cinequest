<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';
require_once '../api/tmdb.php';

$mensaje = '';
$importadas = 0;
$saltadas = 0;

function descargar_poster($poster_path) {
    if (empty($poster_path)) return null;

    $carpeta = __DIR__ . '/../assets/img/posters/';
    if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);

    $nombre = basename($poster_path);
    $ruta_local = $carpeta . $nombre;

    if (file_exists($ruta_local)) return '/cinequest/assets/img/posters/' . $nombre;

    $url = 'https://image.tmdb.org/t/p/w500' . $poster_path;
    $imagen = @file_get_contents($url);
    if ($imagen) {
        file_put_contents($ruta_local, $imagen);
        return '/cinequest/assets/img/posters/' . $nombre;
    }
    return $poster_path;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['importar'])) {
    $paginas = (int)$_POST['paginas'];
    if ($paginas < 1) $paginas = 1;
    if ($paginas > 25) $paginas = 25;

    for ($i = 1; $i <= $paginas; $i++) {
        $resultado = obtener_peliculas_populares($i);
        $peliculas = $resultado['results'] ?? [];

        foreach ($peliculas as $pelicula) {
            $stmt = $pdo->prepare('SELECT id FROM peliculas WHERE tmdb_id = ?');
            $stmt->execute([$pelicula['id']]);
            if ($stmt->fetch()) {
                $saltadas++;
                continue;
            }

            $detalle = obtener_pelicula($pelicula['id']);
            if (!$detalle || isset($detalle['status_code'])) {
                $saltadas++;
                continue;
            }

            $generos = implode(', ', array_map(fn($g) => $g['name'], $detalle['genres'] ?? []));
            $poster_local = descargar_poster($detalle['poster_path'] ?? '');

            $stmt = $pdo->prepare('INSERT INTO peliculas (tmdb_id, titulo, genero, duracion, sinopsis, poster_url, fecha_estreno, vote_average, vote_count) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $detalle['id'],
                $detalle['title'],
                $generos,
                $detalle['runtime'] ?? 0,
                $detalle['overview'] ?? '',
                $poster_local ?? $detalle['poster_path'] ?? '',
                $detalle['release_date'] ?? null,
                $detalle['vote_average'] ?? 0,
                $detalle['vote_count'] ?? 0,
            ]);
            $importadas++;
        }
    }
    $mensaje = "✅ Importación completada: $importadas películas importadas, $saltadas saltadas (ya existían).";
}

include '../includes/header.php';
?>

<main class="admin-container">
    <h1>📥 Importar películas desde TMDB</h1>

    <?php if ($mensaje): ?>
        <p class="exito"><?= $mensaje ?></p>
    <?php endif; ?>

    <div class="admin-seccion">
        <h2>⚠️ Información importante</h2>
        <p style="color:#888; margin-bottom:10px">Cada página importa 20 películas y descarga sus pósters localmente para que funcionen sin conexión a TMDB.</p>
        <p style="color:#888">Recomendamos empezar con 5 páginas (100 películas) para probar.</p>
    </div>

    <div class="admin-seccion">
        <form method="POST" class="admin-form">
            <input type="number" name="paginas" min="1" max="25" value="5"
                   placeholder="Número de páginas (1 página = 20 películas)">
            <button type="submit" name="importar">📥 Importar películas</button>
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>