-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-04-2026 a las 21:56:48
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `universidadb`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignatura`
--

CREATE TABLE `asignatura` (
  `id_asignatura` int(11) NOT NULL,
  `nombre_asignatura` varchar(250) NOT NULL,
  `creditos_asignatura` int(11) NOT NULL,
  `asignatura_PK` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrera`
--

CREATE TABLE `carrera` (
  `id_carrera` int(11) NOT NULL,
  `nombre_carrera` varchar(250) NOT NULL,
  `semestre_carrera` varchar(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrera_asignatura`
--

CREATE TABLE `carrera_asignatura` (
  `carrera_id_carrera` int(11) NOT NULL,
  `Asignatura_id_asignatura` int(11) NOT NULL,
  `carrera_asignatura_PK` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docente`
--

CREATE TABLE `docente` (
  `id_docente` int(50) NOT NULL,
  `nombre_doc` varchar(250) NOT NULL,
  `Fecha_nacimiento_doc` date NOT NULL,
  `Direccion_doc` varchar(100) NOT NULL,
  `Telefono_doc` varchar(50) NOT NULL,
  `Docente_PK` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docenteasignatura`
--

CREATE TABLE `docenteasignatura` (
  `docente_id_docente` int(11) NOT NULL,
  `asignatura_id_asignatura` int(11) NOT NULL,
  `docente_asignatura` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante`
--

CREATE TABLE `estudiante` (
  `id_estudiante` int(50) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `semestre` int(20) NOT NULL,
  `genero` varchar(10) NOT NULL,
  `trabajo` varchar(50) NOT NULL,
  `id_carrera` int(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantedocente`
--

CREATE TABLE `estudiantedocente` (
  `Estudiante_id_estudiante` int(11) NOT NULL,
  `Docente_id_docente` int(11) NOT NULL,
  `Salon` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asignatura`
--
ALTER TABLE `asignatura`
  ADD PRIMARY KEY (`asignatura_PK`);

--
-- Indices de la tabla `carrera`
--
ALTER TABLE `carrera`
  ADD PRIMARY KEY (`id_carrera`);

--
-- Indices de la tabla `carrera_asignatura`
--
ALTER TABLE `carrera_asignatura`
  ADD PRIMARY KEY (`carrera_asignatura_PK`);

--
-- Indices de la tabla `docente`
--
ALTER TABLE `docente`
  ADD PRIMARY KEY (`Docente_PK`);

--
-- Indices de la tabla `docenteasignatura`
--
ALTER TABLE `docenteasignatura`
  ADD PRIMARY KEY (`docente_asignatura`);

--
-- Indices de la tabla `estudiante`
--
ALTER TABLE `estudiante`
  ADD PRIMARY KEY (`id_estudiante`),
  ADD KEY `id_carrera_fk` (`id_carrera`);

--
-- Indices de la tabla `estudiantedocente`
--
ALTER TABLE `estudiantedocente`
  ADD KEY `fk_estudiante_docente_estudiante` (`Estudiante_id_estudiante`);

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `estudiante`
--
ALTER TABLE `estudiante`
  ADD CONSTRAINT `id_carrera_fk` FOREIGN KEY (`id_carrera`) REFERENCES `carrera` (`id_carrera`);

--
-- Filtros para la tabla `estudiantedocente`
--
ALTER TABLE `estudiantedocente`
  ADD CONSTRAINT `fk_estudiante_docente_estudiante` FOREIGN KEY (`Estudiante_id_estudiante`) REFERENCES `estudiante` (`id_estudiante`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
