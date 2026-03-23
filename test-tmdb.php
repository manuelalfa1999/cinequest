<?php
require_once 'api/tmdb.php';

$peliculas = obtener_peliculas_populares();

foreach ($peliculas['results'] as $pelicula) {
    echo $pelicula['title'] . '<br>';
}
