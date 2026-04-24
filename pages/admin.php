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
 
// Procesar cambio de rol
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_rol'])) {
    $uid = (int)$_POST['usuario_id'];
    $nuevo_rol = $_POST['nuevo_rol'];
    if ($uid === $_SESSION['usuario_id']) {
        $error = 'No puedes cambiar tu propio rol.';
    } elseif (in_array($nuevo_rol, ['usuario', 'admin'])) {
        $stmt = $pdo->prepare('UPDATE usuarios SET rol = ? WHERE id = ?');
        $stmt->execute([$nuevo_rol, $uid]);
        $mensaje = '✅ Rol actualizado correctamente.';
    }
}
 
// Procesar eliminar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_usuario'])) {
    $uid = (int)$_POST['usuario_id'];
    if ($uid === $_SESSION['usuario_id']) {
        $error = 'No puedes eliminar tu propia cuenta.';
    } else {
        $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->execute([$uid]);
        $mensaje = '✅ Usuario eliminado correctamente.';
    }
}
 
// Procesar añadir película
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['añadir_pelicula'])) {
    $tmdb_id = (int)$_POST['tmdb_id'];
    if ($tmdb_id) {
        $stmt = $pdo->prepare('SELECT id FROM peliculas WHERE tmdb_id = ?');
        $stmt->execute([$tmdb_id]);
        if ($stmt->fetch()) {
            $error = 'Esa película ya está en la base de datos.';
        } else {
            $pelicula = obtener_pelicula($tmdb_id);
            if (!$pelicula || isset($pelicula['status_code'])) {
                $error = 'No se encontró la película en TMDB.';
            } else {
                $generos = implode(', ', array_map(fn($g) => $g['name'], $pelicula['genres'] ?? []));
                $stmt = $pdo->prepare('INSERT INTO peliculas (tmdb_id, titulo, genero, duracion, sinopsis, poster_url, fecha_estreno, vote_average, vote_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$tmdb_id, $pelicula['title'], $generos, $pelicula['runtime'] ?? 0, $pelicula['overview'] ?? '', $pelicula['poster_path'] ?? '', $pelicula['release_date'] ?? null, $pelicula['vote_average'] ?? 0, $pelicula['vote_count'] ?? 0]);
                $mensaje = '✅ Película "' . htmlspecialchars($pelicula['title']) . '" añadida.';
            }
        }
    }
}
 
// Procesar eliminar película
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_pelicula'])) {
    $id = (int)$_POST['pelicula_id'];
    $stmt = $pdo->prepare('DELETE FROM peliculas WHERE id = ?');
    $stmt->execute([$id]);
    $mensaje = '✅ Película eliminada correctamente.';
}
 
// Obtener datos
$stmt = $pdo->query('SELECT * FROM usuarios ORDER BY fecha_registro DESC');
$usuarios = $stmt->fetchAll();
 
$stmt = $pdo->query('SELECT * FROM peliculas ORDER BY id DESC');
$peliculas_bd = $stmt->fetchAll();
 
$stmt = $pdo->query('SELECT COUNT(*) as total FROM usuarios');
$total_usuarios = $stmt->fetch()['total'];
 
$stmt = $pdo->query('SELECT COUNT(*) as total FROM historial_visto');
$total_vistas = $stmt->fetch()['total'];
 
$stmt = $pdo->query('SELECT COUNT(*) as total FROM peliculas');
$total_peliculas = $stmt->fetch()['total'];
 
$stmt = $pdo->query('SELECT COUNT(*) as total FROM valoraciones');
$total_valoraciones = $stmt->fetch()['total'];
 
// Usuarios más activos
$stmt = $pdo->query('SELECT u.nombre, u.puntos_xp, u.nivel, COUNT(hv.id) as peliculas_vistas 
                     FROM usuarios u 
                     LEFT JOIN historial_visto hv ON hv.usuario_id = u.id 
                     GROUP BY u.id 
                     ORDER BY peliculas_vistas DESC 
                     LIMIT 5');
$usuarios_activos = $stmt->fetchAll();
 
// Películas más vistas
$stmt = $pdo->query('SELECT pelicula_id, COUNT(*) as veces FROM historial_visto GROUP BY pelicula_id ORDER BY veces DESC LIMIT 5');
$peliculas_populares = $stmt->fetchAll();
 
include '../includes/header.php';
?>
 
<main class="admin-container">
    <h1>⚙️ Panel de Administración</h1>
    <a href="importacion.php" class="btn-importar">📥 Importar películas desde TMDB</a>
 
    <?php if ($mensaje): ?>
        <p class="exito"><?= $mensaje ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>
 
    <!-- ESTADÍSTICAS -->
    <div class="admin-stats">
        <div class="stat-card">
            <span class="stat-numero"><?= $total_usuarios ?></span>
            <span class="stat-label">👥 Usuarios</span>
        </div>
        <div class="stat-card">
            <span class="stat-numero"><?= $total_peliculas ?></span>
            <span class="stat-label">🎬 Películas en BD</span>
        </div>
        <div class="stat-card">
            <span class="stat-numero"><?= $total_vistas ?></span>
            <span class="stat-label">👁️ Vistas totales</span>
        </div>
        <div class="stat-card">
            <span class="stat-numero"><?= $total_valoraciones ?></span>
            <span class="stat-label">⭐ Valoraciones</span>
        </div>
    </div>
 
    <!-- USUARIOS MÁS ACTIVOS -->
    <div class="admin-seccion">
        <h2>🏆 Usuarios más activos</h2>
        <div class="admin-tabla">
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nivel</th>
                        <th>XP</th>
                        <th>Películas vistas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios_activos as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nombre']) ?></td>
                        <td>Nivel <?= $u['nivel'] ?></td>
                        <td><?= $u['puntos_xp'] ?> XP</td>
                        <td><?= $u['peliculas_vistas'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
 
    <!-- GESTIÓN DE USUARIOS -->
    <div class="admin-seccion">
        <h2>👥 Gestión de usuarios</h2>
        <div class="admin-tabla">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Nivel</th>
                        <th>XP</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nombre']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span class="rol-badge <?= $u['rol'] ?>">
                                <?= $u['rol'] === 'admin' ? '⚙️ Admin' : '👤 Usuario' ?>
                            </span>
                        </td>
                        <td>Nivel <?= $u['nivel'] ?? 1 ?></td>
                        <td><?= $u['puntos_xp'] ?> XP</td>
                        <td><?= date('d/m/Y', strtotime($u['fecha_registro'])) ?></td>
                        <td class="acciones-usuario">
                            <?php if ($u['id'] !== $_SESSION['usuario_id']): ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                    <select name="nuevo_rol" class="select-rol">
                                        <option value="usuario" <?= $u['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                                        <option value="admin" <?= $u['rol'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="cambiar_rol" class="btn-ver">Cambiar rol</button>
                                </form>
                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm('¿Eliminar usuario <?= htmlspecialchars($u['nombre']) ?>?')">
                                    <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                    <button type="submit" name="eliminar_usuario" class="btn-eliminar">Eliminar</button>
                                </form>
                            <?php else: ?>
                                <span style="color:#666; font-size:12px">Tu cuenta</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
 
    <!-- AÑADIR PELÍCULA -->
    <div class="admin-seccion">
        <h2>➕ Añadir película a la BD</h2>
        <p class="admin-hint">Busca en <a href="https://www.themoviedb.org" target="_blank">themoviedb.org</a> y copia el ID de la URL.</p>
        <form method="POST" class="admin-form">
            <input type="number" name="tmdb_id" placeholder="ID de TMDB (ej: 550 para Fight Club)" required>
            <button type="submit" name="añadir_pelicula">➕ Añadir película</button>
        </form>
    </div>
 
    <!-- CATÁLOGO BD -->
    <div class="admin-seccion">
        <h2>🎬 Películas en la base de datos (<?= $total_peliculas ?>)</h2>
        <?php if (empty($peliculas_bd)): ?>
            <p class="vacio">No hay películas. Añade algunas o importa desde TMDB.</p>
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
                                    <img src="<?= poster_url($p['poster_url']) ?>" class="admin-poster">
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['titulo']) ?></td>
                            <td><?= htmlspecialchars($p['genero']) ?></td>
                            <td><?= $p['duracion'] ?> min</td>
                            <td><?= substr($p['fecha_estreno'] ?? '', 0, 4) ?></td>
                            <td>
                                <a href="/cinequest/pages/pelicula.php?id=<?= $p['tmdb_id'] ?>" class="btn-ver">Ver</a>
                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm('¿Eliminar esta película?')">
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