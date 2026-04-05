# 📋 ReservaPro - Sistema de Reservas de Servicios

Sistema completo de gestión de citas y reservas optimizado para que los clientes reserven en menos de 60 segundos.

## 🚀 Características Principales

### Para Clientes:
- ✅ Flujo de reserva en 4 pasos simples
- 📱 Diseño responsive optimizado para móviles
- ⚡ Reserva en menos de 60 segundos
- 📧 Confirmaciones automáticas por correo
- 🔍 Consulta y gestión de citas
- ❌ Cancelación y reagendamiento fácil

### Para Administradores:
- 📊 Dashboard con métricas en tiempo real
- 📅 Calendario con vistas día/semana/mes
- 👥 CRM básico de clientes
- 👤 Gestión de empleados y horarios
- ✂️ Administración de servicios
- 📈 Reportes detallados
- ⚙️ Configuración personalizable

## 📦 Instalación

### Requisitos:
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Apache/Nginx con mod_rewrite
- XAMPP, WAMP, MAMP o similar

### Pasos de Instalación:

1. **Clonar o descargar el proyecto** en tu carpeta htdocs:
   ```
   e:\xampp\htdocs\reservapro\
   ```

2. **Crear la base de datos**:
   - Abre phpMyAdmin (http://localhost/phpmyadmin)
   - Importa el archivo `database.sql`
   - O ejecuta el archivo SQL completo

3. **Configurar la conexión a la base de datos**:
   - Abre `config/database.php`
   - Ajusta las credenciales si es necesario:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'reservapro');
     ```

4. **Iniciar XAMPP**:
   - Activa Apache y MySQL
   - Accede a: http://localhost/reservapro

5. **Acceder al panel de administración**:
   - URL: http://localhost/reservapro/admin/admin-login.php
   - Usuario: `admin`
   - Contraseña: `admin123`

## 📁 Estructura del Proyecto

```
reservapro/
├── admin/                      # Panel de administración
│   ├── includes/              # Componentes reutilizables
│   │   ├── sidebar.php       # Menú lateral
│   │   └── topbar.php        # Barra superior
│   ├── admin-login.php       # Login del admin
│   ├── auth.php              # Autenticación
│   ├── index.php             # Dashboard
│   ├── calendario.php        # Gestión del calendario
│   ├── clientes.php          # CRM de clientes
│   ├── empleados.php         # Gestión de empleados
│   ├── servicios.php         # Gestión de servicios
│   ├── reportes.php          # Reportes e informes
│   └── configuracion.php     # Configuración general
├── api/                       # APIs REST
│   ├── get-employees.php     # Obtener empleados
│   ├── get-available-dates.php # Fechas disponibles
│   ├── get-available-times.php # Horarios disponibles
│   ├── create-booking.php    # Crear reserva
│   └── cancel-booking.php    # Cancelar reserva
├── assets/                    # Recursos estáticos
│   ├── css/
│   │   ├── styles.css        # Estilos globales
│   │   ├── reserva.css       # Estilos de reserva
│   │   ├── mis-citas.css     # Estilos de mis citas
│   │   └── admin.css         # Estilos del admin
│   ├── js/
│   │   ├── calendar.js       # Componente calendario
│   │   ├── reserva.js        # Lógica de reserva
│   │   ├── mis-citas.js      # Gestión de citas
│   │   └── admin.js          # Funciones admin
│   └── img/                  # Imágenes
├── config/
│   └── database.php          # Configuración y conexión DB
├── database.sql              # Estructura de la BD
├── index.php                 # Página de reserva (pública)
├── mis-citas.php            # Consulta de citas (pública)
└── README.md                # Este archivo
```

## 🎨 Personalización

### Cambiar Colores del Negocio:
Edita las variables CSS en `assets/css/styles.css`:
```css
:root {
    --primary-color: #6366f1;
    --secondary-color: #8b5cf6;
    --success-color: #10b981;
    /* ... más colores */
}
```

### Configurar Datos del Negocio:
1. Accede al panel de administración
2. Ve a Configuración
3. Actualiza nombre, dirección, teléfono, etc.
4. Sube tu logo

### Agregar Servicios:
1. Panel Admin → Servicios
2. Crear nuevo servicio
3. Define nombre, duración, precio e ícono
4. Asigna empleados que pueden realizarlo

### Configurar Empleados:
1. Panel Admin → Empleados
2. Agregar nuevo empleado
3. Define horarios de trabajo
4. Asigna servicios que puede realizar

## 📊 Base de Datos

### Tablas Principales:
- **configuracion**: Datos del negocio
- **empleados**: Personal del negocio
- **servicios**: Servicios ofrecidos
- **clientes**: Base de datos de clientes
- **citas**: Reservas realizadas
- **horarios_empleados**: Turnos de trabajo
- **bloqueos_horario**: Días libres/vacaciones
- **usuarios**: Usuarios del panel admin

## 🔐 Seguridad

**IMPORTANTE**: Antes de usar en producción:

1. **Cambiar contraseñas**:
   - Cambiar la contraseña del usuario admin
   - Actualizar credenciales de la base de datos

2. **Actualizar password hash**:
   ```php
   $nueva_password = password_hash('tu_password_seguro', PASSWORD_DEFAULT);
   ```

3. **Configurar HTTPS**:
   - Usar certificado SSL
   - Forzar conexiones seguras

4. **Proteger directorios**:
   - Restringir acceso a `/admin/`
   - Configurar permisos de archivos

## 📧 Notificaciones (Próximamente)

Para habilitar notificaciones automáticas:
- Configurar SMTP para correos
- Integrar API de WhatsApp/SMS
- Configurar recordatorios automáticos

## 🐛 Solución de Problemas

### Error de conexión a BD:
```
Error: SQLSTATE[HY000] [1045] Access denied
```
**Solución**: Verifica las credenciales en `config/database.php`

### La página se muestra en blanco:
**Solución**: 
- Activa la visualización de errores en PHP
- Revisa el log de Apache
- Verifica que todas las extensiones PHP estén activas

### Las imágenes no cargan:
**Solución**:
- Verifica permisos de la carpeta `assets/img/`
- Asegúrate de que las rutas sean correctas

## 🔄 Actualizaciones Futuras

- [ ] Integración con WhatsApp Business API
- [ ] Envío automático de recordatorios
- [ ] Pagos en línea
- [ ] App móvil nativa
- [ ] Integración con Google Calendar
- [ ] Sistema de reseñas
- [ ] Programa de fidelización

## 📝 Licencia

Este proyecto es de código abierto y está disponible para uso personal y comercial.

## 💡 Soporte

Para soporte técnico o consultas:
- Revisa la documentación
- Reporta bugs en GitHub Issues
- Contacta al desarrollador

---

**Desarrollado con ❤️ para facilitar la gestión de citas y mejorar la experiencia del cliente**

## 🎯 Objetivos Cumplidos

✅ Reserva en menos de 60 segundos  
✅ Diseño mobile-first  
✅ Panel de administración completo  
✅ Sistema de reportes  
✅ CRM básico integrado  
✅ Gestión de horarios flexible  
✅ Código limpio y mantenible  

**¡Disfruta de ReservaPro!** 🚀
