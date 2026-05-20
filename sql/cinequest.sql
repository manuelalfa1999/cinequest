-- ============================================================
-- CineQuest — Script de instalación de base de datos
-- Versión: 1.0 | DAW 2025/2026
-- IES Matemático Puig Adam
-- ============================================================
-- Ejecutar en phpMyAdmin o MySQL CLI:
-- mysql -u root -p < setup.sql
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS `cinequest`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `cinequest`;

CREATE TABLE `historial_visto` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pelicula_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `estado_animo` varchar(50) DEFAULT NULL,
  `compania` varchar(50) DEFAULT NULL,
  `genero_principal` int(11) DEFAULT NULL,
  `duracion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lista_pendientes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pelicula_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `logros` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `icono` varchar(255) DEFAULT NULL,
  `condicion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `peliculas` (
  `id` int(11) NOT NULL,
  `tmdb_id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `genero` varchar(100) DEFAULT NULL,
  `duracion` int(11) DEFAULT NULL,
  `sinopsis` text DEFAULT NULL,
  `poster_url` varchar(255) DEFAULT NULL,
  `fecha_estreno` date DEFAULT NULL,
  `vote_average` float DEFAULT 0,
  `vote_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `retos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `objetivo` int(11) DEFAULT 1,
  `puntos_xp` int(11) DEFAULT 100,
  `categoria` enum('bronce','plata','oro','legendario','platino') DEFAULT 'bronce',
  `nivel_requerido` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT 'default.png',
  `puntos_xp` int(11) DEFAULT 0,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `rol` enum('usuario','admin') NOT NULL DEFAULT 'usuario',
  `nivel` int(11) DEFAULT 1,
  `cinefilo_semana` int(11) DEFAULT 0,
  `cinefilo_semana_fecha` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuario_logros` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `logro_id` int(11) NOT NULL,
  `fecha_obtenido` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuario_retos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `reto_id` int(11) NOT NULL,
  `progreso` int(11) DEFAULT 0,
  `completado` tinyint(1) DEFAULT 0,
  `fecha_completado` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `valoraciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pelicula_id` int(11) NOT NULL,
  `puntuacion` tinyint(4) DEFAULT NULL CHECK (`puntuacion` between 1 and 5),
  `comentario` text DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `historial_visto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `pelicula_id` (`pelicula_id`);

ALTER TABLE `lista_pendientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unico` (`usuario_id`,`pelicula_id`);

ALTER TABLE `logros`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `peliculas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tmdb_id` (`tmdb_id`);

ALTER TABLE `retos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `usuario_logros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `logro_id` (`logro_id`);

ALTER TABLE `usuario_retos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `reto_id` (`reto_id`);

ALTER TABLE `valoraciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `pelicula_id` (`pelicula_id`);

ALTER TABLE `historial_visto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

ALTER TABLE `lista_pendientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `logros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `peliculas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=730;

ALTER TABLE `retos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `usuario_logros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `usuario_retos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

ALTER TABLE `valoraciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `historial_visto`
  ADD CONSTRAINT `historial_visto_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `lista_pendientes`
  ADD CONSTRAINT `lista_pendientes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `usuario_logros`
  ADD CONSTRAINT `usuario_logros_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuario_logros_ibfk_2` FOREIGN KEY (`logro_id`) REFERENCES `logros` (`id`) ON DELETE CASCADE;

ALTER TABLE `usuario_retos`
  ADD CONSTRAINT `usuario_retos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuario_retos_ibfk_2` FOREIGN KEY (`reto_id`) REFERENCES `retos` (`id`) ON DELETE CASCADE;

ALTER TABLE `valoraciones`
  ADD CONSTRAINT `valoraciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

-- Datos base: Retos del sistema
INSERT INTO `retos` (`id`, `titulo`, `descripcion`, `tipo`, `objetivo`, `puntos_xp`, `categoria`, `nivel_requerido`) VALUES
(6, 'Primer vistazo', 'Ve tu primera película en CineQuest', 'peliculas_vistas', 1, 50, 'bronce', 1),
(7, 'Cinéfilo novato', 'Ve 5 películas en CineQuest', 'peliculas_vistas', 5, 100, 'bronce', 1),
(8, 'Crítico en ciernes', 'Valora 3 películas', 'valoraciones', 3, 75, 'bronce', 1),
(9, 'Maratoniano', 'Ve una película de más de 2 horas', 'duracion', 120, 75, 'bronce', 1),
(10, 'Noche de cine', 'Ve una película en compañía', 'compania', 1, 50, 'bronce', 1),
(11, 'Aficionado', 'Ve 20 películas en CineQuest', 'peliculas_vistas', 20, 200, 'plata', 2),
(12, 'Explorador de géneros', 'Ve películas de 3 géneros distintos', 'generos_distintos', 3, 150, 'plata', 2),
(13, 'Gran crítico', 'Valora 10 películas', 'valoraciones', 10, 200, 'plata', 2),
(14, 'Seriamente maratoniano', 'Ve 5 películas de más de 2 horas', 'duracion_multiple', 5, 150, 'plata', 2),
(15, 'Cinéfilo dedicado', 'Ve 50 películas en CineQuest', 'peliculas_vistas', 50, 400, 'oro', 3),
(16, 'Maestro de géneros', 'Ve películas de 5 géneros distintos', 'generos_distintos', 5, 300, 'oro', 3),
(17, 'Crítico profesional', 'Valora 25 películas', 'valoraciones', 25, 350, 'oro', 3),
(18, 'Leyenda del cine', 'Ve 100 películas en CineQuest', 'peliculas_vistas', 100, 800, 'legendario', 4),
(19, 'Omnívoro cinematográfico', 'Ve películas de 8 géneros distintos', 'generos_distintos', 8, 600, 'legendario', 4),
(20, 'El gran crítico', 'Valora 50 películas', 'valoraciones', 50, 700, 'legendario', 4),
(21, 'Maestro Cinéfilo 🏆', 'Has alcanzado el nivel máximo. Eres una leyenda del cine.', 'nivel_maximo', 5, 1000, 'platino', 5);

-- Usuario administrador por defecto
-- Email: admin@cinequest.com | Contraseña: password
INSERT INTO `usuarios` (`nombre`, `email`, `password_hash`, `rol`, `nivel`, `puntos_xp`) VALUES
('Admin', 'admin@cinequest.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 0);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================================
-- Instalación completada
-- Accede a: http://localhost/cinequest
-- Admin: admin@cinequest.com / password
-- ============================================================