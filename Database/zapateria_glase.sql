-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-11-2026 a las 03:35:57
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `zapateria_glase`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `arreglos`
--

CREATE TABLE `arreglos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `cliente` varchar(250) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `tipo_calzado` varchar(100) NOT NULL,
  `tipo_arreglo` varchar(100) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `valor` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estado` varchar(30) NOT NULL DEFAULT 'Pendiente',
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `arreglos`
--

INSERT INTO `arreglos` (`id`, `cliente_id`, `cliente`, `telefono`, `tipo_calzado`, `tipo_arreglo`, `fecha_ingreso`, `fecha_entrega`, `valor`, `estado`, `observaciones`) VALUES
(1, 1, NULL, NULL, 'Tenis', 'Cambio de Suela', '2026-11-04', '2026-11-04', 200000.00, 'En Proceso', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `arreglo_materiales`
--

CREATE TABLE `arreglo_materiales` (
  `id` int(11) NOT NULL,
  `arreglo_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `arreglo_materiales`
--

INSERT INTO `arreglo_materiales` (`id`, `arreglo_id`, `material_id`, `cantidad`) VALUES
(1, 0, 1, 1),
(2, 0, 4, 1),
(3, 0, 9, 1),
(4, 0, 2, 1),
(5, 0, 12, 1),
(6, 0, 1, 1),
(7, 0, 8, 1),
(8, 0, 2, 1),
(9, 0, 5, 1),
(10, 0, 12, 1),
(11, 0, 5, 2),
(12, 0, 4, 1),
(13, 0, 10, 1),
(14, 0, 1, 1),
(15, 0, 2, 1),
(16, 0, 11, 1),
(17, 0, 2, 1),
(18, 0, 12, 1),
(19, 0, 13, 1),
(20, 0, 4, 1),
(21, 0, 1, 1),
(22, 0, 8, 1),
(23, 0, 2, 1),
(24, 0, 19, 1),
(25, 0, 1, 1),
(26, 0, 4, 1),
(27, 1, 14, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombres` varchar(150) NOT NULL,
  `documento` varchar(50) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombres`, `documento`, `telefono`, `correo`, `direccion`, `ciudad`, `observaciones`, `fecha_registro`) VALUES
(1, 'juan', '1013112832', '3227450359', '', 'cra 73b 7b 03', 'Bogota DC', '', '2026-10-20 18:33:48'),
(2, 'Carlos Pérez', '1012345678', '3101234567', 'carlos.perez@gmail.com', 'Calle 100 # 15-20', 'Bogota DC', 'Cliente frecuente, prefiere zapatos formales.', '2026-10-20 18:44:24'),
(3, 'Ana María Gómez', '1023456789', '3209876543', 'ana.gomez@yahoo.com', 'Carrera 45 # 100-30', 'Medellin', 'Busca calzado ortopédico y cómodo.', '2026-10-20 18:44:24'),
(4, 'Luis Fernando Torres', '1034567890', '3112223344', 'luis.torres@outlook.com', 'Av. 68 # 22-10', 'Bogota DC', 'Compra calzado deportivo.', '2026-10-20 18:44:24'),
(5, 'María Camila Rojas', '1045678901', '3153334455', 'mc.rojas@gmail.com', 'Calle 72 # 11-45', 'Bogota DC', 'Interesada en tacones y sandalias.', '2026-10-20 18:44:24'),
(6, 'Jorge Andrés Silva', '1056789012', '3164445566', 'jorge.silva@hotmail.com', 'Carrera 15 # 85-12', 'Cali', 'Cliente nuevo, prefiere mocasines.', '2026-10-20 18:44:24'),
(7, 'Diana Patricia Morales', '1067890123', '3175556677', 'diana.morales@gmail.com', 'Calle 50 # 80-25', 'Medellin', 'Le gusta el calzado de cuero genuino.', '2026-10-20 18:44:24'),
(8, 'Andrés Felipe Castro', '1078901234', '3186667788', 'andres.castro@gmail.com', 'Transversal 39 # 70-15', 'Barranquilla', 'Busca botas para clima frío.', '2026-10-20 18:44:24'),
(9, 'Claudia Marcela Herrera', '1089012345', '3197778899', 'claudia.herrera@outlook.com', 'Calle 13 # 40-50', 'Cali', 'Prefiere zapatos bajos y cómodos.', '2026-10-20 18:44:24'),
(10, 'Santiago Ramírez', '1090123456', '3208889900', 'santiago.ramirez@gmail.com', 'Carrera 7 # 32-12', 'Bogota DC', 'Compra zapatos escolares para sus hijos.', '2026-10-20 18:44:24'),
(11, 'Valentina Ortiz', '1101234567', '3219990011', 'valentina.ortiz@yahoo.com', 'Calle 80 # 12-30', 'Bogota DC', 'Busca zapatillas urbanas.', '2026-10-20 18:44:24'),
(12, 'Ricardo David Vargas', '1112345678', '3101112233', 'ricardo.vargas@gmail.com', 'Carrera 30 # 45-60', 'Medellin', 'Cliente corporativo, compra zapatos elegantes.', '2026-10-20 18:44:24'),
(13, 'Tatiana Andrea Ruiz', '1123456789', '3112223355', 'tatiana.ruiz@hotmail.com', 'Calle 19 # 5-20', 'Cali', 'Interesada en botas de cuero.', '2026-10-20 18:44:24'),
(14, 'Gabriel Alejandro Mendoza', '1134567890', '3123334466', 'gabriel.mendoza@gmail.com', 'Av. Boyacá # 120-10', 'Bogota DC', 'Pregunta frecuentemente por descuentos.', '2026-10-20 18:44:24'),
(15, 'Natalia Sofia Jimenez', '1145678901', '3134445577', 'natalia.jimenez@outlook.com', 'Carrera 9 # 15-40', 'Bucaramanga', 'Busca calzado casual de lona.', '2026-10-20 18:44:24'),
(16, 'Mateo Esteban Castillo', '1156789012', '3145556688', 'mateo.castillo@gmail.com', 'Calle 33 # 20-11', 'Medellin', 'Prefiere mocasines y zapatos sin cordones.', '2026-10-20 18:44:24'),
(17, 'Juliana Andrea Reyes', '1167890123', '3156667799', 'juliana.reyes@gmail.com', 'Carrera 50 # 70-30', 'Barranquilla', 'Le gustan las sandalias de plataforma.', '2026-10-20 18:44:24'),
(18, 'David Alejandro Parra', '1178901234', '3167778800', 'david.parra@hotmail.com', 'Calle 10 # 3-45', 'Cali', 'Compra calzado de seguridad/industrial.', '2026-10-20 18:44:24'),
(19, 'Camila Andrea Medina', '1189012345', '3178889911', 'camila.medina@gmail.com', 'Carrera 27 # 54-18', 'Bucaramanga', 'Interesada en zapatillas deportivas de marca.', '2026-10-20 18:44:24'),
(20, 'Esteban Jose Gomez', '1190123456', '3189990022', 'esteban.gomez@yahoo.com', 'Calle 65 # 10-20', 'Bogota DC', 'Cliente frecuente del área de arreglos.', '2026-10-20 18:44:24'),
(21, 'Paola Andrea Castro', '1201234567', '3190001133', 'paola.castr@gmail.com', 'Carrera 14 # 80-50', 'Medellin', 'Busca calzado formal para eventos.', '2026-10-20 18:44:24'),
(22, 'jaun', '1013112834', '3122312312', 'jaunpa@gmail.com', 'cra 73b 7b 03', 'Bogota DC', '', '2026-10-21 22:12:25'),
(23, 'Juan Pérez', '1001001001', '3001112233', 'juan.perez@correo.com', 'Calle 10 # 5-20', 'Bogotá', 'Cliente de prueba', '2026-10-21 22:31:10'),
(24, 'Laura Gómez', '1001001002', '3104445566', 'laura.gomez@correo.com', 'Carrera 7 # 45-12', 'Medellín', 'Cliente de prueba', '2026-10-21 22:31:10'),
(25, 'Camilo Díaz', '1001001003', '3207778899', 'camilo.diaz@correo.com', 'Av. Boyacá # 80-15', 'Bogotá', 'Cliente de prueba', '2026-10-21 22:31:10'),
(26, 'Marcela Rojas', '1001001004', '3159990011', 'marcela.rojas@correo.com', 'Calle 50 # 20-30', 'Cali', 'Cliente de prueba', '2026-10-21 22:31:10'),
(27, 'Andrés Torres', '1001001005', '3012223344', 'andres.torres@correo.com', 'Carrera 15 # 100-8', 'Barranquilla', 'Cliente de prueba sin arreglos', '2026-10-21 22:31:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_actividad`
--

CREATE TABLE `historial_actividad` (
  `id` int(11) NOT NULL,
  `modulo` varchar(30) NOT NULL,
  `detalle` varchar(255) NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_actividad`
--

INSERT INTO `historial_actividad` (`id`, `modulo`, `detalle`, `tipo`, `fecha`) VALUES
(1, 'Arreglos', 'Arreglo #30 cambió a En Proceso.', 'editado', '2026-11-04 01:42:39'),
(2, 'Arreglos', 'Arreglo #30 editado (Cambio de Suela, En Proceso).', 'editado', '2026-11-04 01:43:17'),
(3, 'Mercancía', '<strong>Editado:</strong> Modificación en Bota Texana Café.', 'editado', '2026-11-04 01:49:33'),
(4, 'Proveedores', 'Se modificó el proveedor Textiles La Fortuna.', 'editado', '2026-11-04 02:24:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_configuracion`
--

CREATE TABLE `historial_configuracion` (
  `id` int(11) NOT NULL,
  `detalle` varchar(255) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_materiales`
--

CREATE TABLE `historial_materiales` (
  `id` int(11) NOT NULL,
  `detalle` varchar(255) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_materiales`
--

INSERT INTO `historial_materiales` (`id`, `detalle`, `tipo`, `fecha`) VALUES
(1, 'Modificación en Cinta Reforzada.', 'editado', '2026-11-04 01:26:01'),
(2, 'Uso de 4 de Plantilla de Gel en un arreglo.', 'editado', '2026-11-04 01:28:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_mercancia`
--

CREATE TABLE `historial_mercancia` (
  `id` int(11) NOT NULL,
  `detalle` varchar(255) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_mercancia`
--

INSERT INTO `historial_mercancia` (`id`, `detalle`, `tipo`, `fecha`) VALUES
(1, '<strong>Editado:</strong> Modificación en Bota Texana Café.', 'editado', '2026-11-04 01:49:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `iniciodesesion`
--

CREATE TABLE `iniciodesesion` (
  `Id` int(11) NOT NULL,
  `Usuario` varchar(50) NOT NULL,
  `Contraseña` varchar(100) NOT NULL,
  `Rol` enum('Administrador','Empleado') NOT NULL DEFAULT 'Empleado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `iniciodesesion`
--

INSERT INTO `iniciodesesion` (`Id`, `Usuario`, `Contraseña`, `Rol`) VALUES
(1, 'Zambrano', '123456', 'Administrador'),
(2, 'Zambrano', '12345', 'Empleado'),
(3, 'Juan', '12345', 'Administrador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales`
--

CREATE TABLE `materiales` (
  `id` int(11) NOT NULL,
  `codigo` varchar(250) NOT NULL,
  `nombredelmaterial` varchar(250) NOT NULL,
  `categoria` varchar(250) NOT NULL,
  `cantidad` int(50) NOT NULL,
  `unidad` varchar(250) NOT NULL,
  `proveedor` varchar(250) NOT NULL,
  `nit` int(25) NOT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `stock_minimo` int(11) NOT NULL DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materiales`
--

INSERT INTO `materiales` (`id`, `codigo`, `nombredelmaterial`, `categoria`, `cantidad`, `unidad`, `proveedor`, `nit`, `proveedor_id`, `stock_minimo`) VALUES
(1, 'MAT-001', 'Pegante Transparente', 'Pegantes', 40, 'Tarros', 'Pegantes del Norte', 800234567, 2, 5),
(2, 'MAT-002', 'Pegante de Contacto', 'Pegantes', 35, 'Tarros', 'Pegantes del Norte', 800234567, 2, 5),
(3, 'MAT-003', 'Pegante Industrial', 'Pegantes', 3, 'Tarros', 'Químicos Industriales', 860890123, 4, 5),
(4, 'MAT-004', 'Hilo Negro', 'Hilos', 60, 'Rollos', 'Hilos y Cueros Ltda', 900345678, 7, 5),
(5, 'MAT-005', 'Hilo Café', 'Hilos', 45, 'Rollos', 'Hilos y Cueros Ltda', 900345678, 7, 5),
(6, 'MAT-006', 'Hilo Blanco', 'Hilos', 4, 'Rollos', 'Textiles La Fortuna', 901901234, 10, 5),
(7, 'MAT-007', 'Hilo Encerado', 'Hilos', 0, 'Rollos', 'Textiles La Fortuna', 901901234, 10, 5),
(8, 'MAT-008', 'Suela de Caucho Negra', 'Suelas', 50, 'Pares', 'Suelas Colombia', 890456789, 5, 5),
(9, 'MAT-009', 'Suela de Goma Café', 'Suelas', 40, 'Pares', 'Suelas Colombia', 890456789, 5, 5),
(10, 'MAT-010', 'Suela Antideslizante', 'Suelas', 30, 'Pares', 'Distribuidora Calzado Andino', 900123456, 6, 5),
(11, 'MAT-011', 'Media Suela de Cuero', 'Suelas', 25, 'Pares', 'Cueros Premium', 800678901, 3, 5),
(12, 'MAT-012', 'Tapas de Tacón', 'Accesorios', 80, 'Pares', 'Accesorios Bogotá', 900789012, 8, 5),
(13, 'MAT-013', 'Tacón Plástico Mediano', 'Accesorios', 20, 'Pares', 'Accesorios Bogotá', 900789012, 8, 5),
(14, 'MAT-014', 'Plantilla de Gel', 'Plantillas', 56, 'Pares', 'Insumos El Zapatero', 901567890, 9, 5),
(15, 'MAT-015', 'Plantilla de Cuero', 'Plantillas', 30, 'Pares', 'Cueros Premium', 800678901, 3, 5),
(16, 'MAT-016', 'Plantilla Ortopédica', 'Plantillas', 2, 'Pares', 'Insumos El Zapatero', 901567890, 9, 5),
(17, 'MAT-017', 'Cordones Negros', 'Accesorios', 100, 'Pares', 'Calzado Express', 800012345, 1, 5),
(18, 'MAT-018', 'Cordones Blancos', 'Accesorios', 70, 'Pares', 'Calzado Express', 800012345, 1, 5),
(19, 'MAT-019', 'Cuero Sintético Negro', 'Accesorios', 15, 'Rollos', 'Cueros Premium', 800678901, 3, 5),
(20, 'MAT-020', 'Cinta Reforzada', 'Accesorios', 2, 'Rollos', 'Distribuidora Calzado Andino', 900123456, 6, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `talla` varchar(20) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock_minimo` int(11) NOT NULL DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo`, `nombre`, `categoria`, `talla`, `cantidad`, `precio`, `stock_minimo`) VALUES
(1, 'DEP-001', 'Tenis Running Pro', 'Deportivo', '40', 15, 189000.00, 5),
(2, 'DEP-002', 'Tenis Training Max', 'Deportivo', '41', 12, 175000.00, 5),
(3, 'DEP-003', 'Tenis Urbano Blanco', 'Deportivo', '39', 20, 159000.00, 5),
(4, 'DEP-004', 'Tenis Basket Elite', 'Deportivo', '43', 3, 220000.00, 5),
(5, 'DEP-005', 'Tenis Ligero Mujer', 'Deportivo', '37', 0, 145000.00, 5),
(6, 'CAS-001', 'Mocasín Cuero Café', 'Casual', '42', 10, 210000.00, 5),
(7, 'CAS-002', 'Zapato Casual Negro', 'Casual', '41', 14, 185000.00, 5),
(8, 'CAS-003', 'Náutico Azul', 'Casual', '40', 8, 165000.00, 5),
(9, 'CAS-004', 'Oxford Clásico', 'Casual', '43', 6, 240000.00, 5),
(10, 'CAS-005', 'Alpargata Lona', 'Casual', '38', 25, 79000.00, 5),
(11, 'BOT-001', 'Bota Cuero Minera', 'Botas', '42', 9, 320000.00, 5),
(12, 'BOT-002', 'Bota Chelsea Negra', 'Botas', '41', 7, 290000.00, 5),
(13, 'BOT-003', 'Bota Industrial Punta Acero', 'Botas', '43', 4, 260000.00, 5),
(14, 'BOT-004', 'Bota Texana Café', 'Botas', '40', 100, 350000.00, 5),
(15, 'TAC-001', 'Tacón Aguja Negro', 'Tacones', '36', 11, 195000.00, 5),
(16, 'TAC-002', 'Tacón Cuadrado Beige', 'Tacones', '37', 13, 178000.00, 5),
(17, 'TAC-003', 'Plataforma Charol', 'Tacones', '38', 5, 205000.00, 5),
(18, 'SAN-001', 'Sandalia Playera', 'Sandalias', '37.5', 30, 69000.00, 5),
(19, 'SAN-002', 'Sandalia Cuero Mujer', 'Sandalias', '38', 18, 112000.00, 5),
(20, 'SAN-003', 'Sandalia Plana Dorada', 'Sandalias', '36', 16, 89000.00, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nit` varchar(15) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `ciudad` varchar(80) NOT NULL,
  `categoria` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nit`, `nombre`, `telefono`, `correo`, `direccion`, `ciudad`, `categoria`) VALUES
(1, '800012345', 'Calzado Express', NULL, NULL, NULL, 'Por definir', 'Accesorios'),
(2, '800234567', 'Pegantes del Norte', NULL, NULL, NULL, 'Por definir', 'Pegantes'),
(3, '800678901', 'Cueros Premium', NULL, NULL, NULL, 'Por definir', 'Accesorios'),
(4, '860890123', 'Químicos Industriales', NULL, NULL, NULL, 'Por definir', 'Pegantes'),
(5, '890456789', 'Suelas Colombia', NULL, NULL, NULL, 'Por definir', 'Suelas'),
(6, '900123456', 'Distribuidora Calzado Andino', NULL, NULL, NULL, 'Por definir', 'Accesorios'),
(7, '900345678', 'Hilos y Cueros Ltda', NULL, NULL, NULL, 'Por definir', 'Hilos'),
(8, '900789012', 'Accesorios Bogotá', NULL, NULL, NULL, 'Por definir', 'Accesorios'),
(9, '901567890', 'Insumos El Zapatero', NULL, NULL, NULL, 'Por definir', 'Plantillas'),
(10, '901901234', 'Textiles La Fortuna', '3227450359', '313123@gmail.com', '', 'Por definir', 'Hilos');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `arreglos`
--
ALTER TABLE `arreglos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `arreglo_materiales`
--
ALTER TABLE `arreglo_materiales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `arreglo_id` (`arreglo_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historial_actividad`
--
ALTER TABLE `historial_actividad`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historial_configuracion`
--
ALTER TABLE `historial_configuracion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historial_materiales`
--
ALTER TABLE `historial_materiales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historial_mercancia`
--
ALTER TABLE `historial_mercancia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `iniciodesesion`
--
ALTER TABLE `iniciodesesion`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `materiales`
--
ALTER TABLE `materiales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_materiales_proveedor` (`proveedor_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_proveedor_nit` (`nit`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `arreglos`
--
ALTER TABLE `arreglos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `arreglo_materiales`
--
ALTER TABLE `arreglo_materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `historial_actividad`
--
ALTER TABLE `historial_actividad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `historial_configuracion`
--
ALTER TABLE `historial_configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_materiales`
--
ALTER TABLE `historial_materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `historial_mercancia`
--
ALTER TABLE `historial_mercancia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `iniciodesesion`
--
ALTER TABLE `iniciodesesion`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `materiales`
--
ALTER TABLE `materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
