# 🎓 Sistema de Gestión de Eventos Académicos

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

Sistema completo para la gestión de eventos académicos (congresos, seminarios, talleres) con control de inscripciones, pagos, asistencia y certificados.

---

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
- [Configuración](#️-configuración)
- [Uso del Sistema](#-uso-del-sistema)
- [Roles y Permisos](#-roles-y-permisos)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Base de Datos](#-base-de-datos)
- [Capturas de Pantalla](#-capturas-de-pantalla)
- [Soporte](#-soporte)

---

## ✨ Características

### 🔐 Autenticación y Seguridad
- ✅ Sistema de login con roles (Administrador, Responsable, Asistente, Participante)
- ✅ Contraseñas encriptadas con BCrypt
- ✅ Control de sesiones con timeout automático (30 minutos)
- ✅ Registro público para participantes
- ✅ Auditoría completa de acciones del sistema

### 📅 Gestión de Eventos
- ✅ CRUD completo de eventos académicos
- ✅ Control de capacidad y cupos disponibles
- ✅ Gestión de sesiones por evento
- ✅ Estados: Activo, Finalizado, Cancelado
- ✅ Fechas de inicio y fin
- ✅ Asignación de ubicación/lugar

### 👥 Gestión de Participantes
- ✅ Registro de participantes (estudiantes, profesionales, ponentes, invitados)
- ✅ Auto-registro público desde la web
- ✅ Control de datos personales y de contacto
- ✅ Vinculación con institución
- ✅ Historial de participación

### 📝 Inscripciones
- ✅ Pre-inscripción automática por participantes
- ✅ Flujo de aprobación por administradores
- ✅ Control de estados (Pendiente, Confirmada, Cancelada)
- ✅ Prevención de inscripciones duplicadas
- ✅ Validación de cupos disponibles
- ✅ Alertas de solicitudes pendientes

### 💳 Gestión de Pagos
- ✅ Registro de pagos por inscripción
- ✅ Múltiples métodos (Efectivo, Transferencia, QR, Tarjeta)
- ✅ Estados de pago (Pendiente, Aprobado, Rechazado)
- ✅ Confirmación automática de inscripciones al asignar pago
- ✅ Aprobación manual de pagos
- ✅ Upload de comprobantes

### ✅ Control de Asistencia
- ✅ Registro de asistencia por sesión
- ✅ Estados: Presente, Tardanza, Ausente
- ✅ Control de horarios de entrada
- ✅ Reportes de asistencia

### 🎖️ Certificados
- ✅ Generación de certificados digitales
- ✅ Código de validación único
- ✅ Almacenamiento de archivos PDF
- ✅ Vinculación con inscripciones

### 📊 Reportes Completos
- ✅ **Dashboard General**: Estadísticas y métricas generales
- ✅ **Reporte de Eventos**: Inscripciones y capacidad por evento
- ✅ **Reporte de Participantes**: Por tipo e institución
- ✅ **Reporte de Pagos**: Estados y montos
- ✅ **Reporte de Asistencia**: Por evento y sesión
- ✅ **Reporte de Certificados**: Certificados emitidos
- ✅ Exportación a Excel
- ✅ Filtros avanzados

### 🎨 Interfaz de Usuario
- ✅ Diseño moderno y responsivo
- ✅ Menú lateral contextual según rol
- ✅ Dashboard personalizado por tipo de usuario
- ✅ Notificaciones y alertas visuales
- ✅ Gradientes y animaciones
- ✅ Modo oscuro en sidebar

---

## 🛠️ Requisitos

### Software Necesario
- **PHP** >= 7.4
- **MySQL** >= 5.7 o MariaDB >= 10.2
- **Apache** o Nginx
- **Composer** (opcional)

### Extensiones PHP Requeridas
- `pdo_mysql`
- `mbstring`
- `openssl`
- `json`

### Recomendado
- **XAMPP** 8.0+ (incluye Apache, PHP y MySQL)
- **Git** para control de versiones

---

## 📥 Instalación

### Opción 1: Con XAMPP (Recomendado)

1. **Descargar e instalar XAMPP**
   ```
   https://www.apachefriends.org/
   ```

2. **Clonar el repositorio**
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/gaboparadatrigo1996/proyectoBD.git
   cd proyectoBD
   ```

3. **Crear la base de datos**
   - Abrir `http://localhost/phpmyadmin`
   - Crear nueva base de datos: `eventos_db`
   - Importar el archivo: `bd/database.sql`

4. **Configurar la conexión**
   
   Editar `config/database.php`:
   ```php
   private $host = "localhost";
   private $db_name = "eventos_db";
   private $username = "root";
   private $password = ""; // Tu contraseña de MySQL
   ```

5. **Configurar URL base**
   
   Editar `config/config.php`:
   ```php
   define('BASE_URL', 'http://localhost/proyectoBD');
   ```

6. **Iniciar Apache y MySQL**
   - Abrir XAMPP Control Panel
   - Start Apache
   - Start MySQL

7. **Acceder al sistema**
   ```
   http://localhost/proyectoBD/auth/login.php
   ```

### Opción 2: Servidor Linux

1. **Instalar dependencias**
   ```bash
   sudo apt update
   sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql php-mbstring
   ```

2. **Clonar repositorio**
   ```bash
   cd /var/www/html
   sudo git clone https://github.com/gaboparadatrigo1996/proyectoBD.git
   sudo chown -R www-data:www-data proyectoBD
   ```

3. **Configurar MySQL**
   ```bash
   sudo mysql -u root -p
   CREATE DATABASE eventos_db;
   CREATE USER 'eventos_user'@'localhost' IDENTIFIED BY 'tu_contraseña';
   GRANT ALL PRIVILEGES ON eventos_db.* TO 'eventos_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

4. **Importar base de datos**
   ```bash
   mysql -u eventos_user -p eventos_db < bd/database.sql
   ```

5. **Configurar permisos**
   ```bash
   sudo chmod -R 755 /var/www/html/proyectoBD
   sudo chown -R www-data:www-data /var/www/html/proyectoBD
   ```

6. **Reiniciar Apache**
   ```bash
   sudo systemctl restart apache2
   ```

---

## ⚙️ Configuración

### Configuración de Base de Datos

Archivo: `config/database.php`
```php
private $host = "localhost";      // Host del servidor MySQL
private $db_name = "eventos_db";  // Nombre de la base de datos
private $username = "root";       // Usuario de MySQL
private $password = "";           // Contraseña de MySQL
```

### Configuración General

Archivo: `config/config.php`
```php
// URL base del proyecto
define('BASE_URL', 'http://localhost/proyectoBD');

// Timeout de sesión (en segundos)
define('SESSION_TIMEOUT', 1800); // 30 minutos

// Tamaño máximo de archivos
define('MAX_FILE_SIZE', 5242880); // 5MB

// Zona horaria
date_default_timezone_set('America/La_Paz');
```

### Credenciales por Defecto

**Administrador:**
- Email: `admin@evento.com`
- Contraseña: `admin123`

**⚠️ IMPORTANTE**: Cambiar estas credenciales en producción.

---

## 🚀 Uso del Sistema

### 1️⃣ Como Participante

#### Registro
1. Ir a la página de login: `http://localhost/proyectoBD/auth/login.php`
2. Click en "Regístrate como Participante"
3. Completar formulario de registro:
   - Nombres y Apellidos
   - DNI/CI
   - Email
   - Teléfono (opcional)
   - Institución (opcional)
   - Tipo de participante
   - Contraseña
4. Confirmar registro

#### Pre-inscripción a Eventos
1. Login con tu cuenta
2. Verás tu portal "Mis Eventos"
3. En "Eventos Disponibles", busca el evento
4. Click en "📝 Pre-inscribirme"
5. Espera aprobación del administrador

#### Seguimiento
- Ve el estado de tus inscripciones
- Revisa estado de pagos
- Descarga certificados cuando estén disponibles

### 2️⃣ Como Administrador

#### Gestión de Solicitudes
1. Login como administrador
2. Ve a "Inscripciones"
3. Verás alerta de solicitudes pendientes
4. Click en "💳 Registrar pago" en cada solicitud
5. Asigna monto y método de pago
6. La inscripción se confirma automáticamente

#### Aprobación de Pagos
1. Ve a "Pagos"
2. Busca pagos con estado "Pendiente"
3. Verifica comprobantes
4. Edita y cambia estado a "Aprobado"

#### Generación de Certificados
1. Ve a "Certificados"
2. Click en "➕ Generar Certificado"
3. Selecciona inscripción
4. Sistema genera código único
5. Opcional: Upload de archivo PDF

#### Reportes
1. Ve a "Reportes"
2. Selecciona tipo de reporte:
   - Dashboard General
   - Eventos
   - Participantes
   - Pagos
   - Asistencia
   - Certificados
3. Aplica filtros
4. Exporta a Excel si necesitas

### 3️⃣ Como Responsable de Inscripción

- Gestión completa de participantes
- Gestión completa de inscripciones
- Registro y aprobación de pagos
- Generación de certificados
- Acceso a reportes
- **Sin acceso** a usuarios y auditoría

### 4️⃣ Como Asistente

- Apoyo en control de asistencia
- Gestión de participantes
- Registros de asistencia por sesión
- Acceso a reportes
- **Sin acceso** a usuarios y auditoría

---

## 👥 Roles y Permisos

### 🔴 Administrador (ID: 1)
**Descripción**: Acceso total al sistema

| Módulo | Permisos |
|--------|----------|
| Eventos | ✅ Crear, Ver, Editar, Eliminar |
| Participantes | ✅ Crear, Ver, Editar, Eliminar |
| Inscripciones | ✅ Crear, Ver, Editar, Eliminar, Aprobar |
| Pagos | ✅ Crear, Ver, Editar, Aprobar, Rechazar |
| Asistencia | ✅ Crear, Ver, Editar |
| Certificados | ✅ Crear, Ver, Generar |
| Reportes | ✅ Acceso completo |
| **Usuarios** | ✅ Gestión completa (exclusivo) |
| **Auditoría** | ✅ Ver logs (exclusivo) |

### 🟡 Responsable de Inscripción (ID: 2)
**Descripción**: Gestiona inscripciones y pagos

| Módulo | Permisos |
|--------|----------|
| Eventos | ✅ Ver |
| Participantes | ✅ Crear, Ver, Editar |
| Inscripciones | ✅ Crear, Ver, Editar, Aprobar |
| Pagos | ✅ Crear, Ver, Editar, Aprobar |
| Asistencia | ✅ Ver |
| Certificados | ✅ Generar |
| Reportes | ✅ Acceso completo |
| Usuarios | ❌ Sin acceso |
| Auditoría | ❌ Sin acceso |

### 🟢 Asistente (ID: 3)
**Descripción**: Apoyo en eventos y asistencia

| Módulo | Permisos |
|--------|----------|
| Eventos | ✅ Ver |
| Participantes | ✅ Ver |
| Inscripciones | ✅ Ver |
| Pagos | ✅ Ver |
| Asistencia | ✅ Crear, Ver, Editar |
| Certificados | ✅ Ver |
| Reportes | ✅ Acceso a reportes |
| Usuarios | ❌ Sin acceso |
| Auditoría | ❌ Sin acceso |

### 🔵 Participante (ID: 4)
**Descripción**: Usuario externo que se inscribe

| Módulo | Permisos |
|--------|----------|
| Portal Personal | ✅ Ver mis eventos |
| Pre-inscripción | ✅ Inscribirse a eventos |
| Mis Inscripciones | ✅ Ver estado |
| Mis Pagos | ✅ Ver estado |
| Mis Certificados | ✅ Descargar |
| Gestión | ❌ Sin acceso |

---

## 📁 Estructura del Proyecto

```
proyectoBD/
├── 📂 assets/
│   ├── css/
│   │   └── style.css          # Estilos principales
│   └── js/
│       └── main.js            # Scripts JavaScript
│
├── 📂 auth/
│   ├── login.php              # Página de login
│   ├── login_process.php      # Procesar login
│   └── logout.php             # Cerrar sesión
│
├── 📂 bd/
│   └── database.sql           # Script de base de datos
│
├── 📂 config/
│   ├── config.php             # Configuración general
│   └── database.php           # Clase de conexión a BD
│
├── 📂 dashboard/
│   └── index.php              # Dashboard principal
│
├── 📂 includes/
│   ├── header.php             # Header con menú lateral
│   └── footer.php             # Footer
│
├── 📂 modules/
│   ├── 📂 asistencia/         # Control de asistencia
│   ├── 📂 auditoria/          # Logs del sistema
│   ├── 📂 certificados/       # Gestión de certificados
│   ├── 📂 consultas/          # Consultas SQL
│   ├── 📂 eventos/            # CRUD de eventos
│   ├── 📂 inscripciones/      # Gestión de inscripciones
│   ├── 📂 pagos/              # Gestión de pagos
│   ├── 📂 participante/       # Portal del participante
│   ├── 📂 participantes/      # CRUD de participantes
│   ├── 📂 reportes/           # Sistema de reportes
│   └── 📂 usuarios/           # Gestión de usuarios
│
├── 📂 public/
│   ├── registro.php           # Registro público
│   └── registro_process.php   # Procesar registro
│
├── .gitignore
└── README.md                   # Este archivo
```

---

## 🗄️ Base de Datos

### Diagrama Entidad-Relación

```
┌─────────────┐          ┌─────────────┐
│   roles     │──────┬───│  usuarios   │
└─────────────┘      │   └─────────────┘
                     │           │
                     │           │ creado_por
                     │           ↓
┌─────────────┐      │   ┌─────────────┐      ┌─────────────┐
│participantes│      │   │   eventos   │──────│  sesiones   │
└─────────────┘      │   └─────────────┘      └─────────────┘
       │             │           │                     │
       │             │           │                     │
       │             │   ┌───────┴───────┐            │
       │             │   │               │            │
       └─────────────┼───│inscripciones  │            │
                     │   └───────┬───────┘            │
                     │           │                     │
                     │   ┌───────┴────────┐           │
                     │   │                │           │
                     │   ↓                ↓           ↓
                     │┌────────┐   ┌───────────┐ ┌──────────┐
                     ││ pagos  │   │certificados│ │asistencias│
                     │└────────┘   └───────────┘ └──────────┘
                     │
                     ↓
              ┌──────────┐
              │auditoria │
              └──────────┘
```

### Tablas Principales

#### `roles`
- `id_rol` (PK)
- `nombre_rol`
- `descripcion`

#### `usuarios`
- `id_usuario` (PK)
- `nombre_completo`
- `email` (UNIQUE)
- `password` (hashed)
- `id_rol` (FK → roles)
- `estado` (activo/inactivo)
- `fecha_creacion`

#### `eventos`
- `id_evento` (PK)
- `nombre_evento`
- `descripcion`
- `fecha_inicio`
- `fecha_fin`
- `lugar`
- `capacidad_maxima`
- `estado` (activo/cancelado/finalizado)
- `creado_por` (FK → usuarios)

#### `participantes`
- `id_participante` (PK)
- `dni` (UNIQUE)
- `nombres`
- `apellidos`
- `email` (UNIQUE)
- `telefono`
- `institucion`
- `tipo_participante` (estudiante/profesional/ponente/invitado)

#### `inscripciones`
- `id_inscripcion` (PK)
- `id_evento` (FK → eventos)
- `id_participante` (FK → participantes)
- `fecha_inscripcion`
- `estado_inscripcion` (pendiente/confirmada/cancelada)
- UNIQUE (`id_evento`, `id_participante`)

#### `pagos`
- `id_pago` (PK)
- `id_inscripcion` (FK → inscripciones)
- `monto`
- `fecha_pago`
- `metodo_pago` (efectivo/transferencia/qr/tarjeta)
- `comprobante_url`
- `estado_pago` (pendiente/aprobado/rechazado)
- `registrado_por` (FK → usuarios)

#### `sesiones`
- `id_sesion` (PK)
- `id_evento` (FK → eventos)
- `nombre_sesion`
- `fecha`
- `hora_inicio`
- `hora_fin`
- `lugar_sesion`
- `capacidad`

#### `asistencias`
- `id_asistencia` (PK)
- `id_sesion` (FK → sesiones)
- `id_participante` (FK → participantes)
- `fecha_hora_entrada`
- `estado` (presente/tardanza/ausente)
- UNIQUE (`id_sesion`, `id_participante`)

#### `certificados`
- `id_certificado` (PK)
- `id_inscripcion` (FK → inscripciones, UNIQUE)
- `codigo_validacion` (UNIQUE)
- `fecha_emision`
- `archivo_url`

#### `auditoria`
- `id_auditoria` (PK)
- `id_usuario` (FK → usuarios)
- `accion`
- `tabla_afectada`
- `id_registro_afectado`
- `detalles`
- `fecha_hora`
- `ip_origen`

---

## 📸 Capturas de Pantalla

### Login
Página de inicio de sesión con diseño moderno y gradiente animado.

### Dashboard Administrador
Vista general con estadísticas, eventos recientes y acciones rápidas.

### Portal del Participante
Vista personalizada con eventos disponibles y estado de inscripciones.

### Gestión de Inscripciones
Lista de inscripciones con alertas de solicitudes pendientes.

### Reportes
Dashboard de estadísticas generales con gráficos y métricas.

---

## 🔄 Flujo de Pre-inscripción

```
PARTICIPANTE
     │
     ├─► 1. Registro público (public/registro.php)
     │        └─► Crea cuenta con rol "Participante"
     │
     ├─► 2. Login (auth/login.php)
     │        └─► Redirige a portal personal
     │
     ├─► 3. Ve eventos disponibles
     │        └─► Muestra eventos activos con cupos
     │
     ├─► 4. Pre-inscripción a evento
     │        └─► Estado: PENDIENTE ⚠️
     │
     └─► 5. Espera aprobación
              └─► Ve estado en su portal

ADMINISTRADOR
     │
     ├─► 1. Ve alerta de solicitudes pendientes
     │
     ├─► 2. Revisa pre-inscripción
     │        └─► Módulo: Inscripciones
     │
     ├─► 3. Asigna pago
     │        └─► Monto + Método
     │        └─► Inscripción → CONFIRMADA ✅
     │
     ├─► 4. Aprueba pago
     │        └─► Módulo: Pagos
     │        └─► Estado pago → APROBADO ✅
     │
     └─► 5. Genera certificado (opcional)
              └─► Módulo: Certificados

RESULTADO
     └─► Participante confirmado e inscrito ✅
```

---

## 🔒 Seguridad

### Implementadas
- ✅ Contraseñas hasheadas con BCrypt (cost: 12)
- ✅ Sesiones con timeout automático (30 min)
- ✅ Sanitización de inputs (htmlspecialchars)
- ✅ Prepared statements (prevención SQL injection)
- ✅ Control de acceso basado en roles
- ✅ Auditoría completa de acciones
- ✅ Validación de datos en cliente y servidor
- ✅ Protección contra registros duplicados

### Recomendaciones para Producción
- 🔐 Cambiar credenciales por defecto
- 🔐 Configurar HTTPS
- 🔐 Implementar CSRF tokens
- 🔐 Rate limiting en login
- 🔐 Backup automático de BD
- 🔐 Configurar firewall
- 🔐 Deshabilitar `display_errors` en PHP

---

## 🐛 Solución de Problemas

### Error de conexión a base de datos
```
**Síntoma**: "Connection failed" o "Access denied"

**Solución**:
1. Verificar que MySQL esté corriendo
2. Revisar credenciales en config/database.php
3. Verificar que la base de datos exista
4. Verificar permisos del usuario MySQL
```

### No aparecen estilos
```
**Síntoma**: Página sin formato

**Solución**:
1. Verificar que BASE_URL esté correctamente configurado
2. Verificar que Apache tenga mod_rewrite habilitado
3. Verificar permisos de lectura en carpeta assets/
```

### Sesión expira inmediatamente
```
**Síntoma**: Logout automático al navegar

**Solución**:
1. Verificar configuración de sesiones en php.ini
2. Ajustar SESSION_TIMEOUT en config.php
3. Verificar permisos en carpeta de sesiones
```

### No se pueden subir archivos
```
**Síntoma**: Error al subir comprobantes

**Solución**:
1. Ajustar upload_max_filesize en php.ini
2. Ajustar post_max_size en php.ini
3. Verificar permisos de escritura en uploads/
4. Verificar MAX_FILE_SIZE en config.php
```

---

## 🤝 Contribuir

Las contribuciones son bienvenidas. Para cambios importantes:

1. Fork del repositorio
2. Crear rama de feature (`git checkout -b feature/NuevaCaracteristica`)
3. Commit de cambios (`git commit -am 'Agregar nueva característica'`)
4. Push a la rama (`git push origin feature/NuevaCaracteristica`)
5. Crear Pull Request

---

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver archivo `LICENSE` para más detalles.

---

## 👨‍💻 Autor

**Gabriel Parada Trigo**
- GitHub: [@gaboparadatrigo1996](https://github.com/gaboparadatrigo1996)
- Repositorio: [proyectoBD](https://github.com/gaboparadatrigo1996/proyectoBD)

---

## 🙏 Agradecimientos

- Proyecto desarrollado para gestión de eventos académicos
- Diseño inspirado en sistemas modernos de gestión
- Iconos: Emojis nativos

---

## 📞 Soporte

Para reportar bugs o solicitar características:
- Abrir un [Issue](https://github.com/gaboparadatrigo1996/proyectoBD/issues) en GitHub

---

## 📊 Estadísticas del Proyecto

- **Lenguaje Principal**: PHP
- **Base de Datos**: MySQL
- **Archivos PHP**: 100+
- **Tablas BD**: 9
- **Roles de Usuario**: 4
- **Módulos**: 10
- **Reportes**: 6

---

## 🚦 Versiones

### v1.0.0 (Actual)
- ✅ Sistema completo de gestión de eventos
- ✅ Auto-registro de participantes
- ✅ Flujo de pre-inscripción
- ✅ Sistema de roles y permisos
- ✅ Reportes completos con exportación
- ✅ Control de asistencia
- ✅ Generación de certificados

---

**Desarrollado con ❤️ para la comunidad académica**
