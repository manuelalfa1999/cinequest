<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineQuest — Error de conexión</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #0f0f0f;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            padding: 40px 20px;
            max-width: 500px;
        }
        .error-icono { font-size: 80px; margin-bottom: 25px; }
        .error-titulo {
            font-size: 28px;
            color: #4fc3f7;
            margin-bottom: 15px;
        }
        .error-msg {
            color: #888;
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 30px;
        }
         
        .btn-reintentar {
            display: inline-block;
            padding: 12px 30px;
            background: #4fc3f7;
            color: #0f0f0f;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
            transition: background 0.2s;
        }
        .btn-reintentar:hover { background: #0295b5; color: #fff; }
        .logo {
            margin-bottom: 30px;
        }
        .logo img { height: 60px; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="logo">
            <img src="/cinequest/assets/img/logo.svg" alt="CineQuest">
        </div>
        <div class="error-icono">🎬</div>
        <h1 class="error-titulo">¡Vaya! Algo ha fallado</h1>
        <p class="error-msg">
            No podemos conectar con la base de datos en este momento.<br>
            Estamos trabajando para solucionarlo.
        </p>
        
        <a href="/cinequest/index.php" class="btn-reintentar">🔄 Reintentar</a>
    </div>
</body>
</html>