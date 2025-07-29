-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-07-2025 a las 22:22:19
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
-- Base de datos: `virtual_ipv`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `app_settings`
--

CREATE TABLE `app_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `app_settings`
--

INSERT INTO `app_settings` (`id`, `setting_key`, `setting_value`, `description`) VALUES
(1, 'date_restriction_enabled', '0', 'Habilita o deshabilita la restricción de subir fotos de galería tomadas en días anteriores. 1 para habilitado, 0 para deshabilitado.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `razon_social` varchar(150) DEFAULT NULL,
  `nit` varchar(50) DEFAULT NULL,
  `categoria_cliente` varchar(100) DEFAULT NULL,
  `ciudad_base` varchar(100) DEFAULT NULL,
  `marca_participacion` varchar(50) DEFAULT NULL,
  `caras_unidades` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `usuario_id`, `razon_social`, `nit`, `categoria_cliente`, `ciudad_base`, `marca_participacion`, `caras_unidades`) VALUES
(5, 4, 'POSTOBON', '900254785', 'LIQUIDO', 'barranquilla', 'Marca A', 'Cara A'),
(10, 10, 'COCA COLA', '906578', 'BEBIDAS', 'CARTAGENA', 'Marca A', 'Cara A');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes_modulos_estado`
--

CREATE TABLE `clientes_modulos_estado` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  `estado` varchar(50) DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_agotados`
--

CREATE TABLE `detalle_agotados` (
  `id` int(11) NOT NULL,
  `reporte_id` int(11) NOT NULL,
  `codigo_barras` varchar(100) NOT NULL,
  `nombre_producto` varchar(255) DEFAULT NULL,
  `marca_producto` varchar(100) DEFAULT NULL,
  `agotados` tinyint(1) DEFAULT 1,
  `causal_agotado` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `detalle_agotados`
--

INSERT INTO `detalle_agotados` (`id`, `reporte_id`, `codigo_barras`, `nombre_producto`, `marca_producto`, `agotados`, `causal_agotado`) VALUES
(100, 117, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', 0, NULL),
(101, 117, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', 1, 'Producto descontinuado'),
(102, 117, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', 1, 'No hay espacio en góndola'),
(103, 121, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', 0, NULL),
(104, 121, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', 0, NULL),
(105, 121, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', 0, NULL),
(106, 125, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', 1, 'No hay espacio en góndola'),
(107, 125, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', 0, NULL),
(108, 125, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', 0, NULL),
(109, 129, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', 0, NULL),
(110, 129, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', 0, NULL),
(111, 129, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_devoluciones`
--

CREATE TABLE `detalle_devoluciones` (
  `id` int(11) NOT NULL,
  `reporte_id` int(11) NOT NULL,
  `codigo_barras` varchar(100) NOT NULL,
  `nombre_producto` varchar(255) DEFAULT NULL,
  `marca_producto` varchar(100) DEFAULT NULL,
  `causal_devolucion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `detalle_devoluciones`
--

INSERT INTO `detalle_devoluciones` (`id`, `reporte_id`, `codigo_barras`, `nombre_producto`, `marca_producto`, `causal_devolucion`) VALUES
(95, 118, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', NULL),
(96, 118, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', NULL),
(97, 118, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', NULL),
(98, 122, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', NULL),
(99, 122, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', NULL),
(100, 122, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', NULL),
(101, 126, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', NULL),
(102, 126, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', NULL),
(103, 126, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', NULL),
(104, 130, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', NULL),
(105, 130, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', NULL),
(106, 130, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_inventarios`
--

CREATE TABLE `detalle_inventarios` (
  `id` int(11) NOT NULL,
  `reporte_id` int(11) NOT NULL,
  `codigo_barras` varchar(100) NOT NULL,
  `nombre_producto` varchar(255) DEFAULT NULL,
  `marca_producto` varchar(100) DEFAULT NULL,
  `inventarios` int(11) DEFAULT 0,
  `sugeridos` int(11) DEFAULT 0,
  `unidades_surtidas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `detalle_inventarios`
--

INSERT INTO `detalle_inventarios` (`id`, `reporte_id`, `codigo_barras`, `nombre_producto`, `marca_producto`, `inventarios`, `sugeridos`, `unidades_surtidas`) VALUES
(35, 119, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', 100, 0, 0),
(36, 119, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', 200, 0, 0),
(37, 119, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', 300, 0, 0),
(38, 123, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', 7, 0, 0),
(39, 123, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', 9, 0, 0),
(40, 123, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', 70, 0, 0),
(41, 127, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', 20, 0, 0),
(42, 127, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', 30, 0, 0),
(43, 127, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', 40, 0, 0),
(44, 131, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', 200, 0, 0),
(45, 131, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', 7, 0, 0),
(46, 131, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', 8, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_precios`
--

CREATE TABLE `detalle_precios` (
  `id` int(11) NOT NULL,
  `reporte_id` int(11) NOT NULL,
  `codigo_barras` varchar(100) NOT NULL,
  `nombre_producto` varchar(255) DEFAULT NULL,
  `marca_producto` varchar(100) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `detalle_precios`
--

INSERT INTO `detalle_precios` (`id`, `reporte_id`, `codigo_barras`, `nombre_producto`, `marca_producto`, `precio`) VALUES
(115, 120, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', 10000.00),
(116, 120, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', 20000.00),
(117, 120, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', 3000.00),
(118, 124, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', 70000.00),
(119, 124, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', 80000.00),
(120, 124, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', 9000.00),
(121, 128, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', 2000.00),
(122, 128, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', 3000.00),
(123, 128, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', 4000.00),
(124, 132, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', 3000.00),
(125, 132, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', 1000.00),
(126, 132, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', 7000.00),
(127, 133, '7701234567890', 'Pepsi 400ml sabor original', 'Pepsi', 5000.00),
(128, 133, '7701234567891', 'Manzana Postobón 400ml', 'Manzana', 6000.00),
(129, 133, '7701234567892', 'Colombiana 400ml tradicional', 'Colombiana', 9000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devoluciones_entradas`
--

CREATE TABLE `devoluciones_entradas` (
  `id` int(11) NOT NULL,
  `detalle_devolucion_id` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT 0,
  `fecha` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `devoluciones_entradas`
--

INSERT INTO `devoluciones_entradas` (`id`, `detalle_devolucion_id`, `cantidad`, `fecha`) VALUES
(43, 95, 3, '2025-08-06'),
(44, 95, 60, '2025-08-21'),
(45, 96, 60, '2025-07-31'),
(46, 97, 20, '2025-07-31'),
(47, 98, 50, '2025-07-26'),
(48, 99, 10, '2025-07-27'),
(49, 100, 50, '2025-07-31'),
(50, 101, 1, '2025-07-22'),
(51, 102, 1, '2025-07-22'),
(52, 103, 1, '2025-07-22'),
(53, 104, 1, '2025-07-31'),
(54, 104, 20, '2025-07-23'),
(55, 105, 2, '2025-07-23'),
(56, 105, 60, '2025-07-23'),
(57, 106, 3, '2025-07-23'),
(58, 106, 80, '2025-07-23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos`
--

CREATE TABLE `modulos` (
  `id` int(11) NOT NULL,
  `nombre_modulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos_cliente`
--

CREATE TABLE `productos_cliente` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `nombre_cliente` varchar(150) DEFAULT NULL,
  `empresa` varchar(100) DEFAULT NULL,
  `codigo_barras` varchar(50) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `segmento` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `presentacion` varchar(100) DEFAULT NULL,
  `unidad_presentacion` varchar(50) DEFAULT NULL,
  `agotados` tinyint(1) DEFAULT NULL,
  `inventarios` tinyint(1) DEFAULT NULL,
  `sugeridos` tinyint(1) DEFAULT NULL,
  `unidades_surtidas` tinyint(1) DEFAULT NULL,
  `devoluciones` tinyint(1) DEFAULT NULL,
  `averias` tinyint(1) DEFAULT NULL,
  `transferencias` tinyint(1) DEFAULT NULL,
  `precios` tinyint(1) DEFAULT NULL,
  `ventas` tinyint(1) DEFAULT NULL,
  `precio_producto` tinyint(1) DEFAULT NULL,
  `vigencia` tinyint(1) DEFAULT NULL,
  `competencia` tinyint(1) DEFAULT NULL,
  `actividades` tinyint(1) DEFAULT NULL,
  `causal_agotado` varchar(255) DEFAULT NULL,
  `fecha_devolucion` date DEFAULT NULL,
  `fecha_ultima_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos_cliente`
--

INSERT INTO `productos_cliente` (`id`, `cliente_id`, `nombre_cliente`, `empresa`, `codigo_barras`, `marca`, `categoria`, `segmento`, `descripcion`, `presentacion`, `unidad_presentacion`, `agotados`, `inventarios`, `sugeridos`, `unidades_surtidas`, `devoluciones`, `averias`, `transferencias`, `precios`, `ventas`, `precio_producto`, `vigencia`, `competencia`, `actividades`, `causal_agotado`, `fecha_devolucion`, `fecha_ultima_actualizacion`) VALUES
(43, 5, 'POSTOBON', 'Postobón S.A', '7701234567890', 'Pepsi', 'Bebidas', 'Gaseosa', 'Pepsi 400ml sabor original', '1', 'UND', 1, 1, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 1, NULL, NULL, '2025-07-23 15:12:51'),
(44, 5, 'POSTOBON', 'Postobón S.A', '7701234567891', 'Manzana', 'Bebidas', 'Gaseosa', 'Manzana Postobón 400ml', '1', 'UND', 1, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, NULL, NULL, '2025-07-23 15:12:51'),
(45, 5, 'POSTOBON', 'Postobón S.A', '7701234567892', 'Colombiana', 'Bebidas', 'Gaseosa', 'Colombiana 400ml tradicional', '1', 'UND', 1, 1, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 1, NULL, NULL, '2025-07-23 15:16:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puntos_venta`
--

CREATE TABLE `puntos_venta` (
  `id` int(11) NOT NULL,
  `pais` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `nombre` varchar(150) DEFAULT NULL,
  `canal` varchar(100) DEFAULT NULL,
  `sub_canal` varchar(100) DEFAULT NULL,
  `nombre_cadena` varchar(100) DEFAULT NULL,
  `nombre_formato` varchar(100) DEFAULT NULL,
  `cod_sap` varchar(50) DEFAULT NULL,
  `barrio` varchar(100) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `nombre_administrador` varchar(100) DEFAULT NULL,
  `contacto_bodega` varchar(100) DEFAULT NULL,
  `metros_cuadrados` varchar(50) DEFAULT NULL,
  `circuito_nielsen` varchar(100) DEFAULT NULL,
  `tipologia_punto_venta` varchar(100) DEFAULT NULL,
  `num_cajas_registradoras` int(11) DEFAULT NULL,
  `num_dependientes` int(11) DEFAULT NULL,
  `latitud` varchar(50) DEFAULT NULL,
  `longitud` varchar(50) DEFAULT NULL,
  `validar_georreferencia` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `puntos_venta`
--

INSERT INTO `puntos_venta` (`id`, `pais`, `region`, `ciudad`, `nombre`, `canal`, `sub_canal`, `nombre_cadena`, `nombre_formato`, `cod_sap`, `barrio`, `direccion`, `telefono`, `nombre_administrador`, `contacto_bodega`, `metros_cuadrados`, `circuito_nielsen`, `tipologia_punto_venta`, `num_cajas_registradoras`, `num_dependientes`, `latitud`, `longitud`, `validar_georreferencia`) VALUES
(100, 'Colombia', 'Atlántico', 'Barranquilla', 'Olímpica La 27', 'Moderno', 'Hiper', 'Olímpica', 'Estándar', '7701001', 'Centro', 'Cra 27 #45-67', '3001234567', 'Carlos Pérez', 'Juan Romero', '120', '0', 'Urbana', 4, 12, '10.9820', '-74.8010', 'SI'),
(101, 'Colombia', 'Atlántico', 'Barranquilla', 'Éxito Metropolitano', 'Moderno', 'Hiper', 'Éxito', 'Estándar', '7701002', 'Metropolitano', 'Calle 30 #1-23', '3001234568', 'Diana López', 'Luis Ríos', '300', '0', 'Residencial', 10, 40, '10.9931', '-74.7899', 'SI'),
(102, 'Colombia', 'Atlántico', 'Barranquilla', 'Carulla Buenavista', 'Moderno', 'Premium', 'Carulla', 'Gourmet', '7701003', 'Buenavista', 'Cra 53 #100-45', '3001234569', 'Laura Díaz', 'Ana Mejía', '200', '0', 'Alto', 6, 20, '11.0042', '-74.8123', 'SI'),
(103, 'Colombia', 'Atlántico', 'Barranquilla', 'Farmatodo Viva', 'Farmacia', 'Moderna', 'Farmatodo', 'Express', '7701004', 'Viva', 'Cra 51B #87-50', '3001234570', 'Miguel Suárez', 'Diana Torres', '90', '0', 'Farmacéutica', 3, 8, '11.0067', '-74.8145', 'SI'),
(104, 'Colombia', 'Atlántico', 'Barranquilla', 'Olímpica Villa Campestre', 'Moderno', 'Hiper', 'Olímpica', 'Mega', '7701005', 'Villa Campestre', 'Carrera 51B #106-23', '3001234571', 'Fernando Díaz', 'Luis Castillo', '250', '0', 'Urbana', 8, 30, '11.0100', '-74.8190', 'SI'),
(105, 'Colombia', 'Atlántico', 'Barranquilla', 'D1 Murillo', 'Descuento', 'D1', 'D1', 'Básico', '7701006', 'Murillo', 'Calle 45 #27-12', '3001234572', 'Patricia Ríos', 'Carlos Cano', '80', '0', 'Económico', 2, 10, '10.9911', '-74.8033', 'SI'),
(106, 'Colombia', 'Atlántico', 'Barranquilla', 'Ara San Felipe', 'Descuento', 'Ara', 'Ara', 'Básico', '7701007', 'San Felipe', 'Carrera 32 #56-78', '3001234573', 'Jorge Ariza', 'Natalia León', '100', '0', 'Mixto', 3, 12, '11.0003', '-74.7904', 'SI'),
(107, 'Colombia', 'Atlántico', 'Barranquilla', 'Olímpica Los Andes', 'Moderno', 'Hiper', 'Olímpica', 'Estándar', '7701008', 'Los Andes', 'Calle 45 #65-23', '3001234574', 'Luisa Mendoza', 'Eduardo Peña', '150', '0', 'Urbana', 5, 15, '10.9876', '-74.7999', 'SI'),
(108, 'Colombia', 'Atlántico', 'Barranquilla', 'La Rebaja Centro', 'Farmacia', 'Tradicional', 'La Rebaja', 'Express', '7701009', 'Centro', 'Carrera 40 #33-44', '3001234575', 'Daniela García', 'Pedro Acosta', '70', '0', 'Farmacéutica', 2, 7, '10.9800', '-74.7911', 'SI');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `punto_venta_id` int(11) NOT NULL,
  `usuario_movil_id` int(11) NOT NULL,
  `fecha_reporte` datetime NOT NULL,
  `tipo_modulo` enum('agotados','devoluciones','inventario','precios','otros') NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `hash_productos` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `reportes`
--

INSERT INTO `reportes` (`id`, `cliente_id`, `punto_venta_id`, `usuario_movil_id`, `fecha_reporte`, `tipo_modulo`, `creado_en`, `hash_productos`) VALUES
(117, 5, 100, 2, '2025-07-22 16:12:04', 'agotados', '2025-07-22 21:12:02', NULL),
(118, 5, 100, 2, '2025-07-22 16:12:49', 'devoluciones', '2025-07-22 21:12:47', NULL),
(119, 5, 100, 2, '2025-07-22 16:13:06', 'inventario', '2025-07-22 21:13:04', NULL),
(120, 5, 100, 2, '2025-07-22 16:13:24', 'precios', '2025-07-22 21:13:22', NULL),
(121, 5, 101, 2, '2025-07-22 16:24:11', 'agotados', '2025-07-22 21:24:10', NULL),
(122, 5, 101, 2, '2025-07-22 16:24:43', 'devoluciones', '2025-07-22 21:24:41', NULL),
(123, 5, 101, 2, '2025-07-22 16:24:56', 'inventario', '2025-07-22 21:24:55', NULL),
(124, 5, 101, 2, '2025-07-22 16:25:15', 'precios', '2025-07-22 21:25:13', NULL),
(125, 5, 102, 2, '2025-07-22 16:34:40', 'agotados', '2025-07-22 21:34:38', NULL),
(126, 5, 102, 2, '2025-07-22 16:35:45', 'devoluciones', '2025-07-22 21:35:44', NULL),
(127, 5, 102, 2, '2025-07-22 16:36:05', 'inventario', '2025-07-22 21:36:03', NULL),
(128, 5, 102, 2, '2025-07-22 16:36:18', 'precios', '2025-07-22 21:36:17', NULL),
(129, 5, 103, 2, '2025-07-23 14:38:11', 'agotados', '2025-07-23 19:38:09', NULL),
(130, 5, 103, 2, '2025-07-23 14:38:54', 'devoluciones', '2025-07-23 19:38:52', NULL),
(131, 5, 103, 2, '2025-07-23 14:39:09', 'inventario', '2025-07-23 19:39:08', NULL),
(132, 5, 103, 2, '2025-07-23 14:39:26', 'precios', '2025-07-23 19:39:25', NULL),
(133, 5, 108, 2, '2025-07-24 11:49:01', 'precios', '2025-07-24 16:49:00', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes_diarios_productos`
--

CREATE TABLE `reportes_diarios_productos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `usuario_movil_id` int(11) NOT NULL,
  `punto_venta_id` int(11) NOT NULL,
  `codigo_barras_producto` varchar(50) NOT NULL,
  `nombre_producto` varchar(255) DEFAULT NULL,
  `marca_producto` varchar(100) DEFAULT NULL,
  `fecha_hora_reporte` datetime DEFAULT current_timestamp(),
  `agotados` tinyint(1) DEFAULT NULL,
  `causal_agotado` varchar(255) DEFAULT NULL,
  `inventarios` int(11) DEFAULT NULL,
  `sugeridos` int(11) DEFAULT NULL,
  `unidades_surtidas` int(11) DEFAULT NULL,
  `devoluciones` int(11) DEFAULT NULL,
  `averias` int(11) DEFAULT NULL,
  `transferencias` int(11) DEFAULT NULL,
  `precio_producto` decimal(10,2) DEFAULT NULL,
  `ventas` decimal(10,2) DEFAULT NULL,
  `vigencia` tinyint(1) DEFAULT NULL,
  `competencia` tinyint(1) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes_hash`
--

CREATE TABLE `reportes_hash` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `punto_venta_id` int(11) DEFAULT NULL,
  `usuario_movil_id` int(11) DEFAULT NULL,
  `contenido_hash` varchar(32) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reporte_fotos_actividades`
--

CREATE TABLE `reporte_fotos_actividades` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `punto_venta_id` int(11) NOT NULL,
  `usuario_movil_id` int(11) NOT NULL,
  `tipo_actividad` varchar(100) NOT NULL,
  `descripcion_actividad` text DEFAULT NULL,
  `nombre_archivo_foto` varchar(255) NOT NULL,
  `ruta_servidor_foto` varchar(500) NOT NULL,
  `perceptual_hash` varchar(16) DEFAULT NULL,
  `es_reusada` tinyint(1) DEFAULT 0,
  `id_foto_original` int(11) DEFAULT NULL,
  `fecha_hora_captura` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reporte_fotos_actividades`
--

INSERT INTO `reporte_fotos_actividades` (`id`, `cliente_id`, `punto_venta_id`, `usuario_movil_id`, `tipo_actividad`, `descripcion_actividad`, `nombre_archivo_foto`, `ruta_servidor_foto`, `perceptual_hash`, `es_reusada`, `id_foto_original`, `fecha_hora_captura`) VALUES
(2, 5, 106, 2, 'fotos_despues', '', 'JPEG_20250723_170619_7529632068470251127.jpg', 'uploads/actividades/foto_68815ce394a68.jpg', NULL, 0, NULL, '2025-07-24 00:06:27'),
(3, 5, 108, 2, 'fotos_antes', '', 'JPEG_20250723_172242_1271479984639739075.jpg', 'uploads/actividades/foto_688160b47fada.jpg', NULL, 0, NULL, '2025-07-24 00:22:44'),
(4, 5, 108, 2, 'planometria', '', 'JPEG_20250723_172321_8613242310558472904.jpg', 'uploads/actividades/foto_688160def1ca0.jpg', NULL, 0, NULL, '2025-07-24 00:23:26'),
(5, 5, 108, 2, 'otras_fotos', '', 'JPEG_20250723_172352_4161729155560275638.jpg', 'uploads/actividades/foto_688160fdd68e3.jpg', NULL, 0, NULL, '2025-07-24 00:23:57'),
(6, 5, 106, 2, 'fotos_antes', '', 'JPEG_20250724_113515_4009589655836852135.jpg', 'uploads/actividades/foto_688260cc9eba9.jpg', NULL, 0, NULL, '2025-07-24 18:35:24'),
(7, 5, 107, 2, 'visibilidad', '', 'JPEG_20250724_114306_6320514442117768337.jpg', 'uploads/actividades/foto_688262a6a2195.jpg', NULL, 0, NULL, '2025-07-24 18:43:18'),
(8, 5, 108, 2, 'promocion', 'se deja todo organizado', 'JPEG_20250724_114630_8198920906299416724.jpg', 'uploads/actividades/foto_68826371371cf.jpg', NULL, 0, NULL, '2025-07-24 18:46:41'),
(9, 5, 107, 2, 'visibilidad', '', 'JPEG_20250724_120507_7733513468145995723.jpg', 'uploads/actividades/foto_688267ccd342e.jpg', NULL, 0, NULL, '2025-07-24 19:05:16'),
(10, 5, 108, 2, 'promocion', '', 'JPEG_20250724_120811_1844810000554164082.jpg', 'uploads/actividades/foto_688268883ce3e.jpg', NULL, 0, NULL, '2025-07-24 19:08:24'),
(11, 5, 108, 2, 'exhibicion', '', 'img_6882779fc96ee_1753380767.jpg', 'uploads/actividades/img_6882779fc96ee_1753380767.jpg', '7', 0, NULL, '2025-07-24 13:12:47'),
(12, 5, 107, 2, 'exhibicion', '', 'img_68827dc3c2efa_1753382339.jpg', 'uploads/actividades/img_68827dc3c2efa_1753382339.jpg', '0', 0, NULL, '2025-07-24 13:38:59'),
(13, 5, 107, 2, 'otras_fotos', '', 'img_68827e07af08c_1753382407.jpg', 'uploads/actividades/img_68827e07af08c_1753382407.jpg', '41797979610', 0, NULL, '2025-07-24 13:40:07'),
(14, 5, 108, 2, 'planometria', '', 'img_688282ebf2fcb_1753383659.jpg', 'uploads/actividades/img_688282ebf2fcb_1753383659.jpg', '0', 0, NULL, '2025-07-24 14:01:00'),
(15, 5, 108, 2, 'fotos_antes', '', 'img_6882837c58938_1753383804.jpg', 'uploads/actividades/img_6882837c58938_1753383804.jpg', '0', 0, NULL, '2025-07-24 14:03:24'),
(16, 5, 108, 2, 'promocion', '', 'img_6882854761bf4_1753384263.jpg', 'uploads/actividades/img_6882854761bf4_1753384263.jpg', '0', 0, NULL, '2025-07-24 14:11:03'),
(17, 5, 108, 2, 'planometria', '', 'img_688286562dc6e_1753384534.jpg', 'uploads/actividades/img_688286562dc6e_1753384534.jpg', '610', 0, NULL, '2025-07-24 14:15:34'),
(18, 5, 108, 2, 'visibilidad', '', 'img_68828780bc678_1753384832.jpg', 'uploads/actividades/img_68828780bc678_1753384832.jpg', '80', 0, NULL, '2025-07-24 14:20:32'),
(19, 5, 108, 2, 'planometria', '', 'img_688288c871f2c_1753385160.jpg', 'uploads/actividades/img_688288c871f2c_1753385160.jpg', '61591d07070f070d', 0, NULL, '2025-07-24 14:26:00'),
(20, 5, 108, 2, 'fotos_antes', '', 'img_6882893d83caf_1753385277.jpg', 'uploads/actividades/img_6882893d83caf_1753385277.jpg', '61591d07070f070d', 1, 19, '2025-07-24 14:27:57'),
(21, 5, 106, 2, 'fotos_antes', '', 'img_68828be1a9035_1753385953.jpg', 'uploads/actividades/img_68828be1a9035_1753385953.jpg', 'fa5243678f071000', 0, NULL, '2025-07-24 14:39:13'),
(22, 5, 106, 2, 'planometria', '', 'img_68828d0882338_1753386248.jpg', 'uploads/actividades/img_68828d0882338_1753386248.jpg', 'fa5243678f071000', 1, 21, '2025-07-24 14:44:08'),
(23, 5, 107, 2, 'visibilidad', '', 'img_68828d7c23338_1753386364.jpg', 'uploads/actividades/img_68828d7c23338_1753386364.jpg', 'b60e296363616000', 0, NULL, '2025-07-24 14:46:04'),
(24, 5, 107, 2, 'planometria', '', 'foto_68828f7133a30_1753386865.jpg', 'uploads/actividades/foto_68828f7133a30_1753386865.jpg', 'b60e296363616000', 1, 23, '2025-07-24 14:54:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rutas`
--

CREATE TABLE `rutas` (
  `id` int(11) NOT NULL,
  `id_promotor` int(11) DEFAULT NULL,
  `id_pv` int(11) DEFAULT NULL,
  `id_empresa` int(11) DEFAULT NULL,
  `ndia` int(11) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `horas` decimal(5,2) DEFAULT NULL,
  `bolsa` int(11) DEFAULT NULL,
  `nombre_promotor` varchar(150) DEFAULT NULL,
  `nombre_punto_venta` varchar(150) DEFAULT NULL,
  `nombre_empresa` varchar(150) DEFAULT NULL,
  `ciudad_pv` varchar(100) DEFAULT NULL,
  `departamento_pv` varchar(100) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  `codigo_carga` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rutas`
--

INSERT INTO `rutas` (`id`, `id_promotor`, `id_pv`, `id_empresa`, `ndia`, `fecha_inicio`, `horas`, `bolsa`, `nombre_promotor`, `nombre_punto_venta`, `nombre_empresa`, `ciudad_pv`, `departamento_pv`, `estado`, `codigo_carga`, `foto`) VALUES
(100, 2, 100, 5, 1, '2025-07-22', 0.60, 0, 'Maria Gomez', 'Olímpica La 27', 'POSTOBON', 'Barranquilla', 'Atlántico', 'ACTIVO', '687fde20e0307', NULL),
(101, 2, 101, 5, 1, '2025-07-22', 0.60, 0, 'Maria Gomez', 'Éxito Metropolitano', 'POSTOBON', 'Barranquilla', 'Atlántico', 'ACTIVO', '687fde20e0307', NULL),
(102, 2, 102, 5, 1, '2025-07-22', 0.60, 0, 'Maria Gomez', 'Carulla Buenavista', 'POSTOBON', 'Barranquilla', 'Atlántico', 'ACTIVO', '687fde20e0307', NULL),
(103, 2, 103, 5, 2, '2025-07-23', 0.60, 0, 'Maria Gomez', 'Farmatodo Viva', 'POSTOBON', 'Barranquilla', 'Atlántico', 'ACTIVO', '687fde20e0307', NULL),
(104, 2, 104, 5, 2, '2025-07-23', 0.60, 0, 'Maria Gomez', 'Olímpica Villa Campestre', 'POSTOBON', 'Barranquilla', 'Atlántico', 'ACTIVO', '687fde20e0307', NULL),
(105, 2, 105, 5, 2, '2025-07-23', 0.60, 0, 'Maria Gomez', 'D1 Murillo', 'POSTOBON', 'Barranquilla', 'Atlántico', 'ACTIVO', '687fde20e0307', NULL),
(106, 2, 106, 5, 3, '2025-07-24', 0.60, 0, 'Maria Gomez', 'Ara San Felipe', 'POSTOBON', 'Barranquilla', 'Atlántico', 'ACTIVO', '687fde20e0307', NULL),
(107, 2, 107, 5, 3, '2025-07-24', 0.60, 0, 'Maria Gomez', 'Olímpica Los Andes', 'POSTOBON', 'Barranquilla', 'Atlántico', 'ACTIVO', '687fde20e0307', NULL),
(108, 2, 108, 5, 3, '2025-07-24', 0.60, 0, 'Maria Gomez', 'La Rebaja Centro', 'POSTOBON', 'Barranquilla', 'Atlántico', 'ACTIVO', '687fde20e0307', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellidos` varchar(100) DEFAULT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `zona` varchar(50) DEFAULT NULL,
  `tipo_usuario` enum('administrador','movil','supervisor','cliente') DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellidos`, `cedula`, `zona`, `tipo_usuario`, `username`, `password`) VALUES
(1, 'Admin', 'Principal', '0000000000', 'Centro', 'administrador', 'admin', '1234'),
(2, 'Maria', 'Gomez', '102012354', 'Eje cafetero', 'movil', 'mgomez', '1020'),
(3, 'Alexander de jesus ', 'Rendon Cabeza', '1143135778', 'Norte', 'administrador', 'arendon', '1143'),
(4, 'POSTOBON', '', '900254785', 'barranquilla', 'cliente', 'postobon', '9002'),
(5, 'Mariluz', 'Cabeza', '1143135778', 'Eje cafetero', 'supervisor', 'mcabeza', '1143'),
(10, 'COCA COLA', '', '906578', 'CARTAGENA', 'cliente', 'cocacola', '9065');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_reportes_agotados`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_reportes_agotados` (
`reporte_id` int(11)
,`fecha_reporte` datetime
,`cliente_id` int(11)
,`nombre_cliente` varchar(150)
,`punto_venta_nombre_cadena` varchar(100)
,`punto_venta_direccion` varchar(200)
,`nombre_usuario_movil` varchar(100)
,`codigo_barras` varchar(100)
,`nombre_producto` varchar(255)
,`marca_producto` varchar(100)
,`agotados` tinyint(1)
,`causal_agotado` text
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_reportes_devoluciones`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_reportes_devoluciones` (
`reporte_id` int(11)
,`fecha_reporte` datetime
,`cliente_id` int(11)
,`nombre_cliente` varchar(150)
,`punto_venta_nombre_cadena` varchar(100)
,`punto_venta_direccion` varchar(200)
,`nombre_usuario_movil` varchar(100)
,`codigo_barras` varchar(100)
,`nombre_producto` varchar(255)
,`marca_producto` varchar(100)
,`cantidad_devuelta` int(11)
,`fecha_entrada_devolucion` date
,`causal_devolucion` text
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_reportes_inventarios`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_reportes_inventarios` (
`reporte_id` int(11)
,`fecha_reporte` datetime
,`cliente_id` int(11)
,`nombre_cliente` varchar(150)
,`punto_venta_nombre_cadena` varchar(100)
,`punto_venta_direccion` varchar(200)
,`nombre_usuario_movil` varchar(100)
,`codigo_barras` varchar(100)
,`nombre_producto` varchar(255)
,`marca_producto` varchar(100)
,`inventarios` int(11)
,`sugeridos` int(11)
,`unidades_surtidas` int(11)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_reportes_precios`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_reportes_precios` (
`reporte_id` int(11)
,`fecha_reporte` datetime
,`cliente_id` int(11)
,`nombre_cliente` varchar(150)
,`punto_venta_nombre_cadena` varchar(100)
,`punto_venta_direccion` varchar(200)
,`nombre_usuario_movil` varchar(100)
,`codigo_barras` varchar(100)
,`nombre_producto` varchar(255)
,`marca_producto` varchar(100)
,`precio` decimal(10,2)
);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nit` (`nit`),
  ADD KEY `idx_clientes_usuario_id` (`usuario_id`);

--
-- Indices de la tabla `clientes_modulos_estado`
--
ALTER TABLE `clientes_modulos_estado`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_clientes_modulos_estado` (`cliente_id`,`usuario_id`,`modulo_id`),
  ADD KEY `fk_clientes_modulos_estado_modulos` (`modulo_id`),
  ADD KEY `fk_clientes_modulos_estado_usuarios` (`usuario_id`);

--
-- Indices de la tabla `detalle_agotados`
--
ALTER TABLE `detalle_agotados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporte_id` (`reporte_id`);

--
-- Indices de la tabla `detalle_devoluciones`
--
ALTER TABLE `detalle_devoluciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporte_id` (`reporte_id`);

--
-- Indices de la tabla `detalle_inventarios`
--
ALTER TABLE `detalle_inventarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporte_id` (`reporte_id`);

--
-- Indices de la tabla `detalle_precios`
--
ALTER TABLE `detalle_precios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporte_id` (`reporte_id`);

--
-- Indices de la tabla `devoluciones_entradas`
--
ALTER TABLE `devoluciones_entradas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `detalle_devolucion_id` (`detalle_devolucion_id`);

--
-- Indices de la tabla `modulos`
--
ALTER TABLE `modulos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_modulo` (`nombre_modulo`);

--
-- Indices de la tabla `productos_cliente`
--
ALTER TABLE `productos_cliente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_productos_cliente_cliente_id` (`cliente_id`),
  ADD KEY `idx_productos_cliente_codigo_barras` (`codigo_barras`);

--
-- Indices de la tabla `puntos_venta`
--
ALTER TABLE `puntos_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_puntos_venta_ciudad_nombre` (`ciudad`,`nombre`);

--
-- Indices de la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `punto_venta_id` (`punto_venta_id`),
  ADD KEY `usuario_movil_id` (`usuario_movil_id`);

--
-- Indices de la tabla `reportes_diarios_productos`
--
ALTER TABLE `reportes_diarios_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reportes_cliente_fecha` (`cliente_id`,`fecha_hora_reporte`),
  ADD KEY `idx_reportes_punto_venta_fecha` (`punto_venta_id`,`fecha_hora_reporte`),
  ADD KEY `idx_reportes_usuario_movil_fecha` (`usuario_movil_id`,`fecha_hora_reporte`);

--
-- Indices de la tabla `reportes_hash`
--
ALTER TABLE `reportes_hash`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hash_lookup` (`cliente_id`,`punto_venta_id`,`contenido_hash`);

--
-- Indices de la tabla `reporte_fotos_actividades`
--
ALTER TABLE `reporte_fotos_actividades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_foto_cliente` (`cliente_id`),
  ADD KEY `fk_foto_punto_venta` (`punto_venta_id`),
  ADD KEY `fk_foto_usuario_movil` (`usuario_movil_id`),
  ADD KEY `idx_perceptual_hash` (`perceptual_hash`);

--
-- Indices de la tabla `rutas`
--
ALTER TABLE `rutas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rutas_promotor` (`id_promotor`),
  ADD KEY `idx_rutas_pv` (`id_pv`),
  ADD KEY `idx_rutas_empresa` (`id_empresa`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_usuarios_tipo` (`tipo_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `app_settings`
--
ALTER TABLE `app_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `clientes_modulos_estado`
--
ALTER TABLE `clientes_modulos_estado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_agotados`
--
ALTER TABLE `detalle_agotados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT de la tabla `detalle_devoluciones`
--
ALTER TABLE `detalle_devoluciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT de la tabla `detalle_inventarios`
--
ALTER TABLE `detalle_inventarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de la tabla `detalle_precios`
--
ALTER TABLE `detalle_precios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT de la tabla `devoluciones_entradas`
--
ALTER TABLE `devoluciones_entradas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de la tabla `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos_cliente`
--
ALTER TABLE `productos_cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de la tabla `puntos_venta`
--
ALTER TABLE `puntos_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT de la tabla `reportes_diarios_productos`
--
ALTER TABLE `reportes_diarios_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de la tabla `reportes_hash`
--
ALTER TABLE `reportes_hash`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `reporte_fotos_actividades`
--
ALTER TABLE `reporte_fotos_actividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `rutas`
--
ALTER TABLE `rutas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_reportes_agotados`
--
DROP TABLE IF EXISTS `vista_reportes_agotados`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_reportes_agotados`  AS SELECT `r`.`id` AS `reporte_id`, `r`.`fecha_reporte` AS `fecha_reporte`, `r`.`cliente_id` AS `cliente_id`, `c`.`razon_social` AS `nombre_cliente`, `pv`.`nombre_cadena` AS `punto_venta_nombre_cadena`, `pv`.`direccion` AS `punto_venta_direccion`, `u`.`nombre` AS `nombre_usuario_movil`, `da`.`codigo_barras` AS `codigo_barras`, `da`.`nombre_producto` AS `nombre_producto`, `da`.`marca_producto` AS `marca_producto`, `da`.`agotados` AS `agotados`, `da`.`causal_agotado` AS `causal_agotado` FROM ((((`reportes` `r` join `detalle_agotados` `da` on(`r`.`id` = `da`.`reporte_id`)) join `clientes` `c` on(`r`.`cliente_id` = `c`.`id`)) join `puntos_venta` `pv` on(`r`.`punto_venta_id` = `pv`.`id`)) join `usuarios` `u` on(`r`.`usuario_movil_id` = `u`.`id`)) WHERE `r`.`tipo_modulo` = 'agotados' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_reportes_devoluciones`
--
DROP TABLE IF EXISTS `vista_reportes_devoluciones`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_reportes_devoluciones`  AS SELECT `r`.`id` AS `reporte_id`, `r`.`fecha_reporte` AS `fecha_reporte`, `r`.`cliente_id` AS `cliente_id`, `c`.`razon_social` AS `nombre_cliente`, `pv`.`nombre_cadena` AS `punto_venta_nombre_cadena`, `pv`.`direccion` AS `punto_venta_direccion`, `u`.`nombre` AS `nombre_usuario_movil`, `dd`.`codigo_barras` AS `codigo_barras`, `dd`.`nombre_producto` AS `nombre_producto`, `dd`.`marca_producto` AS `marca_producto`, `de`.`cantidad` AS `cantidad_devuelta`, `de`.`fecha` AS `fecha_entrada_devolucion`, `dd`.`causal_devolucion` AS `causal_devolucion` FROM (((((`reportes` `r` join `detalle_devoluciones` `dd` on(`r`.`id` = `dd`.`reporte_id`)) join `devoluciones_entradas` `de` on(`dd`.`id` = `de`.`detalle_devolucion_id`)) join `clientes` `c` on(`r`.`cliente_id` = `c`.`id`)) join `puntos_venta` `pv` on(`r`.`punto_venta_id` = `pv`.`id`)) join `usuarios` `u` on(`r`.`usuario_movil_id` = `u`.`id`)) WHERE `r`.`tipo_modulo` = 'devoluciones' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_reportes_inventarios`
--
DROP TABLE IF EXISTS `vista_reportes_inventarios`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_reportes_inventarios`  AS SELECT `r`.`id` AS `reporte_id`, `r`.`fecha_reporte` AS `fecha_reporte`, `r`.`cliente_id` AS `cliente_id`, `c`.`razon_social` AS `nombre_cliente`, `pv`.`nombre_cadena` AS `punto_venta_nombre_cadena`, `pv`.`direccion` AS `punto_venta_direccion`, `u`.`nombre` AS `nombre_usuario_movil`, `di`.`codigo_barras` AS `codigo_barras`, `di`.`nombre_producto` AS `nombre_producto`, `di`.`marca_producto` AS `marca_producto`, `di`.`inventarios` AS `inventarios`, `di`.`sugeridos` AS `sugeridos`, `di`.`unidades_surtidas` AS `unidades_surtidas` FROM ((((`reportes` `r` join `detalle_inventarios` `di` on(`r`.`id` = `di`.`reporte_id`)) join `clientes` `c` on(`r`.`cliente_id` = `c`.`id`)) join `puntos_venta` `pv` on(`r`.`punto_venta_id` = `pv`.`id`)) join `usuarios` `u` on(`r`.`usuario_movil_id` = `u`.`id`)) WHERE `r`.`tipo_modulo` = 'inventario' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_reportes_precios`
--
DROP TABLE IF EXISTS `vista_reportes_precios`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_reportes_precios`  AS SELECT `r`.`id` AS `reporte_id`, `r`.`fecha_reporte` AS `fecha_reporte`, `r`.`cliente_id` AS `cliente_id`, `c`.`razon_social` AS `nombre_cliente`, `pv`.`nombre_cadena` AS `punto_venta_nombre_cadena`, `pv`.`direccion` AS `punto_venta_direccion`, `u`.`nombre` AS `nombre_usuario_movil`, `dp`.`codigo_barras` AS `codigo_barras`, `dp`.`nombre_producto` AS `nombre_producto`, `dp`.`marca_producto` AS `marca_producto`, `dp`.`precio` AS `precio` FROM ((((`reportes` `r` join `detalle_precios` `dp` on(`r`.`id` = `dp`.`reporte_id`)) join `clientes` `c` on(`r`.`cliente_id` = `c`.`id`)) join `puntos_venta` `pv` on(`r`.`punto_venta_id` = `pv`.`id`)) join `usuarios` `u` on(`r`.`usuario_movil_id` = `u`.`id`)) WHERE `r`.`tipo_modulo` = 'precios' ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_clientes_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `clientes_modulos_estado`
--
ALTER TABLE `clientes_modulos_estado`
  ADD CONSTRAINT `fk_clientes_modulos_estado_clientes` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_clientes_modulos_estado_modulos` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_clientes_modulos_estado_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_agotados`
--
ALTER TABLE `detalle_agotados`
  ADD CONSTRAINT `detalle_agotados_ibfk_1` FOREIGN KEY (`reporte_id`) REFERENCES `reportes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_devoluciones`
--
ALTER TABLE `detalle_devoluciones`
  ADD CONSTRAINT `detalle_devoluciones_ibfk_1` FOREIGN KEY (`reporte_id`) REFERENCES `reportes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_inventarios`
--
ALTER TABLE `detalle_inventarios`
  ADD CONSTRAINT `detalle_inventarios_ibfk_1` FOREIGN KEY (`reporte_id`) REFERENCES `reportes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_precios`
--
ALTER TABLE `detalle_precios`
  ADD CONSTRAINT `detalle_precios_ibfk_1` FOREIGN KEY (`reporte_id`) REFERENCES `reportes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `devoluciones_entradas`
--
ALTER TABLE `devoluciones_entradas`
  ADD CONSTRAINT `devoluciones_entradas_ibfk_1` FOREIGN KEY (`detalle_devolucion_id`) REFERENCES `detalle_devoluciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos_cliente`
--
ALTER TABLE `productos_cliente`
  ADD CONSTRAINT `fk_productos_cliente_clientes` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `reportes_ibfk_2` FOREIGN KEY (`punto_venta_id`) REFERENCES `puntos_venta` (`id`),
  ADD CONSTRAINT `reportes_ibfk_3` FOREIGN KEY (`usuario_movil_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `reportes_diarios_productos`
--
ALTER TABLE `reportes_diarios_productos`
  ADD CONSTRAINT `reportes_diarios_productos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reportes_diarios_productos_ibfk_2` FOREIGN KEY (`usuario_movil_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reportes_diarios_productos_ibfk_3` FOREIGN KEY (`punto_venta_id`) REFERENCES `puntos_venta` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reporte_fotos_actividades`
--
ALTER TABLE `reporte_fotos_actividades`
  ADD CONSTRAINT `fk_foto_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_foto_punto_venta` FOREIGN KEY (`punto_venta_id`) REFERENCES `puntos_venta` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_foto_usuario_movil` FOREIGN KEY (`usuario_movil_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `rutas`
--
ALTER TABLE `rutas`
  ADD CONSTRAINT `fk_rutas_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rutas_promotor` FOREIGN KEY (`id_promotor`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rutas_pv` FOREIGN KEY (`id_pv`) REFERENCES `puntos_venta` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

