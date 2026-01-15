# 📋 Registro de Procesos - FrigoTIC

## Versión 1.1.1 - Mejoras de Seguridad

### Nuevas Funcionalidades de Seguridad ✅

1. **Protección CSRF (Cross-Site Request Forgery)**
   - Tokens CSRF generados para cada sesión
   - Verificación en todos los formularios críticos (login, cambio contraseña, perfil)
   - Métodos: `generateCsrfToken()`, `verifyCsrfToken()`, `regenerateCsrfToken()`, `csrfField()`
   - Regeneración automática del token tras acciones críticas

2. **Rate Limiting (Protección contra Fuerza Bruta)**
   - Máximo 5 intentos de login antes del bloqueo
   - Bloqueo de 15 minutos tras exceder los intentos
   - Basado en IP del cliente
   - Logs de seguridad para intentos bloqueados

3. **Prevención de Enumeración de Usuarios**
   - Mensaje de error genérico: "Las credenciales proporcionadas no son válidas"
   - No se diferencia entre usuario inexistente, inactivo o contraseña incorrecta
   - Tiempo de respuesta constante para evitar ataques de timing

4. **Mejoras en Validación de Sesión**
   - Validación por dominio (evita compartir sesiones entre dominios)
   - Verificación de fingerprint (User-Agent)
   - Timeout de inactividad (30 minutos)
   - Regeneración de session ID tras login exitoso
   - Reinicio de sesión tras destrucción para evitar errores

5. **Logging de Seguridad**
   - Registro de logins exitosos con IP
   - Registro de logouts con usuario e IP
   - Registro de bloqueos por rate limiting
   - Registro de sesiones expiradas por inactividad
   - Registro de posibles robos de sesión (User-Agent diferente)

6. **Mejoras en Validación de Contraseña**
   - Longitud mínima de 6 caracteres
   - Validación tanto en cambio de contraseña como en perfil de usuario

### Mejoras de UI ✅

1. **Toggle de Visibilidad de Contraseña**
   - Icono de ojo dentro del campo de contraseña
   - Disponible en: login, cambio de contraseña, perfil, configuración
   - Separador visual entre campo y botón
   - Cambio de icono al alternar (ojo abierto/cerrado)

### Archivos Modificados

| Archivo | Cambios |
|---------|---------|
| `app/controllers/AuthController.php` | CSRF, Rate Limiting, mensajes genéricos, validación sesión mejorada, DI |
| `app/views/auth/login.php` | Campo CSRF, toggle contraseña |
| `app/views/auth/change-password.php` | Campo CSRF, toggle contraseña |
| `app/views/user/perfil.php` | Campo CSRF, validación CSRF, toggle contraseña, validación longitud |
| `public/css/style.css` | Estilos para `.password-wrapper` y `.password-toggle` |
| `version_info.txt` | Actualizado a 1.1.1 |

### Correcciones de Bugs

- ✅ URLs hardcodeadas con `/frigotic/` causaban error 404
- ✅ Sesiones compartidas entre localhost y frigotic.es (ahora aisladas por dominio)
- ✅ `isChangePasswordPage()` usaba `strpos()` (ahora comparación exacta)
- ✅ `destroySession()` fallaba si la sesión no estaba activa

### Constantes de Configuración

```php
MAX_LOGIN_ATTEMPTS = 5           // Intentos antes de bloqueo
LOCKOUT_TIME = 900               // 15 minutos de bloqueo
GENERIC_LOGIN_ERROR = '...'      // Mensaje genérico de error
```

---

## Versión 1.1.0 - Mejoras y Correcciones

### Nuevas Funcionalidades ✅

1. **Sistema de Exportación a PDF**
   - Nuevo archivo `public/export.php` para manejar todas las exportaciones
   - Nuevo helper `app/helpers/PdfHelper.php` para generación de informes HTML/PDF
   - Exportación disponible en: Usuarios, Productos, Facturas, Movimientos, Gráficos
   - Los gráficos se exportan con datos de los últimos 30 días en formato tabular
   - Imágenes de productos incluidas en la exportación (convertidas a base64)
   - Informe de baja de usuario con resumen de consumos y pagos

2. **Mejoras en Gestión de Usuarios**
   - Añadido campo de teléfono en la tabla de usuarios
   - Visualización de deuda negativa corregida (ej: -3,25 € cuando el usuario debe dinero)
   - Corrección del bloqueo de popups al eliminar usuarios

3. **Mejoras en Movimientos**
   - Nuevo tipo de movimiento "reposición" con color azul distintivo
   - Filtros mejorados para búsqueda avanzada

4. **Sistema de Correos Mejorado**
   - Corrección del sistema de plantillas de correo
   - Variables soportadas: `{{nombre}}`, `{{usuario}}`, `{{email}}`, `{{password_temporal}}`
   - Variables adicionales: `{{cantidad}}`, `{{fecha}}`, `{{deuda}}`, `{{fecha_desde}}`
   - Interfaz simplificada para mostrar variables disponibles

5. **Mejoras de UI/UX**
   - Paginación corregida (usa clave `items_por_pagina`)
   - Botones de exportar PDF en todas las vistas de admin
   - Selector de elementos por página funcional

### Archivos Nuevos

| Archivo | Descripción |
|---------|-------------|
| `public/export.php` | Controlador de exportaciones PDF |
| `app/helpers/PdfHelper.php` | Generador de informes HTML para impresión |
| `app/helpers/EmailHelper.php` | Sistema de envío de correos con plantillas |
| `app/helpers/EnvHelper.php` | Lector de variables de entorno .env |

### Archivos Modificados

| Archivo | Cambios |
|---------|---------|
| `app/views/admin/usuarios.php` | Campo teléfono, botón exportar, corrección popup |
| `app/views/admin/productos.php` | Botón exportar PDF |
| `app/views/admin/facturas.php` | Botón exportar PDF |
| `app/views/admin/movimientos.php` | Botón exportar PDF, color reposición |
| `app/views/admin/graficos.php` | Botón exportar PDF con datos 30 días |
| `app/views/admin/correos.php` | Variables simplificadas |
| `database/seeds/001_initial_data.sql` | Plantillas de correo actualizadas |

### Correcciones de Bugs

- ✅ Error 404 en exportación PDF (archivo no existía)
- ✅ Variable de sesión incorrecta (`$_SESSION['rol']` → `$_SESSION['user_role']`)
- ✅ Clave de configuración incorrecta (`elementos_por_pagina` → `items_por_pagina`)
- ✅ Paginación no funcionaba correctamente
- ✅ Variables de plantilla de correo no se reemplazaban
- ✅ Bloqueo de popups al eliminar usuarios
- ✅ Alineación de columnas en informe de baja de usuario
- ✅ Deuda mostrada como positiva cuando debería ser negativa

---

## Versión 1.0.0 - Release Inicial

### Fase 1 - Inicio del Proyecto ✅

#### ✅ Tareas Completadas

1. **Configuración inicial del proyecto**
   - Creado archivo `copilot-instructions.md` con las directrices del proyecto
   - Creado archivo `README.md` con documentación principal
   - Creado archivo `version_info.txt` con versión 1.0.0
   - Creado archivo `Procesos.md` (este archivo)
   - Creado archivo `.gitignore`

2. **Estructura de carpetas**
   - Creada estructura completa de directorios
   - Separación clara entre app, public, database y docs

3. **Base de datos**
   - Diseñado esquema de base de datos (`database/migrations/001_create_tables.sql`)
   - Creadas tablas: usuarios, productos, movimientos, facturas, configuracion, plantillas_correo, sesiones
   - Script de datos iniciales (`database/seeds/001_initial_data.sql`)

---

### Fase 2 - Backend PHP ✅

4. **Configuración de la aplicación**
   - `app/config/database.php` - Conexión MySQL (puerto 3307)
   - `app/config/app.php` - Configuración general
   - `app/config/smtp.php` - Configuración de correo

5. **Modelos de datos**
   - `Database.php` - Singleton PDO para conexión
   - `Usuario.php` - Gestión de usuarios, autenticación, deudas
   - `Producto.php` - CRUD productos, stock, imágenes
   - `Movimiento.php` - Registro de operaciones, estadísticas para gráficos
   - `Factura.php` - Subida/descarga de PDFs
   - `Configuracion.php` - Parámetros del sistema, SMTP

6. **Sistema de autenticación**
   - `AuthController.php` - Login, logout, cambio de contraseña
   - Hash de contraseñas con `password_hash()`
   - Sistema de roles (admin/user)
   - Detección de cambio obligatorio de contraseña

7. **Helpers y funciones**
   - `functions.php` - Escape HTML, URLs, paginación, formateo, CSRF

8. **Router principal**
   - `public/index.php` - Enrutamiento de todas las peticiones

---

### Fase 3 - Frontend y Vistas ✅

9. **Estilos CSS**
   - `public/css/style.css` - 900+ líneas con diseño completo
   - Sistema de temas con CSS Variables (rojo admin, azul usuario)
   - Componentes: botones, cards, tablas, modales, alertas, paginación
   - Diseño responsive

10. **Vistas de autenticación**
    - `auth/login.php` - Formulario de login
    - `auth/change-password.php` - Cambio obligatorio de contraseña

11. **Componentes parciales**
    - `partials/header.php` - Cabecera con logo, usuario, ayuda
    - `partials/footer.php` - Pie de página con Chart.js
    - `partials/user-tabs.php` - Navegación usuario (3 pestañas)
    - `partials/admin-tabs.php` - Navegación admin (6 pestañas)
    - `partials/ayuda-usuario.php` - Modal de ayuda para usuarios
    - `partials/ayuda-admin.php` - Modal de ayuda para administradores

12. **Vistas de usuario (3 pestañas)**
    - `user/productos.php` - Ver productos, apuntar consumos
    - `user/movimientos.php` - Historial personal con filtros
    - `user/perfil.php` - Cambiar contraseña y email

13. **Vistas de administrador (6 pestañas + dashboard)**
    - `admin/dashboard.php` - Resumen, deudas, stock bajo, accesos rápidos
    - `admin/usuarios.php` - CRUD usuarios, resetear contraseña, registrar pagos
    - `admin/productos.php` - CRUD productos, reponer stock, imágenes
    - `admin/facturas.php` - Subir/descargar/eliminar PDFs
    - `admin/movimientos.php` - Historial completo con filtros avanzados
    - `admin/graficos.php` - Estadísticas con Chart.js (4 tipos de gráficos)
    - `admin/configuracion.php` - Contraseña, SMTP, ajustes generales

14. **JavaScript**
    - `public/js/app.js` - Modales, dropdowns, notificaciones, validaciones

15. **Páginas de error**
    - `errors/404.php` - Página de error 404 con estilo

16. **Documentación técnica**
    - `Project_Structure.html` - Estructura visual para desarrolladores

---

## ✅ Estado Actual: APLICACIÓN COMPLETA

### Funcionalidades Implementadas

| Módulo | Estado | Descripción |
|--------|--------|-------------|
| Autenticación | ✅ | Login, logout, cambio contraseña |
| Gestión Usuarios | ✅ | CRUD, reseteo contraseña, pagos |
| Gestión Productos | ✅ | CRUD, stock, imágenes |
| Gestión Facturas | ✅ | Upload/download PDFs |
| Movimientos | ✅ | Registro completo con filtros |
| Gráficos | ✅ | 4 tipos con Chart.js |
| Configuración | ✅ | SMTP, ajustes generales |
| Sistema de Ayuda | ✅ | Modales contextuales |
| Paginación | ✅ | En todas las tablas |

### Archivos Creados

**Total: 40+ archivos**

---

## 📝 Próximos Pasos (Opcionales)

- [x] Subir al repositorio GitHub
- [x] Configurar MySQL (puerto 3306 por defecto)
- [x] Ejecutar scripts de migración y seeds
- [ ] Configurar contraseña de aplicación de Google para SMTP
- [x] Crear logo FrigoTIC para `/public/images/`
- [ ] Pruebas de integración
- [ ] Despliegue en producción

---

## Notas de Desarrollo

### Convenciones Utilizadas
- PHP: PSR-4, PascalCase para clases, camelCase para métodos
- CSS: Variables para temas, enfoque mobile-first
- SQL: snake_case para tablas y columnas
- JS: ES6+, funciones descriptivas

### Decisiones de Arquitectura
- MVC simplificado para facilitar mantenimiento
- Separación de vistas por rol (admin/user)
- Archivos de configuración centralizados
- Configuración mediante archivo .env (no se sube a Git)

### Credenciales por Defecto
- **Admin**: usuario `admin`, contraseña `admin123`
- **Puerto MySQL**: 3306 (configurable en .env)
- **Base de datos**: `frigotic`

---

*Última actualización: Enero 2025*
*Desarrollado por MJCRSoftware*
