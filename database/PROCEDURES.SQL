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

-- SP PROCESO PARA LA GENERACION Y GUARDADO DE UN PEDIDO EN LA BASE DE DATOS

DELIMITER $$

CREATE PROCEDURE PEDIDO_CONFIRMADO(
    IN P_NOMBRE_CLIENTE VARCHAR(100),
    IN P_TELEFONO VARCHAR(20),
    IN P_DIRECCION TEXT,
    OUT O_MENSAJE TEXT,
    OUT O_TOTAL_FINAL DECIMAL(10,2)
)
proc: BEGIN
    DECLARE id_cliente INT;
    DECLARE id_pedido INT;
    DECLARE platillo INT;
    DECLARE cantidad INT;
    DECLARE precio_unitario DECIMAL(10,2);
    DECLARE subtotal DECIMAL(10,2) DEFAULT 0.00;
    DECLARE hoy DATE DEFAULT CURDATE();

    DECLARE exit_handler BOOLEAN DEFAULT FALSE;
    
    -- Cursor para recorrer los platillos de la tabla temporal
    DECLARE cur CURSOR FOR 
        SELECT platillo_id, cantidad FROM temp_pedido_detalle;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET platillo = NULL;
    
    -- Declaración del manejador de errores
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
    BEGIN
        SET O_MENSAJE = 'Error: Ha ocurrido un error en la base de datos.';
        SET O_TOTAL_FINAL = 0.00;
        SET exit_handler = TRUE;
    END;

    START TRANSACTION;
    
    -- Validación básica
    IF P_NOMBRE_CLIENTE IS NULL OR P_TELEFONO IS NULL OR P_DIRECCION IS NULL THEN
        SET O_MENSAJE = 'Error: nombre, teléfono y dirección son obligatorios.';
        SET O_TOTAL_FINAL = 0.00;
        LEAVE proc;
    END IF;

    -- Verificar si el cliente ya existe
    SELECT id INTO id_cliente FROM clientes WHERE telefono = P_TELEFONO LIMIT 1;

    IF id_cliente IS NULL THEN
        INSERT INTO clientes(nombre, telefono) VALUES (P_NOMBRE_CLIENTE, P_TELEFONO);
        SET id_cliente = LAST_INSERT_ID();
    END IF;

    -- Crear el pedido, por el momento se mantiene como  0.0 el total para calcularlo al final y entregarlo
    INSERT INTO pedidos (cliente_id, direccion_envio, total)
    VALUES (id_cliente, P_DIRECCION, 0.00);

    SET id_pedido = LAST_INSERT_ID();

    OPEN cur;

    leer: LOOP
        FETCH cur INTO platillo, cantidad;
        IF platillo IS NULL THEN
            LEAVE leer;
        END IF;

        -- Verificar existencia en menú diario y disponibilidad
        SELECT md.cantidad_disponible, p.precio_base 
        INTO @disponible, @precio
        FROM menu_diario md
        JOIN platillos p ON p.id = md.platillo_id
        WHERE md.fecha = hoy AND md.platillo_id = platillo
        LIMIT 1;

        IF @disponible IS NULL THEN
            SET O_MENSAJE = CONCAT('Error: el platillo ID ', platillo, ' no está en el menú de hoy.');
            ROLLBACK;
            LEAVE proc;
        END IF;

        IF cantidad > @disponible THEN
            SET O_MENSAJE = CONCAT('Error: no hay suficientes unidades disponibles del platillo ID ', platillo, '.');
            ROLLBACK;
            LEAVE proc;
        END IF;

        -- Insertar detalle
        INSERT INTO detalle_pedido(pedido_id, platillo_id, cantidad, precio_unitario)
        VALUES (id_pedido, platillo, cantidad, @precio);

        -- Actualizar stock en menú
        UPDATE menu_diario
        SET cantidad_disponible = cantidad_disponible - cantidad
        WHERE platillo_id = platillo AND fecha = hoy;

        -- Sumar al total
        SET subtotal = subtotal + (cantidad * @precio);


    END LOOP;

    CLOSE cur;

    -- Segunda verificacion para asegurarnos de que la tabla temporal si tuvo platillos durante la ejecucion

    SELECT COUNT(*) INTO @count FROM temp_pedido_detalle;
    IF @count = 0 THEN
        SET O_MENSAJE = 'Error: No hay platillos en el pedido';
        ROLLBACK;
        LEAVE proc;
    END IF;

    -- Actualizar total del pedido
    UPDATE pedidos SET total = subtotal WHERE id = id_pedido;

    IF exit_handler THEN
        ROLLBACK;
        LEAVE proc;
    END IF;

    COMMIT;

    SET O_TOTAL_FINAL = subtotal;
    SET O_MENSAJE = 'Pedido registrado exitosamente.';

END$$

DELIMITER ;





-- Procedimiento almacenado para obtener el menú del día actual
DELIMITER $$
CREATE OR REPLACE PROCEDURE obtener_menu_diario()
BEGIN
    SELECT p.id, md.fecha, p.nombre, p.descripcion, p.precio_base, md.cantidad_disponible, p.imagen_url
    FROM menu_diario md
    JOIN platillos p ON md.platillo_id = p.id
    WHERE md.fecha = CURDATE();
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
    WHERE md.fecha = fecha_param;
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
