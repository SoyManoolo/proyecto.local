-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 10-03-2025 a las 15:52:05
-- Versión del servidor: 8.0.41-0ubuntu0.24.04.1
-- Versión de PHP: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `BlueLock`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Player`
--

CREATE TABLE `Player` (
  `player_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `birthday` date NOT NULL,
  `height` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Stats`
--

CREATE TABLE `Stats` (
  `stats_id` int NOT NULL,
  `def` int NOT NULL,
  `spd` int NOT NULL,
  `off` int NOT NULL,
  `pass` int NOT NULL,
  `drb` int NOT NULL,
  `shoot` int NOT NULL,
  `player_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Users`
--

CREATE TABLE `Users` (
  `id` int NOT NULL,
  `username` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `Users`
--

INSERT INTO `Users` (`id`, `username`, `email`, `name`, `surname`, `password`, `created_at`, `updated_at`) VALUES
(6, 'soymanolo', 'erik.saldi.diaz@gmail.com', 'Erik Manuel', 'Saldaña Diaz', '$2y$10$gF8sseaAfUBrWDQZlLdaf.7LolTaBjkf5eXJSt69Hx970iLV4sB36', '2025-03-10 15:40:07', '2025-03-10 15:40:07');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `Player`
--
ALTER TABLE `Player`
  ADD PRIMARY KEY (`player_id`);

--
-- Indices de la tabla `Stats`
--
ALTER TABLE `Stats`
  ADD PRIMARY KEY (`stats_id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indices de la tabla `Users`
--
ALTER TABLE `Users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `Player`
--
ALTER TABLE `Player`
  MODIFY `player_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Stats`
--
ALTER TABLE `Stats`
  MODIFY `stats_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `Users`
--
ALTER TABLE `Users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `Stats`
--
ALTER TABLE `Stats`
  ADD CONSTRAINT `Stats_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `Player` (`player_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
