<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../api/tmdb.php';

// Mapa de estado de ánimo → géneros TMDB
$mapa_animo = [
    'alegre'    => [35, 12, 16],        // Comedia, Aventura, Animación
    'triste'    => [18, 10749],          // Drama, Romance
    'relajado'  => [99, 18, 10751],     // Documental, Drama, Familia
    'accion'    => [28, 53, 80],         // Acción, Thriller, Crimen
    'intrigado' => [27, 9648, 53],       // Terror, Misterio, Thriller
    'romantico' => [10749, 35, 18],      // Romance, Comedia, Drama
    'aventurero'=> [12, 14, 878],        // Aventura, Fantasía, Sci-Fi
];

// Mapa de compañía → géneros recomendados
$mapa_compania = [
    'solo'      => [27, 878, 53, 80],   // Terror, Sci-Fi, Thriller, Crimen
    'pareja'    => [10749, 35, 18],      // Romance, Comedia, Drama
    'amigos'    => [28, 35, 12, 80],    // Acción, Comedia, Aventura, Crimen
    'familia'   => [10751, 16, 12, 35], // Familia, Animación, Aventura, Comedia
    'primera'   => [10749, 35, 12],     // Romance, Comedia, Aventura
];

// Mapa de tiempo → duración máxima en minutos
$mapa_tiempo = [
    '30min'  => 35,
    '1h'     => 65,
    '1h30'   => 95,
    '2h'     => 125,
    'mas2h'  => 999,
];

$peliculas = [];
$buscado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $buscado = true;
    $animo    = $_POST['animo'] ?? '';
    $compania = $_POST['compania'] ?? '';
    $tiempo   = $_POST['tiempo'] ?? '';

    // Combinar géneros de ánimo y compañía
    $generos_animo    = $mapa_animo[$animo] ?? [];
    $generos_compania = $mapa_compania[$compania] ?? [];

    // Intersección primero, si vacía usar solo ánimo
    $generos_comunes = array_intersect($generos_animo, $generos_compania);
    $generos_finales = !empty($generos_comunes) ? $generos_comunes : $generos_animo;

    $duracion_max = $mapa_tiempo[$tiempo] ?? null;
    $genero_id = $generos_finales[array_key_first($generos_finales)] ?? 28;

    $resultado = obtener_peliculas_por_genero($genero_id, $duracion_max !== 999 ? $duracion_max : null);
    $peliculas = $resultado['results'] ?? [];

    // Guardar contexto en sesión para historial
    $_SESSION['ultimo_contexto'] = [
        'animo'    => $animo,
        'compania' => $compania,
        'tiempo'   => $tiempo,
    ];
}
?>
<?php include '../includes/header.php'; ?>

<main class="recomendador-container">
    <h1>🎯 ¿Qué película vemos hoy?</h1>
    <p class="subtitulo">Cuéntanos cómo estás y te recomendamos la película perfecta</p>

    <form method="POST" class="form-recomendador">

        <div class="campo">
            <label>😊 ¿Cómo te sientes ahora mismo?</label>
            <div class="opciones-grid">
                <label class="opcion <?= ($_POST['animo'] ?? '') === 'alegre' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="animo" value="alegre" required>
                    😄 Alegre / con energía
                </label>
                <label class="opcion <?= ($_POST['animo'] ?? '') === 'triste' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="animo" value="triste">
                    😢 Triste / melancólico
                </label>
                <label class="opcion <?= ($_POST['animo'] ?? '') === 'relajado' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="animo" value="relajado">
                    😌 Relajado / tranquilo
                </label>
                <label class="opcion <?= ($_POST['animo'] ?? '') === 'accion' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="animo" value="accion">
                    💪 Con ganas de acción
                </label>
                <label class="opcion <?= ($_POST['animo'] ?? '') === 'intrigado' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="animo" value="intrigado">
                    😱 Asustadizo / intrigado
                </label>
                <label class="opcion <?= ($_POST['animo'] ?? '') === 'romantico' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="animo" value="romantico">
                    ❤️ Romántico
                </label>
                <label class="opcion <?= ($_POST['animo'] ?? '') === 'aventurero' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="animo" value="aventurero">
                    🧭 Aventurero
                </label>
            </div>
        </div>

        <div class="campo">
            <label>👥 ¿Con quién vas a verla?</label>
            <div class="opciones-grid">
                <label class="opcion <?= ($_POST['compania'] ?? '') === 'solo' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="compania" value="solo" required>
                    🧍 Solo
                </label>
                <label class="opcion <?= ($_POST['compania'] ?? '') === 'pareja' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="compania" value="pareja">
                    👫 En pareja
                </label>
                <label class="opcion <?= ($_POST['compania'] ?? '') === 'amigos' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="compania" value="amigos">
                    👯 Con amigos
                </label>
                <label class="opcion <?= ($_POST['compania'] ?? '') === 'familia' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="compania" value="familia">
                    👨‍👩‍👧 En familia
                </label>
                <label class="opcion <?= ($_POST['compania'] ?? '') === 'primera' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="compania" value="primera">
                    🌹 Primera película juntos
                </label>
            </div>
        </div>

        <div class="campo">
            <label>⏱️ ¿Cuánto tiempo tienes?</label>
            <div class="opciones-grid">
                <label class="opcion <?= ($_POST['tiempo'] ?? '') === '30min' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="tiempo" value="30min" required>
                    ⚡ 30 minutos
                </label>
                <label class="opcion <?= ($_POST['tiempo'] ?? '') === '1h' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="tiempo" value="1h">
                    🕐 Menos de 1 hora
                </label>
                <label class="opcion <?= ($_POST['tiempo'] ?? '') === '1h30' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="tiempo" value="1h30">
                    🕑 1 a 1.5 horas
                </label>
                <label class="opcion <?= ($_POST['tiempo'] ?? '') === '2h' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="tiempo" value="2h">
                    🕒 1.5 a 2 horas
                </label>
                <label class="opcion <?= ($_POST['tiempo'] ?? '') === 'mas2h' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="tiempo" value="mas2h">
                    🎬 Más de 2 horas
                </label>
            </div>
        </div>
        <div id="aviso-filtros" style="display:none; background:#e9456022; border:1px solid #e94560; color:#e94560; padding:12px 20px; border-radius:8px; margin-bottom:15px;">
            ⚠️ Para una buena recomendación selecciona las tres opciones — cómo te sientes, con quién y cuánto tiempo tienes.
        </div>

        <button type="submit" class="btn-recomendar">🎯 Recomendar películas</button>
    </form>

    <?php if ($buscado): ?>
        <div class="resultados">
            <h2>🎬 Películas recomendadas para ti</h2>
            <?php if (empty($peliculas)): ?>
                <p>No encontramos películas con esos criterios. Prueba otra combinación.</p>
            <?php else: ?>
                <div class="grid-peliculas">
                    <?php foreach ($peliculas as $pelicula): ?>
                        <?php if (empty($pelicula['poster_path'])) continue; ?>
                        <a href="pelicula.php?id=<?= $pelicula['id'] ?>" class="tarjeta-pelicula">
                            <img src="<?= TMDB_IMG_URL . $pelicula['poster_path'] ?>"
                                 alt="<?= htmlspecialchars($pelicula['title']) ?>">
                            <div class="tarjeta-info">
                                <h3><?= htmlspecialchars($pelicula['title']) ?></h3>
                                <p class="fecha"><?= substr($pelicula['release_date'] ?? '', 0, 4) ?></p>
                                <p class="puntuacion">⭐ <?= number_format($pelicula['vote_average'], 1) ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>