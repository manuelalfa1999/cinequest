<?php
require_once __DIR__ . '/../config/db.php';

function tmdb_get($endpoint, $params = []) {
    $params['api_key'] = TMDB_API_KEY;
    $params['language'] = 'es-ES';
    $url = TMDB_BASE_URL . $endpoint . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function obtener_peliculas_populares($pagina = 1) {
    return tmdb_get('/movie/popular', ['page' => $pagina]);
}

function buscar_pelicula($query) {
    return tmdb_get('/search/movie', ['query' => $query]);
}

function obtener_pelicula($tmdb_id) {
    return tmdb_get('/movie/' . $tmdb_id);
}

function obtener_peliculas_por_genero($genero_id, $duracion_max = null) {
    $params = ['with_genres' => $genero_id, 'sort_by' => 'popularity.desc'];
    if ($duracion_max) {
        $params['with_runtime.lte'] = $duracion_max;
    }
    return tmdb_get('/discover/movie', $params);
}