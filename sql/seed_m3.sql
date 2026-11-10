-- Agrego datos de M3 después de importar conjunto_app.sql en una base limpia.
-- Mantengo los 40 apartamentos existentes y puedo ejecutar este archivo más de una vez.

START TRANSACTION;

-- Completo 30 usuarios con 15 residentes asociados a los apartamentos 16 a 30.
INSERT IGNORE INTO usuarios
    (id, rol_id, nombres, apellidos, documento, correo, telefono, password_hash, activo)
VALUES
    (16, 2, 'Residente', 'Prueba 16', '100000016', 'residente16@conjunto.com', '+573001000016', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
    (17, 2, 'Residente', 'Prueba 17', '100000017', 'residente17@conjunto.com', '+573001000017', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
    (18, 2, 'Residente', 'Prueba 18', '100000018', 'residente18@conjunto.com', '+573001000018', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
    (19, 2, 'Residente', 'Prueba 19', '100000019', 'residente19@conjunto.com', '+573001000019', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
    (20, 2, 'Residente', 'Prueba 20', '100000020', 'residente20@conjunto.com', '+573001000020', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
    (21, 2, 'Residente', 'Prueba 21', '100000021', 'residente21@conjunto.com', '+573001000021', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
    (22, 2, 'Residente', 'Prueba 22', '100000022', 'residente22@conjunto.com', '+573001000022', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
    (23, 2, 'Residente', 'Prueba 23', '100000023', 'residente23@conjunto.com', '+573001000023', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
    (24, 2, 'Residente', 'Prueba 24', '100000024', 'residente24@conjunto.com', '+573001000024', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
    (25, 2, 'Residente', 'Prueba 25', '100000025', 'residente25@conjunto.com', '+573001000025', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
    (26, 2, 'Residente', 'Prueba 26', '100000026', 'residente26@conjunto.com', '+573001000026', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
    (27, 2, 'Residente', 'Prueba 27', '100000027', 'residente27@conjunto.com', '+573001000027', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
    (28, 2, 'Residente', 'Prueba 28', '100000028', 'residente28@conjunto.com', '+573001000028', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
    (29, 2, 'Residente', 'Prueba 29', '100000029', 'residente29@conjunto.com', '+573001000029', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1),
    (30, 2, 'Residente', 'Prueba 30', '100000030', 'residente30@conjunto.com', '+573001000030', '$2y$10$Ux9l1ylise.jJNxGqzVfVu/lf.kpqVnz8oXx.nYXlLJ3u475UOyxK', 1);

-- Asocio cada nuevo residente con un apartamento activo.
INSERT IGNORE INTO residencias
    (id, usuario_id, apartamento_id, tipo, fecha_inicio, fecha_fin)
VALUES
    (16, 16, 16, 'propietario', '2024-01-01', NULL),
    (17, 17, 17, 'arrendatario', '2024-01-01', NULL),
    (18, 18, 18, 'propietario', '2024-01-01', NULL),
    (19, 19, 19, 'arrendatario', '2024-01-01', NULL),
    (20, 20, 20, 'propietario', '2024-01-01', NULL),
    (21, 21, 21, 'arrendatario', '2024-01-01', NULL),
    (22, 22, 22, 'propietario', '2024-01-01', NULL),
    (23, 23, 23, 'arrendatario', '2024-01-01', NULL),
    (24, 24, 24, 'propietario', '2024-01-01', NULL),
    (25, 25, 25, 'arrendatario', '2024-01-01', NULL),
    (26, 26, 26, 'propietario', '2024-01-01', NULL),
    (27, 27, 27, 'arrendatario', '2024-01-01', NULL),
    (28, 28, 28, 'propietario', '2024-01-01', NULL),
    (29, 29, 29, 'arrendatario', '2024-01-01', NULL),
    (30, 30, 30, 'propietario', '2024-01-01', NULL);

-- Completo los tres meses con la misma regla de cálculo y sin duplicar periodos.
CALL sp_generar_cuotas('2025-10', 9000000.00, '2025-10-15');
CALL sp_generar_cuotas('2025-11', 9000000.00, '2025-11-15');
CALL sp_generar_cuotas('2025-12', 9000000.00, '2025-12-15');

COMMIT;
