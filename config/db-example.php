<?php
// ⚠️ INSTRUCCIONES PARA COMPAÑEROS:
// 1. Copia este archivo y renómbralo como db.php
// 2. Rellena tus datos de conexión de XAMPP
// 3. Nunca subas db.php a GitHub

$host     = 'localhost';
$dbname   = 'cinequest';
$username = 'root';
$password = ''; // En XAMPP suele estar vacío

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]));
}