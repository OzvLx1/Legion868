-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:8889
-- Tiempo de generación: 27-02-2026 a las 23:45:42
-- Versión del servidor: 8.0.44
-- Versión de PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `legion868_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

CREATE TABLE `asistencias` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `fecha` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja_diaria`
--

CREATE TABLE `caja_diaria` (
  `id` int NOT NULL,
  `fecha` date NOT NULL,
  `fondo_inicial` decimal(10,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `caja_diaria`
--

INSERT INTO `caja_diaria` (`id`, `fecha`, `fondo_inicial`) VALUES
(1, '2026-02-19', 0.00),
(2, '2026-02-18', 0.00),
(3, '2026-02-22', 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `id` int NOT NULL,
  `producto` varchar(50) NOT NULL,
  `stock` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`id`, `producto`, `stock`) VALUES
(1, 'Agua', 4),
(2, 'Electrolífe', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `forma_pago` varchar(50) DEFAULT NULL,
  `concepto` varchar(100) NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `usuario_id`, `monto`, `forma_pago`, `concepto`, `fecha`) VALUES
(34, 6, 500.00, 'Efectivo', 'Abono / Prepago', '2026-02-18 18:53:45'),
(35, 6, 30.00, 'Monedero', 'Agua', '2026-02-18 18:53:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservaciones`
--

CREATE TABLE `reservaciones` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `servicio` enum('Nutrición','Body-Care','Masaje','Fisioterapia') NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `estado` enum('confirmada','cancelada','completada') DEFAULT 'confirmada',
  `notas` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `reservaciones`
--

INSERT INTO `reservaciones` (`id`, `usuario_id`, `servicio`, `fecha`, `hora`, `estado`, `notas`, `created_at`) VALUES
(2, 1, 'Nutrición', '2026-02-16', '16:00:00', 'confirmada', 'aumento de masa muscular', '2026-02-15 18:35:26'),
(3, 3, 'Body-Care', '2026-02-15', '13:00:00', 'confirmada', '', '2026-02-15 18:37:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `lesiones` text,
  `operaciones` text,
  `enfermedad` text,
  `horario` varchar(50) DEFAULT NULL,
  `talla_playera` varchar(10) DEFAULT NULL,
  `tipo_usuario` enum('estandar','teen','admin','staff') DEFAULT 'estandar',
  `estado_pago` enum('pagado','pendiente') DEFAULT 'pendiente',
  `saldo_monedero` decimal(10,2) DEFAULT '0.00',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `fecha_nacimiento`, `email`, `password`, `telefono`, `lesiones`, `operaciones`, `enfermedad`, `horario`, `talla_playera`, `tipo_usuario`, `estado_pago`, `saldo_monedero`, `fecha_registro`) VALUES
(1, 'Oz', 'Admin', NULL, 'admin@legion868.com', '$2y$10$Mjzj01uqRy97dZKQ7a6DoelxxbOzLlMw235ENE1Mj5ULy.v.8/y7S', NULL, NULL, NULL, NULL, NULL, NULL, 'admin', 'pagado', 0.00, '2026-02-12 21:27:16'),
(3, 'osvaldo', 'hernandez', '2012-02-15', 'hola@gmail.com', NULL, '55555555', 'si', 'si', 'si', '6:00 AM', 'M', 'estandar', 'pagado', 0.00, '2026-02-15 18:20:03'),
(4, 'santi', 'lopez', '2010-02-15', 'nose@live.com', NULL, '44444444', 'no', 'no', 'no', '5:00 PM', 'S', 'teen', 'pagado', 0.00, '2026-02-15 19:03:23'),
(5, 'natalia', 'lopez', '2008-02-02', 'nat@gmail.com', NULL, '1111111111', 'no', 'no', 'no', '6:00 PM', 'S', 'teen', 'pagado', 0.00, '2026-02-15 19:12:10'),
(6, 'jared', 'bracamontes ', '1990-11-15', '', NULL, '', '', '', '', '6:00 AM', 'XS', 'estandar', 'pagado', 470.00, '2026-02-18 18:45:20');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_asistencia` (`usuario_id`,`fecha`);

--
-- Indices de la tabla `caja_diaria`
--
ALTER TABLE `caja_diaria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fecha` (`fecha`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `reservaciones`
--
ALTER TABLE `reservaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `caja_diaria`
--
ALTER TABLE `caja_diaria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `reservaciones`
--
ALTER TABLE `reservaciones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reservaciones`
--
ALTER TABLE `reservaciones`
  ADD CONSTRAINT `reservaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
