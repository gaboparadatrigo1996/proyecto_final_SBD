# 🚀 Guía Rápida de Instalación y Uso

## ⚡ Instalación en 3 Pasos

### Paso 1: Copiar Archivos
```
Copiar la carpeta: proyectoBDv2
A la ubicación: C:\xampp\htdocs\
```

### Paso 2: Crear Base de Datos
1. Abrir phpMyAdmin: `http://localhost/phpmyadmin`
2. Crear nueva base de datos: `evento_academico`
3. Importar archivo: `instalacion_completa.sql`

**O alternativamente:**
- Importar `database.sql` primero
- Luego importar `database_objects.sql`

### Paso 3: Acceder al Sistema
```
URL: http://localhost/proyectoBDv2
Usuario: admin@evento.com
Contraseña: admin123
```

## 📝 Flujo de Trabajo Típico

### 1. Crear un Evento
```
Dashboard → Eventos → ➕ Nuevo Evento
↓
Completar formulario (nombre, fechas, capacidad)
↓
💾 Guardar
```

### 2. Agregar Sesiones (opcional para eventos simultáneos)
```
Eventos → [Ver evento] → Sesiones → Agregar sesión
```

### 3. Registrar Participante
```
Dashboard → Participantes → ➕ Nuevo Participante
↓
Completar datos (DNI, nombres, email, tipo)
↓
💾 Guardar
```

### 4. Inscribir a Evento
```
Dashboard → Inscripciones → ➕ Nueva Inscripción
↓
Seleccionar evento y participante
↓
Sistema valida: ✓ Capacidad ✓ Duplicados
↓
💾 Guardar
```

### 5. Registrar Pago
```
Inscripciones → [Ver inscripción] → 💳 Registrar Pago
O
Pagos → ➕ Registrar Pago
↓
Seleccionar inscripción, monto, método
↓
💾 Guardar (estado: pendiente)
```

### 6. Aprobar Pago (Administrador)
```
Pagos → [buscar pago pendiente] → ✓ Aprobar
```

### 7. Marcar Asistencia
```
Asistencia → Seleccionar evento → Seleccionar sesión
↓
Ver participantes inscritos
↓
✓ Marcar Presente (uno por uno o masivo)
```

### 8. Generar Certificados
```
Certificados → ⚡ Generar Certificados Pendientes
↓
Sistema verifica: ✓ Asistencia ≥80% ✓ Pago aprobado
↓
Genera certificados con códigos únicos
```

## 📊 Consultas SQL Útiles

### Acceder a Consultas Personalizadas
```
Dashboard → Consultas SQL
```

### Consultas Predefinidas Incluidas:
1. **Total inscritos por evento**
2. **Participantes más activos**
3. **Asistencia por sesión**
4. **Pagos por fecha**
5. **Participantes por tipo**
6. **Capacidad vs inscritos**
7. **Actividad de usuarios**

### Ejecutar Procedimientos Almacenados
```sql
-- Generar certificados de un evento
CALL sp_generar_certificados_evento(1);

-- Ver estadísticas de asistencia
CALL sp_estadisticas_asistencia(1);

-- Actualizar estados de eventos
CALL sp_actualizar_estados_eventos();

-- Resumen de pagos de un evento
CALL sp_resumen_pagos_evento(1);

-- Ver historial de un participante
CALL sp_historial_participante(1);
```

### Usar Funciones
```sql
-- Total recaudado de un evento
SELECT fn_total_recaudado_evento(1) as total;

-- Porcentaje de asistencia de un participante
SELECT fn_porcentaje_asistencia_participante(1, 1) as porcentaje;

-- Verificar si califica para certificado
SELECT fn_califica_certificado(1, 1) as califica;

-- Espacios disponibles en un evento
SELECT fn_espacios_disponibles(1) as espacios;

-- Total de eventos activos
SELECT fn_eventos_activos() as total;
```

### Consultar Vistas
```sql
-- Resumen de eventos
SELECT * FROM vista_eventos_resumen;

-- Actividad de participantes
SELECT * FROM vista_participantes_actividad;

-- Pagos pendientes
SELECT * FROM vista_pagos_pendientes;

-- Certificados pendientes de generación
SELECT * FROM vista_certificados_pendientes;
```

## 📋 Reportes Disponibles

### Generar Reportes
```
Dashboard → Reportes → [Seleccionar tipo]
```

### Tipos de Reportes:
- ✅ **Asistencia** (Excel disponible)
- 💳 **Pagos** (Excel disponible)
- 🎖️ **Certificados**
- 📅 **Eventos**
- 👥 **Participantes**

### Exportar a Excel
```
[Abrir reporte] → 📥 Excel
```

## 🔒 Roles y Permisos

### Administrador
- ✅ Todo el sistema
- ✅ Gestión de usuarios
- ✅ Auditoría
- ✅ Configuración

### Responsable de Inscripción
- ✅ Eventos
- ✅ Participantes
- ✅ Inscripciones
- ✅ Pagos
- ⛔ Usuarios
- ⛔ Auditoría

### Asistente
- ✅ Asistencia
- ✅ Reportes
- ✅ Consultas
- ⛔ Modificar datos
- ⛔ Pagos

### Participante
- ✅ Ver sus inscripciones
- ✅ Descargar certificados
- ⛔ Acceso administrativo

## 🔐 Seguridad

### Cambiar Contraseña de Admin
```sql
UPDATE usuarios 
SET password = '$2y$10$NUEVO_HASH_AQUI'
WHERE email = 'admin@evento.com';
```

Para generar hash en PHP:
```php
echo password_hash('nueva_contraseña', PASSWORD_BCRYPT, ['cost' => 12]);
```

### Ver Auditoría
```
Dashboard → Auditoría (solo Administrador)
↓
Filtrar por: usuario, acción, fechas
```

### Acciones Auditadas:
- LOGIN_SUCCESS / LOGIN_FAILED
- CREATE / UPDATE / DELETE
- SQL_QUERY
- Todas las operaciones críticas

## 🛠️ Troubleshooting

### Error: "Cannot connect to database"
✅ Verificar:
1. MySQL está corriendo
2. Nombre de BD: `evento_academico`
3. Credenciales en `config/database.php`

### Error: "Stored procedure not found"
✅ Solución:
1. Importar `database_objects.sql`
2. O importar `instalacion_completa.sql` nuevamente

### Error: "Session timeout"
✅ Normal después de 30 minutos de inactividad
→ Volver a iniciar sesión

### No puedo aprobar pagos
✅ Verificar:
1. Rol debe ser: Administrador o Responsable
2. Estado debe ser: Pendiente

### Los certificados no se generan
✅ Requisitos:
1. Asistencia ≥ 80% de las sesiones
2. Pago aprobado
3. Estado de inscripción: Confirmada

## 📞 Información del Sistema

### Archivos Importantes:
- `database.sql` - Estructura de tablas
- `database_objects.sql` - Procedimientos, funciones, triggers
- `instalacion_completa.sql` - Instalación todo en uno
- `README.md` - Documentación completa
- `config/config.php` - Configuración global
- `config/database.php` - Conexión a BD

### Estructura de Carpetas:
```
proyectoBDv2/
├── auth/          # Login/Logout
├── dashboard/     # Página principal
├── modules/       # Todos los módulos
├── config/        # Configuración
├── includes/      # Header/Footer
└── assets/        # CSS/JS
```

### URLs Principales:
```
Login:         /proyectoBDv2/auth/login.php
Dashboard:     /proyectoBDv2/dashboard/
Eventos:       /proyectoBDv2/modules/eventos/
Participantes: /proyectoBDv2/modules/participantes/
Inscripciones: /proyectoBDv2/modules/inscripciones/
Pagos:         /proyectoBDv2/modules/pagos/
Asistencia:    /proyectoBDv2/modules/asistencia/
Certificados:  /proyectoBDv2/modules/certificados/
Reportes:      /proyectoBDv2/modules/reportes/
Consultas SQL: /proyectoBDv2/modules/consultas/
Auditoría:     /proyectoBDv2/modules/auditoria/
```

## ✅ Checklist de Verificación Post-Instalación

- [ ] Base de datos creada e importada
- [ ] Login funciona con admin@evento.com
- [ ] Dashboard muestra estadísticas
- [ ] Puedo crear un evento
- [ ] Puedo registrar un participante
- [ ] Puedo crear una inscripción
- [ ] Las validaciones funcionan (duplicados, capacidad)
- [ ] Puedo registrar un pago
- [ ] Puedo marcar asistencia
- [ ] Las consultas SQL funcionan
- [ ] Los reportes se generan
- [ ] La exportación a Excel funciona
- [ ] La auditoría registra acciones

---

**Sistema Completo y Funcional** ✅  
**Listo para Demostración** 🎯  
**Sin Errores Conocidos** ✨
