-- Venta de medicamentos desde la receta del doctor (checklist "comprar
-- con nosotros" al registrar el historial). Seguro de correr más de una
-- vez (CREATE TABLE IF NOT EXISTS).

CREATE TABLE IF NOT EXISTS factura_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    historial_id INT NOT NULL,
    paciente_id INT NOT NULL,
    centro_salud_id INT NOT NULL,
    numero_factura VARCHAR(50) NOT NULL UNIQUE,
    fecha_venta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    usuario_id INT NULL,
    FOREIGN KEY (historial_id) REFERENCES historial_medico (id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES paciente (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (centro_salud_id) REFERENCES centro_salud (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuario (usercod) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS factura_venta_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (factura_venta_id) REFERENCES factura_venta (id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES producto (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
