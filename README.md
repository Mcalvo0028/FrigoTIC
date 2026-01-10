# 🧊 FrigoTIC

**Versión:** 1.0.0  
**Desarrollado por:** MJCRSoftware

---

## 📋 Descripción

FrigoTIC es una aplicación web diseñada para gestionar un frigorífico compartido en el entorno laboral. Permite llevar un control eficiente de las bebidas y productos consumidos por los empleados, facilitando la gestión de pagos mensuales.

## 🎯 Características Principales

### Para Usuarios
- 📦 **Ver productos disponibles** con precios y stock
- ✏️ **Registrar consumos** de forma sencilla
- 📊 **Consultar historial** de movimientos personales
- 💰 **Ver deuda pendiente** y pagos realizados
- 👤 **Gestionar perfil** (contraseña y correo)

### Para Administradores
- 👥 **Gestión de usuarios** (crear, editar, resetear contraseñas)
- 🛒 **Gestión de productos** (CRUD completo con imágenes)
- 🧾 **Gestión de facturas** (subir, descargar, eliminar PDFs)
- 📋 **Ver todos los movimientos** con filtros avanzados
- 📈 **Gráficos estadísticos** personalizables
- ⚙️ **Configuración** del sistema (BD, SMTP, plantillas de correo)

## 🛠️ Tecnologías Utilizadas

| Tecnología | Uso |
|------------|-----|
| PHP 8.x | Backend |
| MySQL 8.x | Base de datos |
| HTML5/CSS3 | Frontend |
| JavaScript | Interactividad |
| Chart.js | Gráficos |
| Font Awesome | Iconos |

## 📁 Estructura del Proyecto

```
FrigoTIC/
├── app/                    # Código principal
│   ├── config/            # Configuración
│   ├── controllers/       # Controladores
│   ├── models/            # Modelos
│   ├── views/             # Vistas
│   └── helpers/           # Funciones auxiliares
├── public/                # Archivos públicos
│   ├── css/              # Estilos
│   ├── js/               # JavaScript
│   ├── images/           # Imágenes
│   └── uploads/          # Archivos subidos
├── database/              # Scripts SQL
├── docs/                  # Documentación
└── Script_Temp/          # Scripts de pruebas
```

## 🚀 Instalación

### Requisitos Previos
- Servidor Apache (XAMPP, WAMP, o similar)
- PHP 8.0 o superior
- MySQL 8.0 o superior

### Pasos de Instalación

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/Mcvo0028/FrigoTIC.git
   ```

2. **Configurar la base de datos**
   - Importar el archivo `database/frigotic.sql`
   - Configurar conexión en `app/config/database.php`

3. **Configurar el servidor web**
   - Apuntar el DocumentRoot a la carpeta `public/`
   - Asegurarse de que mod_rewrite esté habilitado

4. **Configurar permisos**
   - La carpeta `public/uploads/` debe tener permisos de escritura

5. **Acceder a la aplicación**
   - Abrir en el navegador: `http://localhost/frigotic`

## 👤 Credenciales por Defecto

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| admin | admin123 | Administrador |

> ⚠️ **Importante:** Cambiar la contraseña del administrador después del primer inicio de sesión.

## 🎨 Temas Visuales

- **Administrador:** Interfaz en tonos rojos
- **Usuario:** Interfaz en tonos azules

## 📧 Configuración SMTP

La aplicación permite enviar correos electrónicos para:
- Bienvenida a nuevos usuarios
- Recordatorios de pago
- Notificaciones personalizadas

Configurar en: `Configuración > SMTP`

## 📖 Documentación

- [Manual de Usuario](docs/manual_usuario.md)
- [Manual de Administrador](docs/manual_admin.md)
- [Estructura del Proyecto](Project_Structure.html)

## 🔄 Changelog

Ver archivo [Procesos.md](Procesos.md) para el historial de cambios.

## 📄 Licencia

Este proyecto es de uso interno para MJCRSoftware.

## 📞 Soporte

Para soporte técnico, contactar a: frigotic@gmail.com

---

**FrigoTIC** - Gestión inteligente de tu frigorífico compartido 🧊
