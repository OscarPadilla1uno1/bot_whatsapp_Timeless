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
    SELECT p.id, md.fecha, p.nombre, p.descripcion, p.precio_base, md.cantidad_disponible, p.imagen_url
    FROM menu_diario md
    JOIN platillos p ON md.platillo_id = p.id
    WHERE md.fecha = CURDATE() AND p.activo = TRUE;
END $$
DELIMITER ;


-- Procedimiento almacenado para obtener todos los platillos disponibles
DELIMITER $$

CREATE OR REPLACE PROCEDURE ObtenerTodosLosPlatillos()
BEGIN
    SELECT id, nombre, descripcion, precio_base, imagen_url, activo 
    FROM platillos WHERE activo = TRUE
    ORDER BY nombre ASC;
END $$

DELIMITER ;

DELIMITER $$

CREATE OR REPLACE PROCEDURE obtener_menu_diario_por_fecha(IN fecha_param DATE)
BEGIN
    SELECT p.id, md.fecha, p.nombre, p.descripcion, p.precio_base, md.cantidad_disponible
    FROM menu_diario md
    JOIN platillos p ON md.platillo_id = p.id
    WHERE md.fecha = fecha_param AND p.activo = TRUE;
END $$

DELIMITER ;

DELIMITER $$

CREATE OR REPLACE PROCEDURE eliminar_platillo_menu (
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

CREATE OR REPLACE PROCEDURE InsertarPlatillo (
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT,
    IN p_precio_base DECIMAL(10,2),
    IN p_imagen_url TEXT -- Nuevo parámetro para la URL de la imagen
)
BEGIN
    INSERT INTO platillos (nombre, descripcion, precio_base, imagen_url, activo)
    VALUES (p_nombre, p_descripcion, p_precio_base, p_imagen_url, TRUE);
END $$

DELIMITER ;

--PROCEDIMIENTO PARA PODER ACTUALIZAR LOS PLATILLOS

DELIMITER $$

CREATE OR REPLACE PROCEDURE sp_actualizar_platillo (
    IN p_id INT,
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT,
    IN p_precio_base DECIMAL(10,2),
    IN p_imagen_url VARCHAR(255) -- Añadimos la URL de la imagen
)
BEGIN
    UPDATE platillos
    SET 
        nombre = p_nombre,
        descripcion = p_descripcion,
        precio_base = p_precio_base,
        imagen_url = p_imagen_url -- Actualizamos la imagen
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


DELIMITER $$
CREATE OR REPLACE PROCEDURE actualizar_cantidad_menu(IN p_platillo_id INT, IN p_cantidad INT, IN p_fecha DATE)
BEGIN
    DECLARE rows_affected INT;

    UPDATE menu_diario
    SET cantidad_disponible = p_cantidad
    WHERE platillo_id = p_platillo_id AND fecha = p_fecha;

    SET rows_affected = ROW_COUNT();

    IF rows_affected > 0 THEN
        SELECT 'Cantidad actualizada correctamente.' AS mensaje;
    ELSE
        SELECT 'No se encontró el platillo para la fecha especificada.' AS mensaje;
    END IF;
END$$
DELIMITER ;


-- USUARIOS e informacion
DELIMITER //
CREATE PROCEDURE sp_obtener_usuarios()
BEGIN
    SELECT users.id, users.name, users.email, GROUP_CONCAT(permissions.name SEPARATOR ', ') AS permisos
    FROM users
    LEFT JOIN model_has_permissions ON users.id = model_has_permissions.model_id
    LEFT JOIN permissions ON model_has_permissions.permission_id = permissions.id
    GROUP BY users.id;
END //
DELIMITER ;

-- Obtener los permisos existentes
DELIMITER //
CREATE PROCEDURE sp_obtener_permisos()
BEGIN
    SELECT id, name FROM permissions;
END //
DELIMITER ;
