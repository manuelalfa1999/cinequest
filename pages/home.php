<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
?>
<?php include '../includes/header.php'; ?>

<main>
    <h1>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?>! 🎬</h1>
    <p>¿Qué película vemos hoy?</p>
</main>

<?php include '../includes/footer.php'; ?>