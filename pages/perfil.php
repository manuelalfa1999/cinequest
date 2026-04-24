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
require_once '../includes/niveles.php';
 
$usuario_id = $_SESSION['usuario_id'];
 
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();
 
$xp = $usuario['puntos_xp'];
$nivel_actual = calcular_nivel($xp);
$nivel_datos = get_nivel_datos($nivel_actual);
$progreso = progreso_nivel($xp);
$xp_siguiente = xp_para_siguiente_nivel($xp);
 
$stmt = $pdo->prepare('SELECT pelicula_id, fecha, estado_animo, compania FROM historial_visto WHERE usuario_id = ? ORDER BY fecha DESC');
$stmt->execute([$usuario_id]);
$historial = $stmt->fetchAll();
 
$stmt = $pdo->prepare('SELECT r.*, 
                              COALESCE(ur.progreso, 0) as progreso_usuario,
                              COALESCE(ur.completado, 0) as completado
                       FROM retos r
                       LEFT JOIN usuario_retos ur ON ur.reto_id = r.id AND ur.usuario_id = ?
                       ORDER BY r.nivel_requerido ASC, r.categoria ASC, r.id ASC');
$stmt->execute([$usuario_id]);
$todos_retos = $stmt->fetchAll();
 
$categorias = ['bronce', 'plata', 'oro', 'legendario', 'platino'];
$retos_por_categoria = [];
foreach ($categorias as $cat) {
    $retos_por_categoria[$cat] = array_filter($todos_retos, fn($r) => $r['categoria'] === $cat);
}
 
$total_vistas = count($historial);
$retos_completados = count(array_filter($todos_retos, fn($r) => $r['completado']));
 
include '../includes/header.php';
?>
 
<?php if (isset($_SESSION['notificacion_nivel'])): ?>
<div id="notificacion-nivel" class="notificacion-nivel-overlay">
    <div class="notificacion-nivel-box">
        <div class="notificacion-nivel-icono"><?= $nivel_datos['icono'] ?></div>
        <h2>¡Subiste de nivel!</h2>
        <p class="notificacion-nivel-nombre"><?= htmlspecialchars($nivel_datos['nombre']) ?></p>
        <p class="notificacion-nivel-sub">Nivel <?= $nivel_actual ?> alcanzado</p>
        <button onclick="cerrarNotificacion()" class="btn-cerrar-notificacion">¡Genial!</button>
    </div>
</div>
<?php unset($_SESSION['notificacion_nivel']); ?>
<?php endif; ?>
 
<main class="perfil-container">
 
    <div class="perfil-header">
        <div class="perfil-avatar"><?= $nivel_datos['icono'] ?></div>
        <div class="perfil-datos">
            <h1><?= htmlspecialchars($usuario['nombre']) ?></h1>
            <p class="perfil-email"><?= htmlspecialchars($usuario['email']) ?></p>
            <p class="perfil-fecha">Miembro desde <?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></p>
            <div class="nivel-badge" style="background:<?= get_color_categoria($nivel_actual >= 5 ? 'platino' : ($nivel_actual >= 4 ? 'legendario' : ($nivel_actual >= 3 ? 'oro' : ($nivel_actual >= 2 ? 'plata' : 'bronce')))) ?>22; border-color:<?= get_color_categoria($nivel_actual >= 5 ? 'platino' : ($nivel_actual >= 4 ? 'legendario' : ($nivel_actual >= 3 ? 'oro' : ($nivel_actual >= 2 ? 'plata' : 'bronce')))) ?>">
                Nivel <?= $nivel_actual ?> — <?= htmlspecialchars($nivel_datos['nombre']) ?>
            </div>
        </div>
        <div class="perfil-xp">
            <span class="xp-numero"><?= $xp ?></span>
            <span class="xp-label">puntos XP</span>
        </div>
    </div>
 
    <div class="nivel-progreso-container">
        <div class="nivel-progreso-header">
            <span>Nivel <?= $nivel_actual ?> — <?= $nivel_datos['nombre'] ?></span>
            <?php if ($nivel_actual < 5): ?>
                <span><?= $xp_siguiente ?> XP para nivel <?= $nivel_actual + 1 ?></span>
            <?php else: ?>
                <span>🏆 Nivel máximo alcanzado</span>
            <?php endif; ?>
        </div>
        <div class="nivel-barra">
            <div class="nivel-fill" style="width:<?= $progreso ?>%"></div>
        </div>
        <div class="nivel-iconos">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="nivel-icono <?= $i <= $nivel_actual ? 'activo' : '' ?>" title="Nivel <?= $i ?>">
                    <?= get_nivel_datos($i)['icono'] ?>
                </span>
            <?php endfor; ?>
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
            <span class="stat-numero"><?= $xp ?></span>
            <span class="stat-label">⭐ Puntos XP</span>
        </div>
        <div class="stat-card">
            <span class="stat-numero"><?= $nivel_actual ?></span>
            <span class="stat-label"><?= $nivel_datos['icono'] ?> Nivel actual</span>
        </div>
    </div>
 
    <div class="perfil-seccion">
        <h2>🏆 Mis Retos</h2>
        <?php foreach ($categorias as $cat): ?>
            <?php $retos_cat = $retos_por_categoria[$cat]; ?>
            <?php if (empty($retos_cat)) continue; ?>
            <div class="categoria-retos">
                <div class="categoria-header" style="border-color:<?= get_color_categoria($cat) ?>; color:<?= get_color_categoria($cat) ?>">
                    <?= get_icono_categoria($cat) ?> Retos <?= ucfirst($cat) ?>
                    <?php
                    $completados_cat = count(array_filter($retos_cat, fn($r) => $r['completado']));
                    $total_cat = count($retos_cat);
                    ?>
                    <span class="categoria-progreso"><?= $completados_cat ?>/<?= $total_cat ?></span>
                </div>
                <div class="retos-lista">
                    <?php foreach ($retos_cat as $reto): ?>
                        <?php $bloqueado = $nivel_actual < $reto['nivel_requerido']; ?>
                        <div class="reto-card <?= $reto['completado'] ? 'completado' : '' ?> <?= $bloqueado ? 'bloqueado' : '' ?>"
                             style="<?= $reto['completado'] ? 'border-color:'.get_color_categoria($cat) : '' ?>">
                            <div class="reto-header">
                                <h3><?= $bloqueado ? '🔒 ' : '' ?><?= htmlspecialchars($reto['titulo']) ?></h3>
                                <span class="reto-xp" style="color:<?= get_color_categoria($cat) ?>">+<?= $reto['puntos_xp'] ?> XP</span>
                            </div>
                            <p class="reto-desc">
                                <?= $bloqueado ? 'Desbloquea en nivel '.$reto['nivel_requerido'] : htmlspecialchars($reto['descripcion']) ?>
                            </p>
                            <?php if (!$bloqueado): ?>
                            <div class="progreso-container">
                                <div class="progreso-barra">
                                    <div class="progreso-fill" style="width:<?= min(100, ($reto['progreso_usuario'] / $reto['objetivo']) * 100) ?>%; background:<?= get_color_categoria($cat) ?>"></div>
                                </div>
                                <span class="progreso-texto">
                                    <?php if ($reto['completado']): ?>
                                        ✅ Completado
                                    <?php else: ?>
                                        <?= $reto['progreso_usuario'] ?> / <?= $reto['objetivo'] ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
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
                        <img src="<?= poster_url($pelicula['poster_path'] ?? '') ?>"
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
 
<script>
function cerrarNotificacion() {
    document.getElementById('notificacion-nivel').style.display = 'none';
}
</script>
 
<?php include '../includes/footer.php'; ?>