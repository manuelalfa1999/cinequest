<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 minutos de tiempo máximo
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['importar'])) {
    $paginas = (int)$_POST['paginas'];
    if ($paginas < 1) $paginas = 1;
    if ($paginas > 25) $paginas = 25; // máximo 500 películas

    for ($i = 1; $i <= $paginas; $i++) {
        $resultado = obtener_peliculas_populares($i);
        $peliculas = $resultado['results'] ?? [];

        foreach ($peliculas as $pelicula) {
            // Comprobar si ya existe
            $stmt = $pdo->prepare('SELECT id FROM peliculas WHERE tmdb_id = ?');
            $stmt->execute([$pelicula['id']]);
            if ($stmt->fetch()) {
                $saltadas++;
                continue;
            }

            // Obtener detalles completos para tener duración y géneros
            $detalle = obtener_pelicula($pelicula['id']);
            if (!$detalle || isset($detalle['status_code'])) {
                $saltadas++;
                continue;
            }

            $generos = implode(', ', array_map(fn($g) => $g['name'], $detalle['genres'] ?? []));

            $stmt = $pdo->prepare('INSERT INTO peliculas (tmdb_id, titulo, genero, duracion, sinopsis, poster_url, fecha_estreno) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $detalle['id'],
                $detalle['title'],
                $generos,
                $detalle['runtime'] ?? 0,
                $detalle['overview'] ?? '',
                $detalle['poster_path'] ?? '',
                $detalle['release_date'] ?? null,
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
        <p style="color:#888; margin-bottom:10px">Cada página importa 20 películas. El proceso puede tardar varios minutos dependiendo del número de páginas.</p>
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