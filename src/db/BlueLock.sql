-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 27-01-2025 a las 15:23:58
-- Versión del servidor: 8.0.40-0ubuntu0.24.04.1
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
  `height` decimal(5,2) NOT NULL,
  `team_id` int DEFAULT NULL
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
-- Estructura de tabla para la tabla `Team`
--

CREATE TABLE `Team` (
  `team_id` int NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `Player`
--
ALTER TABLE `Player`
  ADD PRIMARY KEY (`player_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indices de la tabla `Stats`
--
ALTER TABLE `Stats`
  ADD PRIMARY KEY (`stats_id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indices de la tabla `Team`
--
ALTER TABLE `Team`
  ADD PRIMARY KEY (`team_id`);

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
  MODIFY `stats_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Team`
--
ALTER TABLE `Team`
  MODIFY `team_id` int NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `Player`
--
ALTER TABLE `Player`
  ADD CONSTRAINT `Player_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `Team` (`team_id`);

--
-- Filtros para la tabla `Stats`
--
ALTER TABLE `Stats`
  ADD CONSTRAINT `Stats_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `Player` (`player_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
