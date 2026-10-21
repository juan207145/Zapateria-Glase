-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-10-2026 a las 07:41:34
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


-- Base de datos: `zapateria_glase`
--

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
(5, 'juan', '1013112832', '3227450359', '', 'cra 73b 7b 03', 'Bogota DC', '', '2026-10-20 18:33:48'),
(6, 'Carlos Pérez', '1012345678', '3101234567', 'carlos.perez@gmail.com', 'Calle 100 # 15-20', 'Bogota DC', 'Cliente frecuente, prefiere zapatos formales.', '2026-10-20 18:44:24'),
(7, 'Ana María Gómez', '1023456789', '3209876543', 'ana.gomez@yahoo.com', 'Carrera 45 # 100-30', 'Medellin', 'Busca calzado ortopédico y cómodo.', '2026-10-20 18:44:24'),
(8, 'Luis Fernando Torres', '1034567890', '3112223344', 'luis.torres@outlook.com', 'Av. 68 # 22-10', 'Bogota DC', 'Compra calzado deportivo.', '2026-10-20 18:44:24'),
(9, 'María Camila Rojas', '1045678901', '3153334455', 'mc.rojas@gmail.com', 'Calle 72 # 11-45', 'Bogota DC', 'Interesada en tacones y sandalias.', '2026-10-20 18:44:24'),
(10, 'Jorge Andrés Silva', '1056789012', '3164445566', 'jorge.silva@hotmail.com', 'Carrera 15 # 85-12', 'Cali', 'Cliente nuevo, prefiere mocasines.', '2026-10-20 18:44:24'),
(11, 'Diana Patricia Morales', '1067890123', '3175556677', 'diana.morales@gmail.com', 'Calle 50 # 80-25', 'Medellin', 'Le gusta el calzado de cuero genuino.', '2026-10-20 18:44:24'),
(12, 'Andrés Felipe Castro', '1078901234', '3186667788', 'andres.castro@gmail.com', 'Transversal 39 # 70-15', 'Barranquilla', 'Busca botas para clima frío.', '2026-10-20 18:44:24'),
(13, 'Claudia Marcela Herrera', '1089012345', '3197778899', 'claudia.herrera@outlook.com', 'Calle 13 # 40-50', 'Cali', 'Prefiere zapatos bajos y cómodos.', '2026-10-20 18:44:24'),
(14, 'Santiago Ramírez', '1090123456', '3208889900', 'santiago.ramirez@gmail.com', 'Carrera 7 # 32-12', 'Bogota DC', 'Compra zapatos escolares para sus hijos.', '2026-10-20 18:44:24'),
(15, 'Valentina Ortiz', '1101234567', '3219990011', 'valentina.ortiz@yahoo.com', 'Calle 80 # 12-30', 'Bogota DC', 'Busca zapatillas urbanas.', '2026-10-20 18:44:24'),
(16, 'Ricardo David Vargas', '1112345678', '3101112233', 'ricardo.vargas@gmail.com', 'Carrera 30 # 45-60', 'Medellin', 'Cliente corporativo, compra zapatos elegantes.', '2026-10-20 18:44:24'),
(17, 'Tatiana Andrea Ruiz', '1123456789', '3112223355', 'tatiana.ruiz@hotmail.com', 'Calle 19 # 5-20', 'Cali', 'Interesada en botas de cuero.', '2026-10-20 18:44:24'),
(18, 'Gabriel Alejandro Mendoza', '1134567890', '3123334466', 'gabriel.mendoza@gmail.com', 'Av. Boyacá # 120-10', 'Bogota DC', 'Pregunta frecuentemente por descuentos.', '2026-10-20 18:44:24'),
(19, 'Natalia Sofia Jimenez', '1145678901', '3134445577', 'natalia.jimenez@outlook.com', 'Carrera 9 # 15-40', 'Bucaramanga', 'Busca calzado casual de lona.', '2026-10-20 18:44:24'),
(20, 'Mateo Esteban Castillo', '1156789012', '3145556688', 'mateo.castillo@gmail.com', 'Calle 33 # 20-11', 'Medellin', 'Prefiere mocasines y zapatos sin cordones.', '2026-10-20 18:44:24'),
(21, 'Juliana Andrea Reyes', '1167890123', '3156667799', 'juliana.reyes@gmail.com', 'Carrera 50 # 70-30', 'Barranquilla', 'Le gustan las sandalias de plataforma.', '2026-10-20 18:44:24'),
(22, 'David Alejandro Parra', '1178901234', '3167778800', 'david.parra@hotmail.com', 'Calle 10 # 3-45', 'Cali', 'Compra calzado de seguridad/industrial.', '2026-10-20 18:44:24'),
(23, 'Camila Andrea Medina', '1189012345', '3178889911', 'camila.medina@gmail.com', 'Carrera 27 # 54-18', 'Bucaramanga', 'Interesada en zapatillas deportivas de marca.', '2026-10-20 18:44:24'),
(24, 'Esteban Jose Gomez', '1190123456', '3189990022', 'esteban.gomez@yahoo.com', 'Calle 65 # 10-20', 'Bogota DC', 'Cliente frecuente del área de arreglos.', '2026-10-20 18:44:24'),
(25, 'Paola Andrea Castro', '1201234567', '3190001133', 'paola.castr@gmail.com', 'Carrera 14 # 80-50', 'Medellin', 'Busca calzado formal para eventos.', '2026-10-20 18:44:24');

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
(1, '<strong>Agregado:</strong> Ingreso de nuevo material pegante.', 'agregado', '2026-10-21 05:10:01');

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
(1, '<strong>Agregado:</strong> Ingreso de nuevo modelo airforce.', 'agregado', '2026-10-21 01:12:42'),
(2, '<strong>Editado/Actualizado:</strong> Modificación en airforce.', 'editado', '2026-10-21 01:14:19'),
(3, '<strong>Editado/Actualizado:</strong> Modificación en Sandalia de Tacón Fino.', 'editado', '2026-10-21 01:21:07'),
(4, '<strong>Eliminado:</strong> Se retiró del inventario airforce.', 'eliminado', '2026-10-21 01:22:39'),
(5, '<strong>Eliminado:</strong> Se retiró del inventario Tacón Bajo Oficina.', 'eliminado', '2026-10-21 01:26:04'),
(6, '<strong>Eliminado:</strong> Se retiró del inventario Producto ID 15.', 'eliminado', '2026-10-21 01:27:39'),
(7, '<strong>Eliminado:</strong> Se retiró del inventario Producto ID 2.', 'eliminado', '2026-10-21 01:27:43'),
(8, '<strong>Editado:</strong> Modificación en Botín Casual gamuza.', 'editado', '2026-10-21 01:41:35'),
(9, '<strong>Editado:</strong> Modificación en Tacón Plataforma Fiesta.', 'editado', '2026-10-21 02:00:51');

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
(3, 'Zambrano', '123456', 'Administrador'),
(4, 'Zambrano', '12345', 'Empleado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales`
--

CREATE TABLE `materiales` (
  `Id` int(11) NOT NULL,
  `codigo` varchar(250) NOT NULL,
  `nombredelmaterial` varchar(250) NOT NULL,
  `categoria` varchar(250) NOT NULL,
  `cantidad` int(50) NOT NULL,
  `unidad` varchar(250) NOT NULL,
  `proveedor` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materiales`
--

INSERT INTO `materiales` (`Id`, `codigo`, `nombredelmaterial`, `categoria`, `cantidad`, `unidad`, `proveedor`) VALUES
(21, 'MAT001', 'Pegante Amarillo', 'Pegantes', 25, 'Tarros', 'Distribuidora Quimica S.A.'),
(22, 'MAT002', 'Suela Deportiva', 'Suelas', 14, 'Pares', 'Calzado y Suelas del Centro'),
(23, 'MAT003', 'Hilo Negro', 'Hilos', 6, 'Rollos', 'Hilos y Costuras Ltda.'),
(24, 'MAT004', 'Plantilla de Cuero', 'Plantillas', 32, 'Unidades', 'Curtiembres Nacionales'),
(25, 'MAT005', 'Cremallera Café', 'Accesorios', 9, 'Unidades', 'Cremalleras y Cierres S.A.'),
(26, 'MAT006', 'Hebillas Metálicas', 'Accesorios', 4, 'Unidades', 'Metalistería Industrial'),
(27, 'MAT007', 'Pegante Transparente', 'Pegantes', 5, 'Tarros', 'Distribuidora Quimica S.A.'),
(28, 'MAT008', 'Cordones Blancos', 'Accesorios', 3, 'Pares', 'Textiles y Cordones El Tigre'),
(29, 'MAT009', 'Suela Casual de Goma', 'Suelas', 20, 'Pares', 'Calzado y Suelas del Centro'),
(30, 'MAT010', 'Hilo Café', 'Hilos', 15, 'Rollos', 'Hilos y Costuras Ltda.'),
(31, 'MAT011', 'Cuero Sintético Negro', 'Materiales', 10, 'Metros', 'Curtiembres Nacionales'),
(32, 'MAT012', 'Lona Impermeable', 'Materiales', 8, 'Metros', 'Textiles y Cordones El Tigre'),
(33, 'MAT013', 'Tacón de Madera', 'Accesorios', 12, 'Pares', 'Madera y Tacones Exclusivos'),
(34, 'MAT014', 'Ojetes Metálicos', 'Accesorios', 50, 'Unidades', 'Metalistería Industrial'),
(35, 'MAT015', 'Plantilla Anatómica', 'Plantillas', 18, 'Unidades', 'Curtiembres Nacionales'),
(36, 'MAT016', 'Cera para Cuero', 'Accesorios', 7, 'Unidades', 'Distribuidora Quimica S.A.'),
(37, 'MAT017', 'Suela de Bota Trekking', 'Suelas', 11, 'Pares', 'Calzado y Suelas del Centro'),
(38, 'MAT018', 'Hilo Transparente', 'Hilos', 9, 'Rollos', 'Hilos y Costuras Ltda.'),
(39, 'MAT019', 'Pegante de Contacto Fuerte', 'Pegantes', 14, 'Tarros', 'Distribuidora Quimica S.A.'),
(40, 'MAT020', 'Velcro Negro', 'Accesorios', 25, 'Metros', 'Textiles y Cordones El Tigre'),
(41, 'MAt000324', 'pegante', 'Pegantes', 2321, 'Tarros', 'Sa');

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
  `precio` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo`, `nombre`, `categoria`, `talla`, `cantidad`, `precio`) VALUES
(3, 'PR001', 'Tenis Deportivos Urbanos', 'Deportivo', '38', 15, 180000.00),
(4, 'PR002', 'Bota de Cuero Elegante', 'Botas', '40', 3, 250000.00),
(5, 'PR003', 'Tacón Stiletto Negro', 'Tacones', '37', 9, 220000.00),
(6, 'PR004', 'Sandalia Verano Dama', 'Sandalias', '36', 18, 120000.00),
(7, 'PR005', 'Zapato Casual Confort', 'Casual', '39', 4, 195000.00),
(8, 'PR006', 'Tenis Running Profesional', 'Deportivo', '41', 21, 280000.00),
(9, 'PR007', 'Botín Casual gamuza', 'Botas', '42', 9, 230000.00),
(10, 'PR008', 'Tacón Plataforma Fiesta', 'Tacones', '38', 7, 210000.00),
(11, 'PR009', 'Sandalia Casual Cuero', 'Sandalias', '37', 14, 140000.00),
(12, 'PR010', 'Mocasín Ejecutivo HOMBRE', 'Casual', '40', 10, 240000.00),
(13, 'PR011', 'Tenis Training Gym', 'Deportivo', '39', 25, 175000.00),
(14, 'PR012', 'Bota Trekking Montaña', 'Botas', '43', 6, 310000.00),
(16, 'PR014', 'Sandalia Planaplaya', 'Sandalias', '39', 30, 95000.00),
(17, 'PR015', 'Zapato Derby Clásico', 'Casual', '41', 8, 260000.00),
(18, 'PR016', 'Tenis Basket Retro', 'Deportivo', '40', 11, 290000.00),
(19, 'PR017', 'Bota Alta de Dama', 'Botas', '38', 4, 340000.00),
(20, 'PR018', 'Tacón Aguja Dorado', 'Tacones', '37', 1, 235000.00),
(21, 'PR019', 'Sandalia de Tacón Fino', 'Sandalias', '38', 10, 165000.00);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
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
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `historial_materiales`
--
ALTER TABLE `historial_materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `historial_mercancia`
--
ALTER TABLE `historial_mercancia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `iniciodesesion`
--
ALTER TABLE `iniciodesesion`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `materiales`
--
ALTER TABLE `materiales`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
