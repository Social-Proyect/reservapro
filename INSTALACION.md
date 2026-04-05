# 🚀 INSTALACIÓN RÁPIDA - ReservaPro

## Pasos para poner en funcionamiento en 5 minutos

### 1️⃣ Preparar XAMPP
```
✓ Iniciar Apache
✓ Iniciar MySQL
```

### 2️⃣ Crear la Base de Datos
1. Abre tu navegador y ve a: http://localhost/phpmyadmin
2. Haz clic en "Nuevo" en el panel izquierdo
3. Nombre de la base de datos: `reservapro`
4. Cotejamiento: `utf8mb4_unicode_ci`
5. Clic en "Crear"
6. Selecciona la base de datos recién creada
7. Clic en la pestaña "Importar"
8. Selecciona el archivo `database.sql` de este proyecto
9. Clic en "Continuar"

### 3️⃣ Verificar Configuración
Abre el archivo: `config/database.php`

Asegúrate de que las credenciales sean correctas:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Por defecto en XAMPP está vacío
define('DB_NAME', 'reservapro');
```

### 4️⃣ Acceder al Sistema

**Sitio Público (Clientes):**
```
http://localhost/reservapro/
```

**Panel de Administración:**
```
http://localhost/reservapro/admin/admin-login.php

Usuario: admin
Contraseña: admin123
```

## ✅ Verificación

Después de la instalación, verifica:

- [ ] La página principal carga correctamente
- [ ] Se muestran los servicios de ejemplo
- [ ] Puedes hacer clic en un servicio
- [ ] El panel de admin carga sin errores
- [ ] Puedes iniciar sesión en el admin

## 🎨 Primeros Pasos

### Configurar tu Negocio:
1. Inicia sesión en el admin
2. Ve a **Configuración**
3. Actualiza:
   - Nombre del negocio
   - Dirección y teléfono
   - Horarios
   - Colores (opcional)
   - Sube tu logo

### Agregar tus Servicios:
1. En el admin, ve a **Servicios**
2. Edita los servicios de ejemplo o crea nuevos
3. Define: nombre, duración, precio, ícono

### Configurar Empleados:
1. Ve a **Empleados**
2. Edita los empleados de ejemplo
3. Define sus horarios de trabajo
4. Asigna qué servicios puede realizar cada uno

### Probar una Reserva:
1. Abre el sitio público
2. Haz una reserva de prueba
3. Ve a **Mis Citas** y busca con el código
4. En el admin, verifica que aparezca en el Dashboard

## 🐛 Solución de Problemas Comunes

### Error: "Access denied for user"
**Causa**: Credenciales de MySQL incorrectas  
**Solución**: Verifica usuario y contraseña en `config/database.php`

### Error: "Table doesn't exist"
**Causa**: Base de datos no importada correctamente  
**Solución**: Reimporta el archivo `database.sql` en phpMyAdmin

### Página en blanco
**Causa**: Error de PHP no mostrado  
**Solución**: 
1. Abre `php.ini` en XAMPP
2. Busca `display_errors`
3. Cambia a `display_errors = On`
4. Reinicia Apache

### Los estilos CSS no cargan
**Causa**: Rutas incorrectas  
**Solución**: Verifica que el proyecto esté en `htdocs/reservapro/`

## 📞 Datos de Contacto de Prueba

El sistema viene con datos de ejemplo:

**Servicios:**
- Corte de Cabello (45 min - $250)
- Tinte Completo (120 min - $800)
- Manicure (30 min - $150)
- Pedicure (45 min - $200)
- Barba y Bigote (30 min - $150)

**Empleados:**
- Juan Pérez - Especialista en cortes
- María González - Especialista en uñas
- Carlos Rodríguez - Maestro barbero

## 🎯 Siguiente Nivel

Una vez que el sistema esté funcionando:

1. **Personaliza los colores** en `assets/css/styles.css`
2. **Agrega tu logo** en Configuración
3. **Configura notificaciones** por correo (requiere SMTP)
4. **Prueba el flujo completo** de reserva
5. **Capacita a tu personal** en el uso del panel admin

## 🔒 Seguridad IMPORTANTE

Antes de usar en producción:

```sql
-- Cambiar contraseña del admin
UPDATE usuarios 
SET password = '$2y$10$TU_NUEVO_HASH_AQUI' 
WHERE username = 'admin';
```

Para generar el hash, usa:
```php
<?php
echo password_hash('tu_nueva_contraseña', PASSWORD_DEFAULT);
?>
```

## 📚 Documentación Completa

Para más información, consulta el archivo `README.md`

---

**¡Listo! Tu sistema de reservas está funcionando** 🎉

¿Necesitas ayuda? Revisa la documentación completa o contacta al soporte.
