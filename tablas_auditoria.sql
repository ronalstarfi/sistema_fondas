CREATE TABLE IF NOT EXISTS `auditoria_solicitudes` (
  `id_auditoria` int(11) NOT NULL AUTO_INCREMENT,
  `id_solicitud` int(11) NOT NULL,
  `estatus_anterior` varchar(50) DEFAULT NULL,
  `estatus_nuevo` varchar(50) NOT NULL,
  `usuario_que_cambio` varchar(100) NOT NULL,
  `cedula_usuario` varchar(20) DEFAULT NULL,
  `rol_usuario` varchar(50) DEFAULT NULL,
  `direccion_ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha_movimiento` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_solicitud` (`id_solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auditoria_general` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_movimiento` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `usuario` varchar(100) NOT NULL,
  `direccion_ip` varchar(45) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
