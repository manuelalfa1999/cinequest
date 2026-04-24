<?php
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre']     = $usuario['nombre'];
        $_SESSION['rol']        = $usuario['rol'];
        header('Location: home.php');
        exit;
    } else {
        $error = 'Email o contraseña incorrectos.';
    }
}
?>
<?php include '../includes/header.php'; ?>

<main class="auth-page">
    <div class="auth-wrapper">
        <div class="auth-bienvenida">
            <h2>🍿 Bienvenido a <span style="color:#4fc3f7">CineQuest</span> 🍿</h2>
            <p>Descubre películas, completa retos y sube de nivel</p>
        </div>
        <div class="auth-container">
            <h1>Iniciar sesión</h1>

            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="email" name="email" placeholder="Email" required autocomplete="off">
                <input type="password" name="password" placeholder="Contraseña" required autocomplete="new-password">
                <button type="submit">Entrar</button>
            </form>
            <p>¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>