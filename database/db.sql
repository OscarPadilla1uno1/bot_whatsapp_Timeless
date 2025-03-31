-- Crear base de datos
CREATE DATABASE IF NOT EXISTS restaurante_bot;
USE restaurante_bot;

-- Tabla: usuarios
-- Esta tabla almacena a los clientes que hacen pedidos a través del bot
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL UNIQUE, -- Se asume que el número de teléfono es único
    email VARCHAR(100),
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: platillos
-- Catálogo general de todos los platillos del restaurante
CREATE TABLE platillos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio_base DECIMAL(10,2) NOT NULL,
    imagen_url TEXT,
    activo BOOLEAN DEFAULT TRUE -- Para marcar si el platillo aún está disponible en general
);

-- Tabla: menu_diario
-- Define qué platillos están disponibles en una fecha específica
-- Relación: Muchos menu_diario a Un platillo
CREATE TABLE menu_diario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    platillo_id INT NOT NULL, -- FK hacia platillos
    cantidad_disponible INT NOT NULL,
    UNIQUE(fecha, platillo_id), -- No puede repetirse el mismo platillo en la misma fecha
    FOREIGN KEY (platillo_id) REFERENCES platillos(id) ON DELETE CASCADE
);

-- Tabla: pedidos
-- Almacena información general de los pedidos hechos por los usuarios
-- Relación: Muchos pedidos a Un usuario
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL, -- FK hacia usuarios
    fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    direccion_envio TEXT NOT NULL,
    estado ENUM('pendiente', 'en preparación', 'entregado', 'cancelado') DEFAULT 'pendiente',
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla: detalle_pedido
-- Relación muchos a muchos entre pedidos y platillos, con información adicional (cantidad, precio)
-- Cada fila representa un platillo incluido en un pedido
-- Relaciones:
--    Muchos detalles a Un pedido
--    Muchos detalles a Un platillo
CREATE TABLE detalle_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL, -- FK hacia pedidos
    platillo_id INT NOT NULL, -- FK hacia platillos
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL, -- Precio en el momento del pedido (por si cambia luego)
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (platillo_id) REFERENCES platillos(id)
);

-- Tabla: pagos
-- Guarda la información del método y estado de pago de cada pedido
-- Relación: Un pago por pedido (uno a uno)
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL, -- FK hacia pedidos
    metodo_pago ENUM('en_linea', 'efectivo') NOT NULL,
    estado_pago ENUM('pendiente', 'confirmado', 'fallido') DEFAULT 'pendiente',
    fecha_pago DATETIME,
    referencia_transaccion VARCHAR(100),
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
);
