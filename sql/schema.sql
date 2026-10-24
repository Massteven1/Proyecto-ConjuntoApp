-- Configuración inicial de la base de datos
CREATE DATABASE IF NOT EXISTS conjunto_app 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE conjunto_app;

-- ==========================================
-- M1. SEGURIDAD, USUARIOS Y UNIDADES
-- ==========================================

-- 1. Tabla: roles
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla: usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    documento VARCHAR(30) NOT NULL UNIQUE,
    correo VARCHAR(150) NOT NULL UNIQUE,
    telefono VARCHAR(30),
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_usuarios_roles FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla: torres
CREATE TABLE torres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    pisos INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla: apartamentos
CREATE TABLE apartamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    torre_id INT NOT NULL,
    numero VARCHAR(20) NOT NULL,
    piso INT NOT NULL,
    area_m2 DECIMAL(8,2) NOT NULL,
    coeficiente DECIMAL(8,4) NOT NULL,
    UNIQUE KEY uk_torre_numero (torre_id, numero),
    CONSTRAINT fk_apartamentos_torres FOREIGN KEY (torre_id) REFERENCES torres(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla: residencias
CREATE TABLE residencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    apartamento_id INT NOT NULL,
    tipo ENUM('propietario', 'arrendatario') NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    CONSTRAINT fk_residencias_usuarios FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_residencias_apartamentos FOREIGN KEY (apartamento_id) REFERENCES apartamentos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- M2. ZONAS COMUNES Y RESERVAS
-- ==========================================

-- 6. Tabla: zonas_comunes
CREATE TABLE zonas_comunes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    aforo INT NOT NULL,
    hora_apertura TIME NOT NULL,
    hora_cierre TIME NOT NULL,
    costo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    requiere_deposito TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabla: reservas
CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zona_id INT NOT NULL,
    apartamento_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    estado VARCHAR(30) NOT NULL,
    observaciones TEXT,
    CONSTRAINT fk_reservas_zonas FOREIGN KEY (zona_id) REFERENCES zonas_comunes(id),
    CONSTRAINT fk_reservas_apartamentos FOREIGN KEY (apartamento_id) REFERENCES apartamentos(id),
    CONSTRAINT fk_reservas_usuarios FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- M3. PQRS
-- ==========================================

-- 8. Tabla: categorias_pqrs
CREATE TABLE categorias_pqrs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabla: pqrs
CREATE TABLE pqrs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    radicado VARCHAR(30) NOT NULL UNIQUE,
    tipo VARCHAR(50) NOT NULL,
    categoria_id INT NOT NULL,
    usuario_id INT NOT NULL,
    apartamento_id INT NOT NULL,
    asunto VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,
    adjunto VARCHAR(255) NULL,
    estado ENUM('Radicada', 'En trámite', 'Respondida', 'Cerrada') NOT NULL DEFAULT 'Radicada',
    fecha_limite DATE NOT NULL,
    calificacion TINYINT NULL CHECK (calificacion BETWEEN 1 AND 5),
    CONSTRAINT fk_pqrs_categorias FOREIGN KEY (categoria_id) REFERENCES categorias_pqrs(id),
    CONSTRAINT fk_pqrs_usuarios FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_pqrs_apartamentos FOREIGN KEY (apartamento_id) REFERENCES apartamentos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Tabla: pqrs_seguimiento
CREATE TABLE pqrs_seguimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pqrs_id INT NOT NULL,
    usuario_id INT NOT NULL,
    estado_anterior VARCHAR(30) NULL,
    estado_nuevo VARCHAR(30) NOT NULL,
    comentario TEXT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_seguimiento_pqrs FOREIGN KEY (pqrs_id) REFERENCES pqrs(id),
    CONSTRAINT fk_seguimiento_usuarios FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- M4. PORTERÍA Y VISITANTES
-- ==========================================

-- 11. Tabla: visitantes
CREATE TABLE visitantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento VARCHAR(30) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    telefono VARCHAR(30)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Tabla: autorizaciones
CREATE TABLE autorizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitante_id INT NOT NULL,
    apartamento_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_visita DATE NOT NULL,
    con_vehiculo TINYINT(1) NOT NULL DEFAULT 0,
    placa VARCHAR(20) NULL,
    CONSTRAINT fk_autorizaciones_visitantes FOREIGN KEY (visitante_id) REFERENCES visitantes(id),
    CONSTRAINT fk_autorizaciones_apartamentos FOREIGN KEY (apartamento_id) REFERENCES apartamentos(id),
    CONSTRAINT fk_autorizaciones_usuarios FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Tabla: parqueaderos_visitantes
CREATE TABLE parqueaderos_visitantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL,
    ocupado TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Tabla: ingresos
CREATE TABLE ingresos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitante_id INT NOT NULL,
    apartamento_id INT NOT NULL,
    portero_id INT NOT NULL,
    parqueadero_id INT NULL,
    placa VARCHAR(20) NULL,
    fecha_ingreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_salida DATETIME NULL,
    CONSTRAINT fk_ingresos_visitantes FOREIGN KEY (visitante_id) REFERENCES visitantes(id),
    CONSTRAINT fk_ingresos_apartamentos FOREIGN KEY (apartamento_id) REFERENCES apartamentos(id),
    CONSTRAINT fk_ingresos_portero FOREIGN KEY (portero_id) REFERENCES usuarios(id),
    CONSTRAINT fk_ingresos_parqueaderos FOREIGN KEY (parqueadero_id) REFERENCES parqueaderos_visitantes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- M5. CUOTAS, PAGOS, COMUNICADOS Y TABLERO
-- ==========================================

-- 15. Tabla: cuotas
CREATE TABLE cuotas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apartamento_id INT NOT NULL,
    periodo CHAR(7) NOT NULL COMMENT 'Formato AAAA-MM',
    valor DECIMAL(12,2) NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    saldo DECIMAL(12,2) NOT NULL,
    estado VARCHAR(30) NOT NULL,
    UNIQUE KEY uk_apartamento_periodo (apartamento_id, periodo),
    CONSTRAINT fk_cuotas_apartamentos FOREIGN KEY (apartamento_id) REFERENCES apartamentos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Tabla: pagos
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cuota_id INT NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    medio VARCHAR(50) NOT NULL,
    soporte VARCHAR(255) NULL,
    registrado_por INT NOT NULL,
    CONSTRAINT fk_pagos_cuotas FOREIGN KEY (cuota_id) REFERENCES cuotas(id),
    CONSTRAINT fk_pagos_usuarios FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Tabla: comunicados
CREATE TABLE comunicados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    contenido TEXT NOT NULL,
    fecha_publicacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    vigente_hasta DATE NOT NULL,
    autor_id INT NOT NULL,
    CONSTRAINT fk_comunicados_usuarios FOREIGN KEY (autor_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla opcional (Completa las 17 tablas mencionadas en el documento): comunicados_leidos
CREATE TABLE comunicados_leidos (
    comunicado_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (comunicado_id, usuario_id),
    CONSTRAINT fk_leidos_comunicados FOREIGN KEY (comunicado_id) REFERENCES comunicados(id),
    CONSTRAINT fk_leidos_usuarios FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- OBJETOS ADICIONALES EXIGIDOS EN LA BD
-- Vista, Procedimiento Almacenado y Trigger
-- ==========================================

-- 1. Vista: v_estado_cuenta (Estado de cuenta por apartamento con saldo y mora)
CREATE OR REPLACE VIEW v_estado_cuenta AS
SELECT 
    a.id AS apartamento_id,
    t.nombre AS torre,
    a.numero AS apartamento,
    COUNT(CASE WHEN c.saldo > 0 THEN 1 END) AS cuotas_pendientes,
    COUNT(CASE WHEN c.saldo > 0 AND c.fecha_vencimiento < CURDATE() THEN 1 END) AS cuotas_en_mora,
    COALESCE(SUM(c.saldo), 0.00) AS saldo_total
FROM apartamentos a
INNER JOIN torres t ON a.torre_id = t.id
LEFT JOIN cuotas c ON a.id = c.apartamento_id
GROUP BY a.id, t.nombre, a.numero;

-- 2. Procedimiento Almacenado: sp_generar_cuotas
-- Genera las cuotas del mes para todos los apartamentos según su coeficiente
DELIMITER $$
CREATE PROCEDURE sp_generar_cuotas(
    IN p_periodo CHAR(7),
    IN p_presupuesto_base DECIMAL(12,2),
    IN p_fecha_vencimiento DATE
)
BEGIN
    INSERT INTO cuotas (apartamento_id, periodo, valor, fecha_vencimiento, saldo, estado)
    SELECT 
        a.id,
        p_periodo,
        ROUND(p_presupuesto_base * a.coeficiente, 2),
        p_fecha_vencimiento,
        ROUND(p_presupuesto_base * a.coeficiente, 2),
        'Pendiente'
    FROM apartamentos a
    WHERE NOT EXISTS (
        SELECT 1 FROM cuotas c 
        WHERE c.apartamento_id = a.id AND c.periodo = p_periodo
    );
END$$
DELIMITER ;

-- 3. Trigger: Actualizar saldo de la cuota al insertar un pago
DELIMITER $$
CREATE TRIGGER tr_actualizar_saldo_pago
AFTER INSERT ON pagos
FOR EACH ROW
BEGIN
    UPDATE cuotas
    SET 
        saldo = GREATEST(0, saldo - NEW.valor),
        estado = CASE 
            WHEN (saldo - NEW.valor) <= 0 THEN 'Pagada'
            ELSE 'Abonada'
        END
    WHERE id = NEW.cuota_id;
END$$
DELIMITER ;