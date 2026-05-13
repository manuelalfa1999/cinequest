<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['usuario_id'])) {
    header('Location: pages/home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineQuest — Descubre tu próxima película</title>
    <link rel="stylesheet" href="/cinequest/assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: #0f0f0f;
            color: #fff;
            overflow-x: hidden;
        }

        /* NAV */
        .landing-nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 40px;
            background: rgba(10,10,20,0.85);
            backdrop-filter: blur(10px);
            z-index: 100;
            border-bottom: 1px solid #ffffff11;
        }

        .landing-nav img { height: 50px; }

        .landing-nav-btns { display: flex; gap: 15px; }

        .btn-nav-login {
            padding: 9px 22px;
            border: 2px solid #4fc3f7;
            color: #4fc3f7;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.2s;
        }

        .btn-nav-login:hover { background: #4fc3f722; }

        .btn-nav-register {
            padding: 9px 22px;
            background: #4fc3f7;
            color: #0f0f0f;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.2s;
        }

        .btn-nav-register:hover { background: #0295b5; color: #fff; }

        /* HERO */
        .hero {
            min-height: 100vh;
            background: 
                linear-gradient(to bottom, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.4) 50%, rgba(15,15,15,1) 100%),
                url('https://image.tmdb.org/t/p/original/xJHokMbljvjADYdit5fK5VQsXEG.jpg') center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 100px 20px 60px;
        }

        .hero-content { max-width: 750px; }

        .hero-badge {
            display: inline-block;
            background: #4fc3f722;
            border: 1px solid #4fc3f7;
            color: #4fc3f7;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 13px;
            margin-bottom: 25px;
            letter-spacing: 1px;
        }

        .hero h1 {
            font-size: 62px;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 20px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.8);
        }

        .hero h1 span { color: #4fc3f7; }

        .hero p {
            font-size: 20px;
            color: #ccc;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .hero-btns {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-hero-primary {
            padding: 16px 40px;
            background: #4fc3f7;
            color: #0f0f0f;
            border-radius: 10px;
            text-decoration: none;
            font-size: 17px;
            font-weight: bold;
            transition: all 0.2s;
            box-shadow: 0 4px 20px #4fc3f755;
        }

        .btn-hero-primary:hover { background: #0295b5; color: #fff; transform: translateY(-2px); }

        .btn-hero-secondary {
            padding: 16px 40px;
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 10px;
            text-decoration: none;
            font-size: 17px;
            border: 1px solid rgba(255,255,255,0.3);
            transition: all 0.2s;
            backdrop-filter: blur(5px);
        }

        .btn-hero-secondary:hover { background: rgba(255,255,255,0.2); transform: translateY(-2px); }

        /* STATS */
        .stats {
            display: flex;
            justify-content: center;
            gap: 60px;
            padding: 50px 40px;
            background: #1a1a2e;
            flex-wrap: wrap;
        }

        .stat-item { text-align: center; }
        .stat-num { font-size: 42px; font-weight: bold; color: #4fc3f7; }
        .stat-txt { color: #888; font-size: 14px; margin-top: 5px; }

        /* FEATURES */
        .features {
            padding: 80px 40px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .features-title {
            text-align: center;
            font-size: 36px;
            margin-bottom: 10px;
        }

        .features-sub {
            text-align: center;
            color: #888;
            margin-bottom: 60px;
            font-size: 16px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .feature-card {
            background: #1a1a2e;
            border-radius: 15px;
            padding: 35px 30px;
            border: 1px solid #333;
            transition: border-color 0.2s, transform 0.2s;
        }

        .feature-card:hover { border-color: #4fc3f7; transform: translateY(-4px); }

        .feature-icono { font-size: 42px; margin-bottom: 18px; }
        .feature-card h3 { font-size: 20px; margin-bottom: 10px; }
        .feature-card p { color: #888; font-size: 14px; line-height: 1.6; }

        /* NIVELES */
        .niveles-section {
            background: #1a1a2e;
            padding: 80px 40px;
        }

        .niveles-section h2 {
            text-align: center;
            font-size: 36px;
            margin-bottom: 10px;
        }

        .niveles-section .sub {
            text-align: center;
            color: #888;
            margin-bottom: 50px;
            font-size: 16px;
        }

        .niveles-grid {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            max-width: 900px;
            margin: 0 auto;
        }

        .nivel-item {
            text-align: center;
            background: #0f0f0f;
            border-radius: 12px;
            padding: 25px 20px;
            border: 1px solid #333;
            min-width: 140px;
            transition: border-color 0.2s;
        }

        .nivel-item:hover { border-color: #4fc3f7; }
        .nivel-item .icono { font-size: 36px; margin-bottom: 10px; }
        .nivel-item .nombre { font-size: 14px; font-weight: bold; margin-bottom: 5px; }
        .nivel-item .xp { color: #4fc3f7; font-size: 12px; }

        /* CTA FINAL */
        .cta {
            padding: 100px 40px;
            text-align: center;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 100%);
        }

        .cta h2 { font-size: 42px; margin-bottom: 15px; }
        .cta h2 span { color: #4fc3f7; }
        .cta p { color: #888; font-size: 18px; margin-bottom: 40px; }

        /* FOOTER */
        .landing-footer {
            text-align: center;
            padding: 20px;
            background: #1a1a2e;
            color: #666;
            font-size: 13px;
        }
    </style>
</head>
<body>

    <!-- NAV -->
    <nav class="landing-nav">
        <img src="/cinequest/assets/img/logo.svg" alt="CineQuest">
        <div class="landing-nav-btns">
            <a href="/cinequest/pages/login.php" class="btn-nav-login">Iniciar sesión</a>
            <a href="/cinequest/pages/register.php" class="btn-nav-register">Registrarse gratis</a>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-content">
            <span class="hero-badge">🎬 La plataforma de cine con gamificación</span>
            <h1>Tu próxima película<br>favorita te <span>espera</span></h1>
            <p>Descubre películas perfectas para cada momento, completa retos, sube de nivel y compite con otros cinéfilos.</p>
            <div class="hero-btns">
                <a href="/cinequest/pages/register.php" class="btn-hero-primary">🚀 Empieza gratis</a>
                <a href="/cinequest/pages/login.php" class="btn-hero-secondary">Ya tengo cuenta</a>
            </div>
        </div>
    </section>

    <!-- STATS -->
    <div class="stats">
        <div class="stat-item">
            <div class="stat-num">500+</div>
            <div class="stat-txt">Películas en catálogo</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">15</div>
            <div class="stat-txt">Retos para completar</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">5</div>
            <div class="stat-txt">Niveles de progresión</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">∞</div>
            <div class="stat-txt">Recomendaciones personalizadas</div>
        </div>
    </div>

    <!-- FEATURES -->
    <section class="features">
        <h2 class="features-title">¿Qué es CineQuest?</h2>
        <p class="features-sub">Mucho más que un catálogo de películas</p>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icono">🎯</div>
                <h3>Recomendador inteligente</h3>
                <p>Dinos cómo te sientes, con quién estás y cuánto tiempo tienes. Te recomendamos la película perfecta para ese momento.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icono">🏆</div>
                <h3>Sistema de retos</h3>
                <p>Completa retos de Bronce, Plata, Oro y Legendario. Cada reto completado te da XP para subir de nivel.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icono">⭐</div>
                <h3>Valora y comenta</h3>
                <p>Puntúa las películas que has visto de 1 a 5 estrellas y escribe tu crítica para la comunidad.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icono">📌</div>
                <h3>Lista de pendientes</h3>
                <p>Guarda las películas que quieres ver para no olvidarte de ninguna. Tu lista siempre a mano en la pantalla principal.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icono">📊</div>
                <h3>Ranking semanal</h3>
                <p>Compite con otros usuarios por ser el cinéfilo de la semana. Cada domingo se premia al más activo.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icono">🎬</div>
                <h3>Trailers incluidos</h3>
                <p>Mira el trailer oficial de cada película directamente desde su página, sin salir de CineQuest.</p>
            </div>
        </div>
    </section>

    <!-- NIVELES -->
    <section class="niveles-section">
        <h2>Sube de nivel</h2>
        <p class="sub">Cuantas más películas veas y retos completes, más alto llegarás</p>
        <div class="niveles-grid">
            <div class="nivel-item">
                <div class="icono">🎟️</div>
                <div class="nombre">Espectador</div>
                <div class="xp">Nivel 1 · 0 XP</div>
            </div>
            <div class="nivel-item">
                <div class="icono">🎬</div>
                <div class="nombre">Aficionado</div>
                <div class="xp">Nivel 2 · 300 XP</div>
            </div>
            <div class="nivel-item">
                <div class="icono">🎭</div>
                <div class="nombre">Cinéfilo</div>
                <div class="xp">Nivel 3 · 700 XP</div>
            </div>
            <div class="nivel-item">
                <div class="icono">🏅</div>
                <div class="nombre">Crítico</div>
                <div class="xp">Nivel 4 · 1500 XP</div>
            </div>
            <div class="nivel-item" style="border-color: #f5c518;">
                <div class="icono">🏆</div>
                <div class="nombre">Maestro Cinéfilo</div>
                <div class="xp" style="color:#f5c518">Nivel 5 · 3000 XP</div>
            </div>
        </div>
    </section>

    <!-- CTA FINAL -->
    <section class="cta">
        <h2>¿Listo para tu <span>próxima película</span>?</h2>
        <p>Únete a CineQuest y empieza tu aventura cinematográfica hoy mismo.</p>
        <div class="hero-btns">
            <a href="/cinequest/pages/register.php" class="btn-hero-primary">🚀 Crear cuenta gratis</a>
            <a href="/cinequest/pages/login.php" class="btn-hero-secondary">Iniciar sesión</a>
        </div>
    </section>

    <footer class="landing-footer">
        © 2026 CineQuest — Todos los derechos reservados
    </footer>

</body>
</html>