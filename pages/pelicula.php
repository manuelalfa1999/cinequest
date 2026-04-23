<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../api/tmdb.php';
require_once '../config/db.php';

function actualizarRetos($pdo, $usuario_id, $pelicula) {
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM historial_visto WHERE usuario_id = ?');
    $stmt->execute([$usuario_id]);
    $total_vistas = $stmt->fetch()['total'];

    $stmt = $pdo->prepare('SELECT r.id, r.objetivo FROM retos r 
                           LEFT JOIN usuario_retos ur ON ur.reto_id = r.id AND ur.usuario_id = ?
                           WHERE r.tipo = "peliculas_vistas" AND (ur.completado IS NULL OR ur.completado = 0)');
    $stmt->execute([$usuario_id]);
    $retos = $stmt->fetchAll();

    foreach ($retos as $reto) {
        upsertReto($pdo, $usuario_id, $reto['id'], $total_vistas, $reto['objetivo']);
    }

    if (($pelicula['runtime'] ?? 0) >= 120) {
        $stmt = $pdo->prepare('SELECT r.id FROM retos r 
                               LEFT JOIN usuario_retos ur ON ur.reto_id = r.id AND ur.usuario_id = ?
                               WHERE r.tipo = "duracion" AND (ur.completado IS NULL OR ur.completado = 0)');
        $stmt->execute([$usuario_id]);
        $retos_duracion = $stmt->fetchAll();

        foreach ($retos_duracion as $reto) {
            upsertReto($pdo, $usuario_id, $reto['id'], 120, 120);
        }
    }
}

function upsertReto($pdo, $usuario_id, $reto_id, $progreso, $objetivo) {
    $stmt = $pdo->prepare('SELECT id FROM usuario_retos WHERE usuario_id = ? AND reto_id = ?');
    $stmt->execute([$usuario_id, $reto_id]);
    $existe = $stmt->fetch();

    $completado = $progreso >= $objetivo ? 1 : 0;
    $fecha = $completado ? date('Y-m-d H:i:s') : null;

    if ($existe) {
        $stmt = $pdo->prepare('UPDATE usuario_retos SET progreso = ?, completado = ?, fecha_completado = ? 
                               WHERE usuario_id = ? AND reto_id = ?');
        $stmt->execute([$progreso, $completado, $fecha, $usuario_id, $reto_id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO usuario_retos (usuario_id, reto_id, progreso, completado, fecha_completado) 
                               VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$usuario_id, $reto_id, $progreso, $completado, $fecha]);
    }

    if ($completado) {
        $stmt = $pdo->prepare('SELECT puntos_xp FROM retos WHERE id = ?');
        $stmt->execute([$reto_id]);
        $xp = $stmt->fetch()['puntos_xp'] ?? 0;
        $stmt = $pdo->prepare('UPDATE usuarios SET puntos_xp = puntos_xp + ? WHERE id = ?');
        $stmt->execute([$xp, $usuario_id]);
    }
}

$tmdb_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$tmdb_id) {
    header('Location: peliculas.php');
    exit;
}

$pelicula = obtener_pelicula($tmdb_id);
if (!$pelicula || isset($pelicula['status_code'])) {
    header('Location: peliculas.php');
    exit;
}

$trailer_key = obtener_trailer($tmdb_id);
$usuario_id = $_SESSION['usuario_id'];
$mensaje = '';
$mensaje_valoracion = '';

// Comprobar si ya está marcada como vista
$stmt = $pdo->prepare('SELECT id FROM historial_visto WHERE usuario_id = ? AND pelicula_id = ?');
$stmt->execute([$usuario_id, $tmdb_id]);
$ya_vista = $stmt->fetch();

// Comprobar si ya tiene valoración
$stmt = $pdo->prepare('SELECT * FROM valoraciones WHERE usuario_id = ? AND pelicula_id = ?');
$stmt->execute([$usuario_id, $tmdb_id]);
$mi_valoracion = $stmt->fetch();

// Procesar marcar como vista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_vista'])) {
    if (!$ya_vista) {
        $contexto = $_SESSION['ultimo_contexto'] ?? [];
        $stmt = $pdo->prepare('INSERT INTO historial_visto (usuario_id, pelicula_id, estado_animo, compania) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $usuario_id,
            $tmdb_id,
            $contexto['animo'] ?? null,
            $contexto['compania'] ?? null,
        ]);
        actualizarRetos($pdo, $usuario_id, $pelicula);
        $ya_vista = true;
        $mensaje = '✅ ¡Película marcada como vista!';
    } else {
        $mensaje = 'Ya habías marcado esta película como vista.';
    }
}

// Procesar quitar vista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quitar_vista']) && $_POST['quitar_vista'] == '1') {
    $stmt = $pdo->prepare('DELETE FROM historial_visto WHERE usuario_id = ? AND pelicula_id = ?');
    $stmt->execute([$usuario_id, $tmdb_id]);

    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM historial_visto WHERE usuario_id = ?');
    $stmt->execute([$usuario_id]);
    $total_vistas = $stmt->fetch()['total'];

    $stmt = $pdo->prepare('SELECT r.id, r.objetivo FROM retos r 
                           INNER JOIN usuario_retos ur ON ur.reto_id = r.id AND ur.usuario_id = ?
                           WHERE r.tipo = "peliculas_vistas"');
    $stmt->execute([$usuario_id]);
    $retos = $stmt->fetchAll();

    foreach ($retos as $reto) {
        $completado = $total_vistas >= $reto['objetivo'] ? 1 : 0;
        $stmt = $pdo->prepare('UPDATE usuario_retos SET progreso = ?, completado = ?, fecha_completado = ?
                               WHERE usuario_id = ? AND reto_id = ?');
        $stmt->execute([$total_vistas, $completado, null, $usuario_id, $reto['id']]);
    }

    if (($pelicula['runtime'] ?? 0) >= 120) {
        $stmt = $pdo->prepare('SELECT pelicula_id FROM historial_visto WHERE usuario_id = ?');
        $stmt->execute([$usuario_id]);
        $otras = $stmt->fetchAll();

        $tiene_otra_larga = false;
        foreach ($otras as $otra) {
            $datos = obtener_pelicula($otra['pelicula_id']);
            if (($datos['runtime'] ?? 0) >= 120) {
                $tiene_otra_larga = true;
                break;
            }
        }

        if (!$tiene_otra_larga) {
            $stmt = $pdo->prepare('SELECT id, puntos_xp FROM retos WHERE tipo = "duracion"');
            $stmt->execute();
            $reto_dur = $stmt->fetch();

            if ($reto_dur) {
                $stmt = $pdo->prepare('UPDATE usuario_retos SET progreso = 0, completado = 0, fecha_completado = NULL
                                       WHERE usuario_id = ? AND reto_id = ?');
                $stmt->execute([$usuario_id, $reto_dur['id']]);

                $stmt = $pdo->prepare('UPDATE usuarios SET puntos_xp = GREATEST(0, puntos_xp - ?) WHERE id = ?');
                $stmt->execute([$reto_dur['puntos_xp'], $usuario_id]);
            }
        }
    }

    $ya_vista = false;
    $mensaje = '↩️ Película eliminada de tu historial.';
}

// Procesar valoración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_valoracion'])) {
    $puntuacion = (int)$_POST['puntuacion'];
    $comentario = trim($_POST['comentario'] ?? '');

    if ($puntuacion < 1 || $puntuacion > 5) {
        $mensaje_valoracion = '⚠️ Selecciona una puntuación entre 1 y 5 estrellas.';
    } else {
        if ($mi_valoracion) {
            $stmt = $pdo->prepare('UPDATE valoraciones SET puntuacion = ?, comentario = ?, fecha = NOW() WHERE usuario_id = ? AND pelicula_id = ?');
            $stmt->execute([$puntuacion, $comentario, $usuario_id, $tmdb_id]);
            $mensaje_valoracion = '✅ Valoración actualizada correctamente.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO valoraciones (usuario_id, pelicula_id, puntuacion, comentario) VALUES (?, ?, ?, ?)');
            $stmt->execute([$usuario_id, $tmdb_id, $puntuacion, $comentario]);
            $mensaje_valoracion = '✅ Valoración guardada correctamente.';
        }
        $stmt = $pdo->prepare('SELECT * FROM valoraciones WHERE usuario_id = ? AND pelicula_id = ?');
        $stmt->execute([$usuario_id, $tmdb_id]);
        $mi_valoracion = $stmt->fetch();
    }
}

// Obtener todas las valoraciones de la película
$stmt = $pdo->prepare('SELECT v.*, u.nombre FROM valoraciones v 
                       JOIN usuarios u ON u.id = v.usuario_id 
                       WHERE v.pelicula_id = ? 
                       ORDER BY v.fecha DESC');
$stmt->execute([$tmdb_id]);
$valoraciones = $stmt->fetchAll();

$media_valoracion = 0;
if (!empty($valoraciones)) {
    $media_valoracion = array_sum(array_column($valoraciones, 'puntuacion')) / count($valoraciones);
}

$generos = array_map(fn($g) => $g['name'], $pelicula['genres'] ?? []);
$horas = intdiv($pelicula['runtime'] ?? 0, 60);
$minutos = ($pelicula['runtime'] ?? 0) % 60;

include '../includes/header.php';
?>

<main class="detalle-container">
    <div class="detalle-hero">
        <img src="<?= poster_url($pelicula['poster_path'] ?? '') ?>"
             alt="<?= htmlspecialchars($pelicula['title']) ?>"
             class="detalle-poster">

        <div class="detalle-info">
            <h1><?= htmlspecialchars($pelicula['title']) ?></h1>

            <?php if (!empty($pelicula['tagline'])): ?>
                <p class="tagline">"<?= htmlspecialchars($pelicula['tagline']) ?>"</p>
            <?php endif; ?>

            <div class="detalle-meta">
                <span>📅 <?= substr($pelicula['release_date'] ?? '', 0, 4) ?></span>
                <?php if ($pelicula['runtime']): ?>
                    <span>⏱️ <?= $horas ?>h <?= $minutos ?>min</span>
                <?php endif; ?>
                <span>⭐ <?= number_format($pelicula['vote_average'], 1) ?>/10</span>
                <?php if (!empty($valoraciones)): ?>
                    <span>🌟 <?= number_format($media_valoracion, 0) ?>/5 (<?= count($valoraciones) ?> reseñas)</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($generos)): ?>
                <div class="generos">
                    <?php foreach ($generos as $genero): ?>
                        <span class="tag-genero"><?= htmlspecialchars($genero) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p class="sinopsis"><?= htmlspecialchars($pelicula['overview'] ?? 'Sin sinopsis disponible.') ?></p>

            <?php if ($trailer_key): ?>
                <div class="trailer-container">
                    <h3>🎬 Trailer oficial</h3>
                    <iframe
                        src="https://www.youtube.com/embed/<?= htmlspecialchars($trailer_key) ?>?rel=0"
                        title="Trailer"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
            <?php endif; ?>

            <?php if ($mensaje): ?>
                <p class="mensaje-vista"><?= $mensaje ?></p>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="tmdb_id_form" value="<?= $tmdb_id ?>">
                <?php if (!$ya_vista): ?>
                    <button type="submit" name="marcar_vista" class="btn-vista">
                        🎬 Marcar como vista
                    </button>
                <?php else: ?>
                    <button type="submit" name="quitar_vista" value="1" class="btn-vista quitar">
                        ❌ Quitar de vistas
                    </button>
                <?php endif; ?>
            </form>

            <a href="javascript:history.back()" class="btn-volver">← Volver</a>
        </div>
    </div>

    <?php if ($ya_vista): ?>
    <div class="valoracion-seccion">
        <h2>⭐ Tu valoración</h2>

        <?php if ($mensaje_valoracion): ?>
            <p class="<?= str_starts_with($mensaje_valoracion, '✅') ? 'exito' : 'error' ?>"><?= $mensaje_valoracion ?></p>
        <?php endif; ?>

        <form method="POST" class="form-valoracion">
            <div class="estrellas-input">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="puntuacion" id="star<?= $i ?>" value="<?= $i ?>"
                           >
                    <label for="star<?= $i ?>">★</label>
                <?php endfor; ?>
            </div>
            <textarea name="comentario" placeholder="Escribe tu crítica (opcional)..." rows="4"></textarea>
            <button type="submit" name="guardar_valoracion" class="btn-valorar">
                <?= $mi_valoracion ? '✏️ Actualizar valoración' : '⭐ Guardar valoración' ?>
            </button>
        </form>
    </div>
    <?php endif; ?>

    <?php if (!empty($valoraciones)): ?>
    <div class="resenas-seccion">
        <h2>💬 Reseñas de la comunidad (<?= count($valoraciones) ?>)</h2>
        <div class="resenas-lista">
            <?php foreach ($valoraciones as $v): ?>
                <div class="resena-card <?= $v['usuario_id'] == $usuario_id ? 'mi-resena' : '' ?>">
                    <div class="resena-header">
                        <span class="resena-autor">👤 <?= htmlspecialchars($v['nombre']) ?></span>
                        <span class="resena-estrellas">
                            <?= str_repeat('★', $v['puntuacion']) ?><?= str_repeat('☆', 5 - $v['puntuacion']) ?>
                        </span>
                        <span class="resena-fecha"><?= date('d/m/Y', strtotime($v['fecha'])) ?></span>
                    </div>
                    <?php if (!empty($v['comentario'])): ?>
                        <p class="resena-comentario"><?= htmlspecialchars($v['comentario']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</main>

<?php include '../includes/footer.php'; ?>