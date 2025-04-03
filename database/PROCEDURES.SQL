-- Procedimiento almacenado para insertar o actualizar el menú diario con la fecha actual
DELIMITER $$
CREATE OR REPLACE PROCEDURE actualizar_menu_diario(
    IN platillo_id INT,
    IN cantidad_maxima INT
)
BEGIN
    DECLARE existe INT;
    
    -- Verificar si el platillo ya está en el menú diario para la fecha actual
    SELECT COUNT(*) INTO existe FROM menu_diario WHERE fecha = CURDATE() AND platillo_id = platillo_id;
    
    IF existe > 0 THEN
        -- Si ya existe, actualizar la cantidad
        UPDATE menu_diario
        SET cantidad_disponible = cantidad_maxima
        WHERE fecha = CURDATE() AND platillo_id = platillo_id;
    ELSE
        -- Si no existe, insertarlo
        INSERT INTO menu_diario (fecha, platillo_id, cantidad_disponible)
        VALUES (CURDATE(), platillo_id, cantidad_maxima);
    END IF;
END $$
DELIMITER ;


-- Procedimiento almacenado para obtener el menú del día actual
DELIMITER $$
CREATE OR REPLACE PROCEDURE obtener_menu_diario()
BEGIN
    SELECT md.fecha, p.nombre, p.descripcion, p.precio_base, md.cantidad_disponible
    FROM menu_diario md
    JOIN platillos p ON md.platillo_id = p.id
    WHERE md.fecha = CURDATE();
END $$
DELIMITER ;


-- Procedimiento almacenado para obtener todos los platillos disponibles
DELIMITER //

CREATE PROCEDURE ObtenerTodosLosPlatillos()
BEGIN
    SELECT id, nombre, descripcion, precio_base, imagen_url, activo 
    FROM platillos
    ORDER BY nombre ASC;
END //

DELIMITER ;