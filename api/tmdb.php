<?php
require_once __DIR__ . '/../config/db.php';

function tmdb_get($endpoint, $params = []) {
    $params['api_key'] = TMDB_API_KEY;
    $params['language'] = 'es-ES';
    $url = TMDB_BASE_URL . $endpoint . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || !$response) return null;

    $datos = json_decode($response, true);
    if (!$datos || isset($datos['status_code'])) return null;

    return $datos;
}

function tmdb_disponible() {
    $resultado = tmdb_get('/configuration');
    return $resultado !== null;
}

function poster_url($poster_path) {
    if (empty($poster_path)) return '';
    if (str_starts_with($poster_path, '/cinequest')) return $poster_path;
    if (str_starts_with($poster_path, 'http')) return $poster_path;
    return TMDB_IMG_URL . $poster_path;
}

function obtener_peliculas_populares($pagina = 1) {
    $resultado = tmdb_get('/movie/popular', ['page' => $pagina]);
    if ($resultado) return $resultado;
    return fallback_peliculas_populares($pagina);
}

function obtener_mejor_valoradas() {
    $resultado = tmdb_get('/movie/top_rated');
    if ($resultado) return $resultado;
    return fallback_mejor_valoradas();
}

function obtener_estrenos() {
    $resultado = tmdb_get('/movie/now_playing');
    if ($resultado) return $resultado;
    return fallback_estrenos();
}

function buscar_pelicula($query) {
    $resultado = tmdb_get('/search/movie', ['query' => $query]);
    if ($resultado) return $resultado;
    return fallback_buscar($query);
}

function obtener_pelicula($tmdb_id) {
    $resultado = tmdb_get('/movie/' . $tmdb_id);
    if ($resultado) return $resultado;
    return fallback_pelicula($tmdb_id);
}

function obtener_peliculas_por_genero($genero_id, $duracion_max = null) {
    $params = ['with_genres' => $genero_id, 'sort_by' => 'popularity.desc'];
    if ($duracion_max) $params['with_runtime.lte'] = $duracion_max;
    $resultado = tmdb_get('/discover/movie', $params);
    if ($resultado) return $resultado;
    return fallback_por_genero($genero_id, $duracion_max);
}

// ── FUNCIONES FALLBACK ──────────────────────────────────────────

function fallback_peliculas_populares($pagina = 1) {
    global $pdo;
    $por_pagina = 20;
    $offset = (int)(($pagina - 1) * $por_pagina);
    $limit = (int)$por_pagina;

    $stmt = $pdo->query('SELECT COUNT(*) as total FROM peliculas');
    $total = $stmt->fetch()['total'];
    $total_paginas = max(1, ceil($total / $por_pagina));

    $stmt = $pdo->query("SELECT * FROM peliculas ORDER BY vote_average DESC LIMIT $limit OFFSET $offset");

    return formatear_fallback($stmt->fetchAll(), $total_paginas);
}

function fallback_mejor_valoradas() {
    global $pdo;
    $stmt = $pdo->query('SELECT * FROM peliculas ORDER BY vote_average DESC LIMIT 20');
    return formatear_fallback($stmt->fetchAll());
}

function fallback_estrenos() {
    global $pdo;
    $stmt = $pdo->query('SELECT * FROM peliculas ORDER BY fecha_estreno DESC LIMIT 20');
    return formatear_fallback($stmt->fetchAll());
}

function fallback_buscar($query) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM peliculas WHERE titulo LIKE ? LIMIT 20');
    $stmt->execute(['%' . $query . '%']);
    return formatear_fallback($stmt->fetchAll());
}

function fallback_pelicula($tmdb_id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM peliculas WHERE tmdb_id = ?');
    $stmt->execute([$tmdb_id]);
    $p = $stmt->fetch();
    if (!$p) return null;

    return [
        'id'           => $p['tmdb_id'],
        'title'        => $p['titulo'],
        'overview'     => $p['sinopsis'],
        'poster_path'  => $p['poster_url'],
        'release_date' => $p['fecha_estreno'],
        'runtime'      => $p['duracion'],
        'vote_average' => $p['vote_average'] ?? 0,
        'vote_count'   => $p['vote_count'] ?? 0,
        'tagline'      => '',
        'backdrop_path'=> $p['poster_url'],
        'genres'       => array_map(fn($g) => ['name' => trim($g)], explode(',', $p['genero'] ?? '')),
    ];
}

function fallback_por_genero($genero_id, $duracion_max = null) {
    global $pdo;
    $sql = 'SELECT * FROM peliculas WHERE 1=1';
    $params = [];
    if ($duracion_max) {
        $sql .= ' AND duracion <= ?';
        $params[] = $duracion_max;
    }
    $sql .= ' ORDER BY RAND() LIMIT 20';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return formatear_fallback($stmt->fetchAll());
}

function formatear_fallback($peliculas, $total_pages = 1) {
    $results = array_map(fn($p) => [
        'id'           => $p['tmdb_id'],
        'title'        => $p['titulo'],
        'overview'     => $p['sinopsis'],
        'poster_path'  => $p['poster_url'],
        'release_date' => $p['fecha_estreno'],
        'runtime'      => $p['duracion'],
        'vote_average' => $p['vote_average'] ?? 0,
        'vote_count'   => $p['vote_count'] ?? 0,
        'backdrop_path'=> $p['poster_url'],
        'genres'       => [],
    ], $peliculas);

    return ['results' => $results, 'total_pages' => $total_pages];
}