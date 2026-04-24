<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../api/tmdb.php';

$mapa_animo = [
    'alegre'    => [35, 12, 16],
    'triste'    => [18, 10749],
    'relajado'  => [99, 18, 10751],
    'accion'    => [28, 53, 80],
    'intrigado' => [27, 9648, 53],
    'romantico' => [10749, 35, 18],
    'aventurero'=> [12, 14, 878],
];

$mapa_compania = [
    'solo'      => [27, 878, 53, 80],
    'pareja'    => [10749, 35, 18],
    'amigos'    => [28, 35, 12, 80],
    'familia'   => [10751, 16, 12, 35],
    'primera'   => [10749, 35, 12],
];

$mapa_tiempo = [
    '30min'  => 35,
    '1h'     => 65,
    '1h30'   => 95,
    '2h'     => 125,
    'mas2h'  => 999,
];

$mapa_momento = [
    'manana'    => [35, 10751, 16],
    'tarde'     => [28, 12, 35],
    'noche'     => [53, 27, 9648],
    'madrugada' => [27, 878, 53],
];

$mapa_decada = [
    '80s'     => ['1980-01-01', '1989-12-31'],
    '90s'     => ['1990-01-01', '1999-12-31'],
    '2000s'   => ['2000-01-01', '2009-12-31'],
    '2010s'   => ['2010-01-01', '2019-12-31'],
    'reciente'=> ['2020-01-01', date('Y-m-d')],
];

$mapa_idioma = [
    'es' => 'Español',
    'en' => 'Inglés',
    'fr' => 'Francés',
    'ja' => 'Japonés',
    'it' => 'Italiano',
    'de' => 'Alemán',
    'ko' => 'Coreano',
];

$peliculas = [];
$buscado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $buscado        = true;
    $animo          = $_POST['animo'] ?? '';
    $compania       = $_POST['compania'] ?? '';
    $tiempo         = $_POST['tiempo'] ?? '';
    $concentracion  = $_POST['concentracion'] ?? '';
    $momento        = $_POST['momento'] ?? '';
    $decada         = $_POST['decada'] ?? '';
    $idioma         = $_POST['idioma'] ?? '';
    $puntuacion_min = (float)($_POST['puntuacion_min'] ?? 0);

    $generos_animo    = $mapa_animo[$animo] ?? [];
    $generos_compania = $mapa_compania[$compania] ?? [];
    $generos_momento  = $mapa_momento[$momento] ?? [];

    $generos_comunes = array_intersect($generos_animo, $generos_compania);
    $generos_finales = !empty($generos_comunes) ? $generos_comunes : $generos_animo;

    if (!empty($generos_momento)) {
        $mix = array_intersect($generos_finales, $generos_momento);
        if (!empty($mix)) $generos_finales = $mix;
    }

    $genero_id = $generos_finales[array_key_first($generos_finales)] ?? 28;
    $duracion_max = $mapa_tiempo[$tiempo] ?? null;

    $params = ['with_genres' => $genero_id, 'sort_by' => 'popularity.desc'];

    if ($duracion_max && $duracion_max !== 999) {
        $params['with_runtime.lte'] = $duracion_max;
    }

    if ($concentracion === 'compleja' || $concentracion === 'giros') {
        $params['sort_by'] = 'vote_average.desc';
        $params['vote_average.gte'] = 7;
        $params['vote_count.gte'] = $concentracion === 'compleja' ? 200 : 100;
    } elseif ($concentracion === 'normal') {
        $params['sort_by'] = 'vote_average.desc';
    }

    if ($decada && isset($mapa_decada[$decada])) {
        $params['primary_release_date.gte'] = $mapa_decada[$decada][0];
        $params['primary_release_date.lte'] = $mapa_decada[$decada][1];
    }

    if ($idioma) {
        $params['with_original_language'] = $idioma;
    }

    if ($puntuacion_min > 0) {
        $params['vote_average.gte'] = $puntuacion_min;
        $params['vote_count.gte'] = 50;
    }

    $resultado = tmdb_get('/discover/movie', $params);
    if (!$resultado) $resultado = obtener_peliculas_por_genero($genero_id, $duracion_max !== 999 ? $duracion_max : null);
    $peliculas = $resultado['results'] ?? [];

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

        <div class="campo">
            <label>💡 ¿Qué nivel de concentración tienes?</label>
            <div class="opciones-grid">
                <label class="opcion <?= ($_POST['concentracion'] ?? '') === 'facil' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="concentracion" value="facil" required>
                    😴 Fácil de seguir
                </label>
                <label class="opcion <?= ($_POST['concentracion'] ?? '') === 'normal' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="concentracion" value="normal">
                    🧠 Normal
                </label>
                <label class="opcion <?= ($_POST['concentracion'] ?? '') === 'compleja' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="concentracion" value="compleja">
                    🤯 Compleja y profunda
                </label>
                <label class="opcion <?= ($_POST['concentracion'] ?? '') === 'giros' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="concentracion" value="giros">
                    🌀 Con giros de guión
                </label>
            </div>
        </div>

        <div class="campo">
            <label>⏳ ¿En qué momento del día estás?</label>
            <div class="opciones-grid">
                <label class="opcion <?= ($_POST['momento'] ?? '') === 'manana' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="momento" value="manana" required>
                    🌅 Mañana
                </label>
                <label class="opcion <?= ($_POST['momento'] ?? '') === 'tarde' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="momento" value="tarde">
                    ☀️ Tarde
                </label>
                <label class="opcion <?= ($_POST['momento'] ?? '') === 'noche' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="momento" value="noche">
                    🌙 Noche
                </label>
                <label class="opcion <?= ($_POST['momento'] ?? '') === 'madrugada' ? 'seleccionada' : '' ?>">
                    <input type="radio" name="momento" value="madrugada">
                    🌃 Madrugada
                </label>
            </div>
        </div>

        <div class="filtros-extra">
            <h3>🔧 Filtros adicionales (opcionales)</h3>
            <div class="filtros-extra-grid">

                <div class="filtro-extra-campo">
                    <label>📅 Década</label>
                    <select name="decada">
                        <option value="">Cualquier época</option>
                        <option value="80s" <?= ($_POST['decada'] ?? '') === '80s' ? 'selected' : '' ?>>🕹️ Años 80</option>
                        <option value="90s" <?= ($_POST['decada'] ?? '') === '90s' ? 'selected' : '' ?>>📼 Años 90</option>
                        <option value="2000s" <?= ($_POST['decada'] ?? '') === '2000s' ? 'selected' : '' ?>>💿 Años 2000</option>
                        <option value="2010s" <?= ($_POST['decada'] ?? '') === '2010s' ? 'selected' : '' ?>>📱 Años 2010</option>
                        <option value="reciente" <?= ($_POST['decada'] ?? '') === 'reciente' ? 'selected' : '' ?>>🆕 Reciente (2020+)</option>
                    </select>
                </div>

                <div class="filtro-extra-campo">
                    <label>🌍 Idioma original</label>
                    <select name="idioma">
                        <option value="">Cualquier idioma</option>
                        <?php foreach ($mapa_idioma as $codigo => $nombre): ?>
                            <option value="<?= $codigo ?>" <?= ($_POST['idioma'] ?? '') === $codigo ? 'selected' : '' ?>>
                                <?= $nombre ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtro-extra-campo">
                    <label>⭐ Puntuación mínima</label>
                    <select name="puntuacion_min">
                        <option value="0">Cualquier puntuación</option>
                        <option value="6" <?= ($_POST['puntuacion_min'] ?? '') == '6' ? 'selected' : '' ?>>6+ — Buena</option>
                        <option value="7" <?= ($_POST['puntuacion_min'] ?? '') == '7' ? 'selected' : '' ?>>7+ — Muy buena</option>
                        <option value="8" <?= ($_POST['puntuacion_min'] ?? '') == '8' ? 'selected' : '' ?>>8+ — Excelente</option>
                    </select>
                </div>

            </div>
        </div>

        <div id="aviso-filtros" style="display:none; background:#4fc3f722; border:1px solid #4fc3f7; color:#4fc3f7; padding:12px 20px; border-radius:8px; margin-bottom:15px;">
            ⚠️ Para una buena recomendación selecciona todas las opciones principales.
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
                            <img src="<?= poster_url($pelicula['poster_path']) ?>"
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