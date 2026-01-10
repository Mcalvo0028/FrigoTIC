# Copilot Instructions - FrigoTIC

## Información del Proyecto
- **Nombre del Proyecto:** FrigoTIC
- **Empresa Desarrolladora:** MJCRSoftware
- **Repositorio:** https://github.com/Mcvo0028/FrigoTIC.git
- **Ubicación de Desarrollo:** C:\Programacion\FrigoTIC
- **Ubicación de Producción:** C:\FrigoTIC
- **Base de Datos:** MySQL (ubicada en C:\FrigoTIC\MySQL)

## Descripción General
FrigoTIC es una aplicación web para gestionar un frigorífico compartido en el trabajo. Funciona en una intranet y permite:
- Al **administrador**: comprar bebidas, rellenar el frigorífico y gestionar pagos
- A los **usuarios**: coger bebidas, apuntarlas y pagar mensualmente al admin

## Stack Tecnológico
- **Backend:** PHP 8.x
- **Base de Datos:** MySQL 8.x
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Librerías:** Chart.js (gráficos), Font Awesome (iconos)
- **Servidor:** Apache (XAMPP/WAMP o similar)

## Estructura de Carpetas
```
FrigoTIC/
├── .github/                    # Configuración de GitHub
│   └── copilot-instructions.md
├── app/                        # Código principal de la aplicación
│   ├── config/                 # Configuración (BD, SMTP, etc.)
│   ├── controllers/            # Controladores PHP
│   ├── models/                 # Modelos de datos
│   ├── views/                  # Vistas HTML/PHP
│   │   ├── admin/              # Vistas del administrador
│   │   ├── user/               # Vistas del usuario
│   │   ├── auth/               # Vistas de autenticación
│   │   └── partials/           # Componentes reutilizables
│   └── helpers/                # Funciones auxiliares
├── public/                     # Archivos públicos
│   ├── css/                    # Estilos CSS
│   ├── js/                     # JavaScript
│   ├── images/                 # Imágenes
│   └── uploads/                # Archivos subidos (facturas, productos)
├── database/                   # Scripts de base de datos
│   ├── migrations/             # Migraciones
│   └── seeds/                  # Datos iniciales
├── docs/                       # Documentación
├── Script_Temp/                # Scripts temporales de pruebas
├── README.md                   # Documentación principal
├── Procesos.md                 # Registro de progresos
├── version_info.txt            # Versión actual de la app
└── Project_Structure.html      # Estructura para programadores
```

## Roles de Usuario

### Administrador (Admin)
**Pestañas disponibles:**
1. **Usuarios** - Gestión de usuarios, reseteo de contraseñas, ver/marcar deudas como pagadas
2. **Productos** - CRUD de productos con imagen, precio compra/venta, stock
3. **Facturas** - Subir/descargar/modificar/eliminar PDFs de facturas
4. **Movimientos** - Lista de todos los movimientos con filtros
5. **Gráficos** - Estadísticas visuales con diferentes tipos de gráficos
6. **Configuración** - Contraseña admin, datos BD, SMTP, plantillas de correo

**Tema visual:** Tonos ROJOS

### Usuario Final
**Pestañas disponibles:**
1. **Productos** - Ver productos disponibles, precios, apuntar consumos
2. **Movimientos** - Historial personal con filtros
3. **Perfil** - Cambiar contraseña y correo electrónico

**Tema visual:** Tonos AZULES

## Reglas de Desarrollo

### Autenticación
- Contraseñas hasheadas (password_hash de PHP)
- NO se requieren requisitos de seguridad complejos
- Campos obligatorios: nombre de usuario, contraseña, correo electrónico
- Sistema de reseteo de contraseña con cambio obligatorio en primer login

### UI/UX
- Iconos + texto en todas las opciones (Font Awesome)
- Header: Logo FrigoTIC (izq) | Ayuda | Nombre usuario | Cerrar sesión (der)
- Paginación en todas las tablas/listas
- Selector de elementos por página
- Sistema de ayuda contextual

### Base de Datos
- Instancia separada de MySQL en C:\FrigoTIC\MySQL
- my.ini específico para esta instancia
- Puerto diferente al MySQL existente (ej: 3307)

### Correos SMTP
- Cuenta: frigotic@gmail.com
- Servidor: smtp.gmail.com
- Puerto: 587 (TLS)
- Plantillas configurables: bienvenida, aviso de pago, etc.

## Convenciones de Código

### PHP
- PSR-4 para autoloading
- Nombres de clases en PascalCase
- Nombres de métodos y variables en camelCase
- Comentarios en español

### CSS
- Variables CSS para colores de tema
- Clases BEM cuando sea apropiado
- Responsive design

### JavaScript
- ES6+ syntax
- Funciones descriptivas
- Validación de formularios del lado cliente

### SQL
- Nombres de tablas en snake_case y plural (ej: `usuarios`, `productos`)
- Nombres de columnas en snake_case
- Claves primarias: `id`
- Claves foráneas: `nombre_tabla_id`

## Tablas de Base de Datos

### usuarios
- id, nombre_usuario, password_hash, email, rol (admin/user), fecha_registro, debe_cambiar_password, activo

### productos
- id, nombre, descripcion, precio_compra, precio_venta, stock, imagen, activo, fecha_creacion

### movimientos
- id, usuario_id, producto_id, tipo (consumo/pago/ajuste), cantidad, precio_unitario, total, fecha_hora

### facturas
- id, nombre_archivo, ruta_archivo, fecha_subida, descripcion

### configuracion
- id, clave, valor, descripcion

### plantillas_correo
- id, tipo, asunto, cuerpo, variables_disponibles

## Comandos Git Frecuentes
```bash
git add .
git commit -m "Descripción del cambio"
git push origin main
```

## Notas Importantes
1. Mantener actualizado `Procesos.md` con cada avance
2. Incrementar versión en `version_info.txt` con cada release
3. Scripts de prueba van en `Script_Temp/`
4. No subir archivos sensibles (.env, configuraciones con passwords)
5. Documentar cambios importantes en `Project_Structure.html`
