<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=cinequest;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
} catch (PDOException $e) {
    // Redirigir a página de error personalizada
    if (!headers_sent()) {
        header('Location: /cinequest/error.php');
        exit;
    }
    // Si los headers ya se enviaron, mostrar página de error inline
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><meta http-equiv="refresh" content="0;url=/cinequest/error.php"></head><body></body></html>');
}

define('TMDB_API_KEY', '314f4000c01c4294a668076359930300');
define('TMDB_BASE_URL', 'https://api.themoviedb.org/3');
define('TMDB_IMG_URL', 'https://image.tmdb.org/t/p/w500');