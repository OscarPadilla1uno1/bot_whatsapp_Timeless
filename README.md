# WhatsApp Chatbot con Administración, Gestión de Pedidos y Entregas

Un sistema completo de chatbot para WhatsApp que automatiza la atención al cliente, gestión de pedidos y coordinación de entregas para empresas.

## 📋 Descripción

Este proyecto consiste en el desarrollo de un asistente virtual automatizado que opera de manera continua, proporcionando:

- **Atención 24/7**: Respuesta inmediata a consultas de clientes
- **Gestión de Pedidos**: Automatización del proceso de ventas
- **Optimización de Entregas**: Coordinación eficiente de rutas de delivery
- **Panel Administrativo**: Control total sobre operaciones y reportes

## ✨ Características Principales

### 🤖 Chatbot Inteligente
- Flujos de conversación personalizados
- Menús interactivos con botones y opciones numéricas
- Conexión directa con base de datos de productos
- Respuestas automatizadas y contextualmente relevantes

### 📦 Gestión de Pedidos
- Procesamiento automático de órdenes
- Seguimiento en tiempo real del estado de pedidos
- Integración con inventario de productos
- Confirmaciones automáticas de pedidos

### 🚚 Sistema de Entregas
- Panel de control para gestión de rutas
- Optimización automática de rutas de delivery
- Alertas y notificaciones automáticas
- Seguimiento de entregas en tiempo real

### 📊 Panel Administrativo
- Dashboard con métricas en tiempo real
- Reportes de ventas y entregas
- Gestión de productos y precios
- Análisis de rendimiento del chatbot

## 🛠️ Tecnologías Utilizadas

- **Backend**: Laravel 10+ (PHP 8.1+)
- **Base de Datos**: MySQL/PostgreSQL
- **Frontend**: Laravel Blade + Vue.js/Alpine.js
- **WhatsApp Integration**: WhatsApp Business API
- **Queue System**: Redis/Database queues
- **Hosting**: Servidor VPS con Apache/Nginx
- **Autenticación**: Laravel Sanctum/Passport

## 📁 Estructura del Proyecto (Laravel)

```
whatsapp-chatbot/
├── app/
│   ├── Console/
│   │   └── Commands/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │     └── AdminController/ 
│   │   │   ├── Auth/
│   │   │   │     ├── AuthenticatedSessionController/
│   │   │   │     ├── ConfirmablePasswordController/
│   │   │   │     ├── EmailVerificationPromptController/
│   │   │   │     ├── NewPasswordController/
│   │   │   │     ├── PasswordCOntroller/
│   │   │   │     ├── PasswordResetLinkController/
│   │   │   │     ├── RegisteredUserController/
│   │   │   │     └── VerifyEmailController/
│   │   │   ├── Controller/
│   │   │   ├── DashboardController/
│   │   │   ├── ProfileController/ 
│   │   │   └── VroomController/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Models/
│   │   ├── Cliente/
│   │   ├── DetallePedido/
│   │   ├── MenuDiario/
│   │   ├── Pago/
│   │   ├── Pedido/
│   │   ├── Platillo/
│   │   ├── RouteAssignment/
│   │   └── User/
│   ├── Jobs/
│   └── Events/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
│   ├── css/
│   ├── js/
│   └── assets/
├── resources/
│   ├── views/
│   │   ├── admin/
│   │   │     ├── Dashboard/
│   │   │     ├── menu-diario/
│   │   │     ├── pedidos/
│   │   │     ├── pedidosProgramados/
│   │   │     ├── platillos/
│   │   │     └── users/
│   │   ├── auth/
│   │   │     ├── confirm-password/
│   │   │     ├── forgot-password/
│   │   │     ├── login/
│   │   │     ├── register/
│   │   │     ├── reset/
│   │   │     └── verify/
│   │   ├── cocina/
│   │   │     └── dashboard/
│   │   ├── components/
│   │   │     ├── application-logo/
│   │   │     ├── auth-session-status/
│   │   │     ├── danger-button/
│   │   │     ├── dropdown/
│   │   │     ├── input-error/
│   │   │     ├── input-label/
│   │   │     ├── modal/
│   │   │     ├── nav-link/
│   │   │     ├── primary-button/
│   │   │     ├── responsive-nav-link/
│   │   │     ├── secondary-button/
│   │   │     └── text-input/
│   │   ├── layouts/
│   │   │     ├── app/
│   │   │     ├── guest/
│   │   │     ├── navigation/
│   │   │     ├── navigacionAdmin/
│   │   │     ├── navigationCocina/
│   │   │     └── navigationMotorista/
│   │   ├── livewire/
│   │   │     └── pedidos-status/
│   │   ├── motorista/
│   │   │     └── dashboard/
│   │   ├── pdf/
│   │   │     └── factura/
│   │   ├── profile/
│   │   │     └── partials/
│   │   │            ├── delete-user-form/
│   │   │            ├── update-password-form/
│   │   │            └── update-profile-information/  
│   │   ├── vehicle/
│   │   │     └── show/  
│   │   └── vroom/
│   │         └── map/  
│   ├── js/
│   └── css/
├── routes/
│   ├── web.php
│   ├── api.php
│   └── channels.php
├── storage/
│   ├── app/
│   ├── framework/
│   └── logs/
├── tests/
│   ├── Feature/
│   └── Unit/
└── vendor/
```

## ⚙️ Instalación y Configuración

### Requisitos Previos
- PHP 8.1 o superior
- Composer
- MySQL/PostgreSQL
- Servidor VPS con Apache/Nginx

### Configuración Inicial

1. **Clonar el repositorio**
```bash
git clone [repository-url]
cd bot_whatsapp_timeless
```

2. **Instalar dependencias de PHP**
```bash
composer install
```

3. **Configurar variables de entorno**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurar base de datos en .env**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=whatsapp_chatbot
DB_USERNAME=your_username
DB_PASSWORD=your_password

# WhatsApp API Configuration
WHATSAPP_API_TOKEN=your_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_id
WHATSAPP_VERIFY_TOKEN=your_verify_token

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue Configuration
QUEUE_CONNECTION=redis
```

5. **Ejecutar migraciones y seeders**
```bash
php artisan migrate
php artisan db:seed
```

6. **Instalar dependencias de frontend**
```bash
npm install
npm run build
```

7. **Configurar permisos de storage**
```bash
php artisan storage:link
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

8. **Iniciar el servidor**
```bash
php artisan serve
```

9. **Iniciar worker de colas (en terminal separado)**
```bash
php artisan queue:work
```

## 🚀 Funcionalidades Implementadas

### Fase 1: Configuración Base
- [x] Instalación en servidor VPS
- [x] Configuración de Laravel y dependencias
- [x] Conexión con WhatsApp Business API
- [x] Configuración de base de datos MySQL
- [x] Implementación de sistema de colas con Redis

### Fase 2: Chatbot Core
- [x] Creación de modelos (Message, Conversation, Flow)
- [x] Controladores para manejo de webhooks
- [x] Servicios para procesamiento de mensajes
- [x] Middleware para validación de WhatsApp
- [x] Jobs para procesamiento asíncrono

### Fase 3: Gestión de Pedidos
- [x] Modelos: Product, Order, OrderItem
- [x] Controladores para API de pedidos
- [x] Servicios de carrito y checkout
- [x] Jobs para confirmación de pedidos
- [x] Events y Listeners para notificaciones

### Fase 4: Sistema de Entregas
- [x] Modelos: Delivery, Route, Driver
- [x] Algoritmos de optimización de rutas
- [x] Panel administrativo con Laravel Blade
- [x] API para aplicación móvil de repartidores
- [x] Notificaciones en tiempo real

### Fase 5: Panel Administrativo
- [x] Dashboard con métricas (Laravel Charts)
- [x] CRUD para productos y configuraciones
- [x] Reportes con Laravel Excel
- [x] Autenticación con Laravel Sanctum
- [x] Roles y permiissiones con Spatie

## 📊 Métricas y Reportes

El sistema genera reportes automáticos sobre:

- **Ventas**: Ingresos diarios, semanales y mensuales
- **Pedidos**: Cantidad, estados y tendencias
- **Entregas**: Tiempos, rutas optimizadas y eficiencia
- **Chatbot**: Interacciones, conversiones y satisfacción

## 🔧 Mantenimiento

### Comandos de Mantenimiento Laravel

```bash
# Optimizar aplicación para producción
php artisan optimize

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Actualizar composer
composer update

# Ejecutar migraciones en producción
php artisan migrate --force

# Reiniciar workers de colas
php artisan queue:restart
```

### Soporte Incluido
- Actualización de flujos de conversación
- Mejoras y optimizaciones mensuales
- Monitoreo de rendimiento
- Corrección de errores y bugs

### Actualizaciones Regulares
- Nuevas funcionalidades según feedback
- Optimización de algoritmos de rutas
- Mejoras en la interfaz administrativa
- Actualizaciones de seguridad

## 📞 Soporte Técnico

Para soporte técnico y consultas:

- **Email**: timelesscodetgu@gmail.com
- **Empresa**: Timeless Software
- **Soporte**: Disponible con plan de mantenimiento

## 📋 Términos y Condiciones

- Cotización válida por 30 días
- Pago: 5% al inicio, 95% al finalizar
- Cambios adicionales pueden generar costos extra
- Garantía sujeta a plan de mantenimiento

## 🎯 Beneficios Empresariales

- **Reducción de Costos**: Automatización de atención al cliente
- **Mejora en Ventas**: Disponibilidad 24/7 para pedidos
- **Optimización Logística**: Rutas eficientes de entrega
- **Satisfacción del Cliente**: Respuestas inmediatas y seguimiento

## 📈 ROI Estimado

- Reducción del 60% en costos de atención al cliente
- Aumento del 40% en conversiones de ventas
- Optimización del 30% en rutas de entrega
- Mejora del 50% en satisfacción del cliente

---

**Desarrollado por Timeless Software** | **Contacto**: timelesscodetgu@gmail.com