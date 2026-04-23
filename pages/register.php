<?php
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
 
$error = '';
$exito = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $repetir  = $_POST['repetir'];
 
    if (empty($nombre) || empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif ($password !== $repetir) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'La contraseña debe contener al menos una mayúscula.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'La contraseña debe contener al menos un número.';
    } elseif (!preg_match('/[\W_]/', $password)) {
        $error = 'La contraseña debe contener al menos un carácter especial (!@#$%...).';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Ese email ya está registrado.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$nombre, $email, $hash]);
            $exito = '¡Cuenta creada correctamente! Ya puedes iniciar sesión.';
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
 
<main class="auth-page">
    <div class="auth-container">
        <h1>Crear cuenta</h1>
 
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($exito): ?>
            <p class="exito"><?= htmlspecialchars($exito) ?></p>
        <?php endif; ?>
 
        <form method="POST">
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Min. 8 car., mayúscula, número y especial" required>
            <input type="password" name="repetir" placeholder="Repetir contraseña" required>
            <button type="submit">Registrarse</button>
        </form>
        <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
    </div>
</main>
 
<?php include '../includes/footer.php'; ?>