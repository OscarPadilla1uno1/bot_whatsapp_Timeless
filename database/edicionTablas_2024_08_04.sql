-- 1. Eliminar la columna 'email' si ya no la necesitas
ALTER TABLE clientes DROP COLUMN email;


-- 2. AGREGAR EL ESTADO DE DESPACHADO A LA COLUMNA DEL ESTADO DE UN PEDIDO
ALTER TABLE pedidos
MODIFY COLUMN estado ENUM('pendiente', 'en preparación', 'entregado', 'cancelado', 'despachado') 
DEFAULT 'pendiente';