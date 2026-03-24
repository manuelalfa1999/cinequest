<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';
require_once '../api/tmdb.php';

$mensaje = '';
$error = '';

// Procesar añadir película
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['añadir_pelicula'])) {
    $tmdb_id = (int)$_POST['tmdb_id'];

    if ($tmdb_id) {
        // Comprobar si ya existe
        $stmt = $pdo->prepare('SELECT id FROM peliculas WHERE tmdb_id = ?');
        $stmt->execute([$tmdb_id]);
        if ($stmt->fetch()) {
            $error = 'Esa película ya está en la base de datos.';
        } else {
            // Obtener datos de TMDB
            $pelicula = obtener_pelicula($tmdb_id);
            if (!$pelicula || isset($pelicula['status_code'])) {
                $error = 'No se encontró la película en TMDB. Comprueba el ID.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO peliculas (tmdb_id, titulo, genero, duracion, sinopsis, poster_url, fecha_estreno) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)');
                $generos = implode(', ', array_map(fn($g) => $g['name'], $pelicula['genres'] ?? []));
                $stmt->execute([
                    $tmdb_id,
                    $pelicula['title'],
                    $generos,
                    $pelicula['runtime'] ?? 0,
                    $pelicula['overview'] ?? '',
                    $pelicula['poster_path'] ?? '',
                    $pelicula['release_date'] ?? null,
                ]);
                $mensaje = '✅ Película "' . htmlspecialchars($pelicula['title']) . '" añadida correctamente.';
            }
        }
    } else {
        $error = 'El ID de TMDB no es válido.';
    }
}

// Procesar eliminar película
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_pelicula'])) {
    $id = (int)$_POST['pelicula_id'];
    $stmt = $pdo->prepare('DELETE FROM peliculas WHERE id = ?');
    $stmt->execute([$id]);
    $mensaje = '✅ Película eliminada correctamente.';
}

// Obtener películas en BD propia
$stmt = $pdo->query('SELECT * FROM peliculas ORDER BY id DESC');
$peliculas_bd = $stmt->fetchAll();

// Estadísticas generales
$stmt = $pdo->query('SELECT COUNT(*) as total FROM usuarios');
$total_usuarios = $stmt->fetch()['total'];

$stmt = $pdo->query('SELECT COUNT(*) as total FROM historial_visto');
$total_vistas = $stmt->fetch()['total'];

$stmt = $pdo->query('SELECT COUNT(*) as total FROM peliculas');
$total_peliculas = $stmt->fetch()['total'];

include '../includes/header.php';
?>

<main class="admin-container">
    <h1>⚙️ Panel de Administración</h1>
    <a href="importacion.php" class="btn-importar">📥 Importar películas desde TMDB</a>

    <div class="admin-stats">
        <div class="stat-card">
            <span class="stat-numero"><?= $total_usuarios ?></span>
            <span class="stat-label">👥 Usuarios registrados</span>
        </div>
        <div class="stat-card">
            <span class="stat-numero"><?= $total_peliculas ?></span>
            <span class="stat-label">🎬 Películas en BD</span>
        </div>
        <div class="stat-card">
            <span class="stat-numero"><?= $total_vistas ?></span>
            <span class="stat-label">👁️ Películas marcadas vistas</span>
        </div>
    </div>

    <div class="admin-seccion">
        <h2>➕ Añadir película a la BD</h2>
        <p class="admin-hint">Busca la película en <a href="https://www.themoviedb.org" target="_blank">themoviedb.org</a>, 
        copia el ID que aparece en la URL y pégalo aquí.</p>

        <?php if ($mensaje): ?>
            <p class="exito"><?= $mensaje ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST" class="admin-form">
            <input type="number" name="tmdb_id" placeholder="ID de TMDB (ej: 550 para Fight Club)" required>
            <button type="submit" name="añadir_pelicula">➕ Añadir película</button>
        </form>
    </div>

    <div class="admin-seccion">
        <h2>🎬 Películas en la base de datos (<?= $total_peliculas ?>)</h2>
        <?php if (empty($peliculas_bd)): ?>
            <p class="vacio">No hay películas en la BD todavía. Añade algunas usando el formulario de arriba.</p>
        <?php else: ?>
            <div class="admin-tabla">
                <table>
                    <thead>
                        <tr>
                            <th>Póster</th>
                            <th>Título</th>
                            <th>Género</th>
                            <th>Duración</th>
                            <th>Año</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($peliculas_bd as $p): ?>
                            <tr>
                                <td>
                                    <?php if ($p['poster_url']): ?>
                                        <img src="<?= TMDB_IMG_URL . $p['poster_url'] ?>" 
                                             alt="<?= htmlspecialchars($p['titulo']) ?>"
                                             class="admin-poster">
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($p['titulo']) ?></td>
                                <td><?= htmlspecialchars($p['genero']) ?></td>
                                <td><?= $p['duracion'] ?> min</td>
                                <td><?= substr($p['fecha_estreno'] ?? '', 0, 4) ?></td>
                                <td>
                                    <a href="/cinequest/pages/pelicula.php?id=<?= $p['tmdb_id'] ?>" 
                                       class="btn-ver">Ver</a>
                                    <form method="POST" style="display:inline" 
                                          onsubmit="return confirm('¿Seguro que quieres eliminar esta película?')">
                                        <input type="hidden" name="pelicula_id" value="<?= $p['id'] ?>">
                                        <button type="submit" name="eliminar_pelicula" class="btn-eliminar">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>