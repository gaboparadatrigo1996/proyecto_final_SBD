# Sistema de Gestión de Eventos Académicos

Sistema web completo para la administración de congresos, seminarios y jornadas universitarias, desarrollado en PHP, MySQL, HTML, CSS y JavaScript.

## 📋 Características Principales

### Módulos Implementados

1. **Autenticación y Seguridad**
   - Login con control de roles
   - Gestión de sesiones con timeout
   - Auditoría de accesos y acciones
   - Hash de contraseñas con bcrypt

2. **Gestión de Eventos**
   - Creación y edición de eventos
   - Configuración de sesiones múltiples
   - Control de capacidad
   - Estados: activo, cancelado, finalizado
   - Soporte para eventos simultáneos

3. **Participantes**
   - Registro con validación de duplicados
   - Perfiles detallados (institucional/personal)
   - Clasificación por tipo (estudiante, profesional, ponente, invitado)

4. **Inscripciones en Línea**
   - Formulario paso a paso
   - Estados: pendiente, confirmada, cancelada
   - Control de duplicados automático
   - Validación de capacidad

5. **Gestión de Pagos**
   - Múltiples métodos: efectivo, transferencia, QR, tarjeta
   - Estados: pendiente, aprobado, rechazado
   - Registro de comprobantes
   - Aprobación manual

6. **Control de Asistencia**
   - Registro en tiempo real por sesión
   - Interfaz para marcar presentes
   - Control por eventos simultáneos
   - Reportes inmediatos

7. **Certificados**
   - Generación automática
   - Códigos de validación únicos
   - Descarga digital
   - Plantillas configurables

8. **Reportes**
   - Asistencia por evento/sesión
   - Estado de pagos
   - Certificados emitidos
   - Exportación a PDF y Excel
   - Filtros dinámicos

9. **Consultas SQL Personalizadas**
   - Interfaz para ejecutar consultas SELECT
   - 7+ consultas predefinidas
   - Validación de seguridad
   - Exportación de resultados

10. **Módulo Administrativo**
    - Gestión de usuarios CRUD
    - Auditoría completa del sistema
    - Logs de acciones

## 🛠️ Tecnologías Utilizadas

- **Backend:** PHP 7.4+ (Programación Procedural y POO)
- **Base de Datos:** MySQL 5.7+ / MariaDB
- **Frontend:** HTML5, CSS3 (Grid, Flexbox, Gradients)
- **JavaScript:** Vanilla JS (ES6+)
- **Diseño:** Custom CSS con variables CSS, animaciones

## 📦 Instalación

### Requisitos Previos

- XAMPP, WAMP, LAMP o similar
- PHP 7.4 o superior
- MySQL 5.7 o MariaDB 10.3+
- Navegador web moderno

### Pasos de Instalación

1. **Copiar archivos al servidor**
   ```
   Copiar la carpeta proyectoBDv2 a: C:\xampp\htdocs\
   ```

2. **Crear la base de datos**
   - Abrir phpMyAdmin (http://localhost/phpmyadmin)
   - Crear una nueva base de datos llamada: `evento_academico`
   - Importar el archivo `database.sql` 
   - Importar el archivo `database_objects.sql`

3. **Configurar la conexión**
   - Editar `config/database.php`
   - Ajustar credenciales si es necesario:
     ```php
     private $host = 'localhost';
     private $dbname = 'evento_academico';
     private $username = 'root';
     private $password = '';
     ```

4. **Acceder al sistema**
   - URL: http://localhost/proyectoBDv2
   - Usuario: admin@evento.com
   - Contraseña: admin123

## 🗄️ Estructura de la Base de Datos

### Tablas Principales

- `roles` - Roles de usuario (Administrador, Asistente, etc.)
- `usuarios` - Usuarios del sistema
- `eventos` - Eventos académicos
- `sesiones` - Sesiones de cada evento (para simultaneidad)
- `participantes` - Personas que se inscriben
- `inscripciones` - Relación participante-evento
- `pagos` - Registro de pagos
- `asistencias` - Control de asistencia por sesión
- `certificados` - Certificados emitidos
- `auditoria` - Log de todas las acciones del sistema

### Objetos de Base de Datos

#### Stored Procedures (8)
1. `sp_registrar_participante_completo` - Registro completo con inscripción
2. `sp_generar_certificados_evento` - Generación masiva de certificados
3. `sp_actualizar_estados_eventos` - Actualización automática de estados
4. `sp_estadisticas_asistencia` - Estadísticas por evento
5. `sp_cancelar_inscripcion` - Cancelación con datos relacionados
6. `sp_resumen_pagos_evento` - Resumen financiero
7. `sp_historial_participante` - Historial completo
8. `sp_marcar_asistencia_masiva` - Marcar múltiples asistencias

#### Functions (8)
1. `fn_total_recaudado_evento` - Total recaudado por evento
2. `fn_porcentaje_asistencia_participante` - % de asistencia
3. `fn_califica_certificado` - Verifica si califica para certificado
4. `fn_siguiente_sesion_evento` - Próxima sesión
5. `fn_eventos_activos` - Contador de eventos activos
6. `fn_espacios_disponibles` - Cupos disponibles
7. `fn_total_eventos_participante` - Total de eventos por participante
8. `fn_dias_hasta_evento` - Días faltantes

#### Triggers (8)
1. `trg_validar_capacidad_inscripcion` - Valida capacidad antes de inscribir
2. `trg_actualizar_estado_evento` - Auto-actualiza estado por fecha
3. `trg_evitar_inscripcion_duplicada` - Previene duplicados
4. `trg_auditoria_usuarios_insert` - Auditoría en inserciones
5. `trg_auditoria_usuarios_update` - Auditoría en actualizaciones
6. `trg_validar_monto_pago` - Valida montos positivos
7. `trg_validar_asistencia_inscripcion` - Valida inscripción previa
8. `trg_actualizar_inscripcion_pago` - Actualiza estado al aprobar pago

#### Views (4)
1. `vista_eventos_resumen` - Resumen de eventos con estadísticas
2. `vista_participantes_actividad` - Participantes con su actividad
3. `vista_pagos_pendientes` - Pagos pendientes de aprobación
4. `vista_certificados_pendientes` - Certificados por generar

#### Cursors
- Implementados en procedimientos almacenados (sp_generar_certificados_evento, etc.)

## 🔐 Seguridad Implementada

1. **Autenticación**
   - Password hashing con bcrypt (cost 12)
   - Validación de sesiones con timeout (30 min)
   - Prevención de SQL injection con PDO prepared statements

2. **Autorización**
   - Control de acceso basado en roles
   - Menú contextual según permisos
   - Validación de permisos en cada acción

3. **Auditoría**
   - Log de todos los logins (exitosos y fallidos)
   - Registro de todas las acciones (CREATE, UPDATE, DELETE)
   - Registro de IP de origen
   - Timestamps automáticos

4. **Validación de Entrada**
   - Sanitización de todos los inputs
   - Validación de tipos de datos
   - Escape de HTML para prevenir XSS
   - Validación de consultas SQL (solo SELECT permitido)

5. **Protección de Datos**
   - Uso de prepared statements
   - Validación de duplicados
   - Transacciones para operaciones críticas
   - Constraints de integridad referencial

## 👥 Roles del Sistema

### Administrador
- Acceso total al sistema
- Gestión de usuarios
- Visualización de auditoría
- Configuración del sistema

### Responsable de Inscripción
- Gestión de eventos
- Registro de participantes
- Procesamiento de inscripciones
- Gestión de pagos

### Asistente
- Control de asistencia
- Consulta de eventos
- Generación de reportes

### Participante
- Visualización de eventos
- Consulta de inscripciones propias
- Descarga de certificados

## 📊 Reportes Disponibles

1. **Reporte de Asistencia**
   - Por evento o sesión específica
   - Filtros dinámicos
   - Exportación a Excel

2. **Estado de Pagos**
   - Por estado (pendiente, aprobado, rechazado)
   - Por método de pago
   - Totales recaudados

3. **Certificados Emitidos**
   - Lista completa
   - Por evento o participante
   - Códigos de validación

4. **Reporte de Eventos**
   - Estadísticas de inscripciones
   - Capacidad vs inscritos
   - Estados de eventos

5. **Dashboard General**
   - Métricas principales
   - Gráficos y estadísticas
   - Eventos recientes

## 🎨 Características de Diseño

- **Diseño Moderno:** Gradientes, glassmorphism, sombras suaves
- **Responsive:** Adaptable a móviles, tablets y desktop
- **Animaciones:** Transiciones suaves y micro-interacciones
- **Tipografía:** Google Fonts (Inter)
- **Colores:** Paleta profesional con variables CSS
- **Componentes:** Cards, badges, alerts, modals, tables

## 📁 Estructura del Proyecto

```
proyectoBDv2/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── auth/
│   ├── login.php
│   ├── login_process.php
│   └── logout.php
├── config/
│   ├── config.php
│   └── database.php
├── dashboard/
│   └── index.php
├── includes/
│   ├── header.php
│   └── footer.php
├── modules/
│   ├── asistencia/
│   ├── auditoria/
│   ├── certificados/
│   ├── consultas/
│   ├── eventos/
│   ├── inscripciones/
│   ├── pagos/
│   ├── participantes/
│   ├── reportes/
│   └── usuarios/
├── database.sql
├── database_objects.sql
├── index.php
└── README.md
```

## 🚀 Uso del Sistema

### Flujo Básico

1. **Crear un Evento**
   - Login como Administrador/Responsable
   - Ir a Eventos > Nuevo Evento
   - Completar formulario
   - Agregar sesiones al evento

2. **Registrar Participante**
   - Ir a Participantes > Nuevo
   - Ingresar datos personales
   - Guardar

3. **Inscribir a Evento**
   - Ir a Inscripciones > Nueva
   - Seleccionar evento y participante
   - El sistema valida capacidad y duplicados

4. **Registrar Pago**
   - Ir a Pagos > Registrar
   - Asociar a inscripción
   - Ingresar monto y método
   - Aprobar o rechazar

5. **Control de Asistencia**
   - Ir a Asistencia
   - Seleccionar evento y sesión
   - Marcar presentes uno por uno

6. **Generar Certificados**
   - Ir a Certificados
   - Ejecutar generación automática
   - Sistema verifica asistencia mínima
   - Genera códigos únicos

7. **Ver Reportes**
   - Ir a Reportes
   - Seleccionar tipo de reporte
   - Aplicar filtros
   - Exportar a Excel/PDF

## 🔧 Mantenimiento

### Actualizar Estados de Eventos
```sql
CALL sp_actualizar_estados_eventos();
```

### Generar Certificados de un Evento
```sql
CALL sp_generar_certificados_evento(1); -- ID del evento
```

### Ver Estadísticas de Asistencia
```sql
CALL sp_estadisticas_asistencia(1); -- ID del evento
```

## 📝 Notas Importantes

- Cambiar la contraseña del administrador en producción
- Configurar backup automático de la base de datos
- Revisar periódicamente los logs de auditoría
- Ajustar timeout de sesión según necesidades
- Personalizar plantillas de certificados

## 👨‍💻 Soporte

Para soporte técnico o consultas sobre el sistema:
- Revisar la documentación
- Consultar los logs de auditoría
- Verificar permisos de usuario

## 📄 Licencia

Sistema desarrollado para uso académico.

---

**Versión:** 1.0.0  
**Fecha:** 2025  
**Desarrollado con:** PHP + MySQL + HTML + CSS + JS
