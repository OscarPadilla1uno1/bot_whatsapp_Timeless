-- Insertar clientes
INSERT INTO clientes (nombre, telefono, email) VALUES
('Ana Gómez', '5551234567', 'ana.gomez@example.com'),
('Carlos Ruiz', '5559876543', 'carlos.ruiz@example.com'),
('Luisa Martínez', '5551122334', 'luisa.martinez@example.com');

-- Insertar platillos
INSERT INTO platillos (nombre, descripcion, precio_base, imagen_url) VALUES
('Tacos al Pastor', 'Deliciosos tacos con carne al pastor, piña y cebolla.', 45.00, 'https://ejemplo.com/img/tacos.jpg'),
('Enchiladas Verdes', 'Enchiladas rellenas de pollo con salsa verde y crema.', 60.00, 'https://ejemplo.com/img/enchiladas.jpg'),
('Pozole Rojo', 'Pozole tradicional con carne de cerdo y maíz cacahuazintle.', 70.00, 'https://ejemplo.com/img/pozole.jpg');

-- Insertar menú diario (suponiendo fecha de hoy y mañana)
INSERT INTO menu_diario (fecha, platillo_id, cantidad_disponible) VALUES
(CURDATE(), 1, 10),
(CURDATE(), 2, 5),
(CURDATE() + INTERVAL 1 DAY, 2, 8),
(CURDATE() + INTERVAL 1 DAY, 3, 12);

-- Insertar pedidos
INSERT INTO pedidos (cliente_id, direccion_envio, estado, total) VALUES
(1, 'Calle 123, Ciudad A', 'pendiente', 90.00),
(2, 'Av. Reforma 456, Ciudad B', 'en preparación', 70.00),
(3, 'Boulevard Central 789, Ciudad C', 'entregado', 120.00);

-- Insertar detalles de pedidos
INSERT INTO detalle_pedido (pedido_id, platillo_id, cantidad, precio_unitario) VALUES
(1, 1, 2, 45.00), -- 2 tacos al pastor
(2, 3, 1, 70.00), -- 1 pozole
(3, 2, 2, 60.00); -- 2 enchiladas verdes

-- Insertar pagos
INSERT INTO pagos (pedido_id, metodo_pago, estado_pago, fecha_pago, referencia_transaccion) VALUES
(1, 'en_linea', 'pendiente', NULL, NULL),
(2, 'efectivo', 'confirmado', NOW(), NULL),
(3, 'en_linea', 'confirmado', NOW(), 'TRX1234567890');


INSERT INTO platillos (nombre, descripcion, precio_base, imagen_url) VALUES
('Chilaquiles Rojos', 'Totopos bañados en salsa roja con pollo deshebrado, crema y queso.', 55.00, 'https://ejemplo.com/img/chilaquiles_rojos.jpg'),
('Chilaquiles Verdes', 'Totopos en salsa verde acompañados de pollo, cebolla y queso.', 55.00, 'https://ejemplo.com/img/chilaquiles_verdes.jpg'),
('Quesadillas de Flor de Calabaza', 'Quesadillas hechas a mano con flor de calabaza y queso.', 35.00, 'https://ejemplo.com/img/quesadillas.jpg'),
('Tortas Ahogadas', 'Tortas rellenas de carnitas bañadas en salsa picante.', 50.00, 'https://ejemplo.com/img/tortas_ahogadas.jpg'),
('Mole Poblano', 'Pechuga de pollo bañada en mole poblano con arroz.', 80.00, 'https://ejemplo.com/img/mole_poblano.jpg'),
('Tamales de Elote', 'Tamales dulces de elote servidos con crema.', 25.00, 'https://ejemplo.com/img/tamales_elote.jpg'),
('Tostadas de Tinga', 'Tostadas crujientes con tinga de pollo, lechuga, crema y queso.', 40.00, 'https://ejemplo.com/img/tostadas_tinga.jpg'),
('Flautas Doradas', 'Flautas de pollo crujientes con crema, lechuga y queso.', 45.00, 'https://ejemplo.com/img/flautas.jpg'),
('Caldo de Res', 'Caldo caliente con carne de res, verduras y arroz.', 75.00, 'https://ejemplo.com/img/caldo_res.jpg'),
('Sopes de Chorizo', 'Sopes de maíz con frijoles, chorizo, crema y queso.', 38.00, 'https://ejemplo.com/img/sopes_chorizo.jpg'),
('Empanadas de Queso', 'Empanadas fritas rellenas de queso y acompañadas de salsa.', 30.00, 'https://ejemplo.com/img/empanadas_queso.jpg');
