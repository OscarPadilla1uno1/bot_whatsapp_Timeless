--Actualizar el menu diario
DELIMITER $$

CREATE OR REPLACE PROCEDURE actualizar_menu_diario(
    IN p_platillo_id INT,
    IN p_cantidad_maxima INT,
    IN p_fecha DATE
)
BEGIN
    DECLARE existe INT;

    -- Verificar si el platillo ya existe en el menú para la fecha proporcionada
    SELECT COUNT(*) INTO existe 
    FROM menu_diario 
    WHERE fecha = p_fecha AND platillo_id = p_platillo_id;

    IF existe > 0 THEN
        -- Si ya existe, actualizar la cantidad disponible
        UPDATE menu_diario
        SET cantidad_disponible = p_cantidad_maxima
        WHERE fecha = p_fecha AND platillo_id = p_platillo_id;
    ELSE
        -- Si no existe, insertarlo en el menú para esa fecha
        INSERT INTO menu_diario (fecha, platillo_id, cantidad_disponible)
        VALUES (p_fecha, p_platillo_id, p_cantidad_maxima);
    END IF;
END$$

DELIMITER ;





-- Procedimiento almacenado para obtener el menú del día actual
DELIMITER $$
CREATE OR REPLACE PROCEDURE obtener_menu_diario()
BEGIN
    SELECT p.id, md.fecha, p.nombre, p.descripcion, p.precio_base, md.cantidad_disponible
    FROM menu_diario md
    JOIN platillos p ON md.platillo_id = p.id
    WHERE md.fecha = CURDATE();
END $$
DELIMITER ;


-- Procedimiento almacenado para obtener todos los platillos disponibles
DELIMITER //

CREATE OR REPLACE PROCEDURE ObtenerTodosLosPlatillos()
BEGIN
    SELECT id, nombre, descripcion, precio_base, imagen_url, activo 
    FROM platillos WHERE activo = TRUE
    ORDER BY nombre ASC;
END //

DELIMITER ;

DELIMITER $$

CREATE OR REPLACE PROCEDURE obtener_menu_diario_por_fecha(IN fecha_param DATE)
BEGIN
    SELECT p.id, md.fecha, p.nombre, p.descripcion, p.precio_base, md.cantidad_disponible
    FROM menu_diario md
    JOIN platillos p ON md.platillo_id = p.id
    WHERE md.fecha = fecha_param;
END $$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE eliminar_platillo_menu (
    IN p_platillo_id INT,
    IN p_fecha DATE
)
BEGIN
    DELETE FROM menu_diario
    WHERE platillo_id = p_platillo_id AND fecha = p_fecha;
END $$

DELIMITER ;

--Procedimiento almacenado para guardar un platillo nuevo, la subida de la imagen esta pendiente
DELIMITER $$

CREATE PROCEDURE InsertarPlatillo (
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT,
    IN p_precio_base DECIMAL(10,2)
)
BEGIN
    INSERT INTO platillos (nombre, descripcion, precio_base, activo)
    VALUES (p_nombre, p_descripcion, p_precio_base, TRUE);
END $$

DELIMITER ;

--PROCEDIMIENTO PARA PODER ACTUALIZAR LOS PLATILLOS

DELIMITER $$

CREATE OR REPLACE PROCEDURE sp_actualizar_platillo (
    IN p_id INT,
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT,
    IN p_precio_base DECIMAL(10,2)
)
BEGIN
    UPDATE platillos
    SET 
        nombre = p_nombre,
        descripcion = p_descripcion,
        precio_base = p_precio_base
    WHERE id = p_id;
END $$

DELIMITER ;


DELIMITER $$

CREATE OR REPLACE PROCEDURE sp_borrar_platillo (
    IN p_id INT
)
BEGIN
    UPDATE platillos
    SET activo = FALSE
    WHERE id = p_id;
END$$

DELIMITER ;
