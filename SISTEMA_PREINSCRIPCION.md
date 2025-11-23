# 🎯 Sistema de Auto-Registro y Pre-Inscripción de Participantes

## 📋 Descripción General

El sistema ahora permite que los **participantes se registren públicamente** y realicen **pre-inscripciones** a eventos sin necesidad de que un administrador los cree manualmente. Cuenta con un flujo de aprobación donde los responsables revisan, aprueban y asignan pagos a las solicitudes.

---

## 🔄 Flujo Completo del Proceso

### 1️⃣ **Registro Público del Participante**

**Página**: `/public/registro.php`

#### Características:
- ✅ Formulario público (sin autenticación requerida)
- ✅ Diseño moderno con validaciones
- ✅ Crea registro en tabla `participantes` y `usuarios`
- ✅ Asigna automáticamente rol "Participante" (ID: 4)
- ✅ Password hasheado con BCrypt

#### Datos Requeridos:
- Nombres *
- Apellidos *
- DNI/CI *
- Email *
- Teléfono (opcional)
- Institución (opcional)
- Tipo de Participante * (estudiante, profesional, ponente, invitado)
- Contraseña * (mínimo 6 caracteres)
- Confirmar Contraseña *

#### Validaciones:
- ✅ Email único (no puede repetirse)
- ✅ DNI único (no puede repetirse)
- ✅ Contraseñas deben coincidir
- ✅ Longitud mínima de contraseña

#### Proceso:
```
Usuario accede → Completa formulario → Submit
     ↓
Validaciones en front-end (JS)
     ↓
POST a registro_process.php
     ↓
Validaciones en back-end
     ↓
Transacción de Base de Datos:
  1. Inserta en participantes
  2. Inserta en usuarios (rol = 4)
     ↓
Auditoría registrada
     ↓
Redirección a login con mensaje de éxito
```

---

### 2️⃣ **Login del Participante**

**Página**: `/auth/login.php`

#### Acceso:
- Email: el registrado
- Contraseña: la definida en el registro

#### Características Mejoradas:
- ✅ Link de registro visible: "¿No tienes una cuenta? Regístrate como Participante"
- ✅ Redirección automática según rol:
  - **Participante** → `/modules/participante/mis_eventos.php`
  - **Admin/Responsable/Asistente** → `/dashboard/index.php`

---

### 3️⃣ **Portal del Participante**

**Página**: `/modules/participante/mis_eventos.php`

#### Vistas:

##### **Estadísticas Personales (Cards)**
1. Total de mis inscripciones
2. Inscripciones confirmadas
3. Inscripciones pendientes
4. Eventos disponibles

##### **Sección: Mis Inscripciones**

Muestra todas las pre-inscripciones del participante con:
- 📅 Nombre del evento
- 🗓️ Fecha del evento
- 📍 Lugar
- 📝 Fecha de inscripción
- **Estado de Inscripción**: 
  - 🟡 Pendiente (esperando aprobación)
  - 🟢 Confirmada (aprobada)
  - 🔴 Cancelada
- **Estado de Pago**:
  - ⏳ Sin pago asignado
  - 🟡 Pendiente de pago
  - 🟢 Aprobado
  - 🔴 Rechazado
- 🎖️ Certificado (si ya fue emitido)

##### **Sección: Eventos Disponibles**

Muestra todos los eventos activos con cupos disponibles:
- Información del evento
- Cupos restantes
- Botón "📝 Pre-inscribirme"
- Marca "Ya inscrito" si ya realizó pre-inscripción

---

### 4️⃣ **Pre-Inscripción a un Evento**

**Proceso**: `/modules/participante/pre_inscribir.php`

#### Flujo:
```
Participante ve evento disponible
     ↓
Click en "Pre-inscribirme"
     ↓
POST con id_evento e id_participante
     ↓
Validaciones:
  - Evento existe y está activo
  - Hay cupos disponibles
  - No está ya inscrito
  - Participante es válido
     ↓
Crea registro en inscripciones:
  - estado_inscripcion = 'pendiente'
  - fecha_inscripcion = NOW()
     ↓
Auditoría registrada
     ↓
Mensaje: "¡Pre-inscripción exitosa! 
         Un administrador revisará tu solicitud"
```

#### Estado Inicial:
- **Estado Inscripción**: `pendiente`
- **Estado Pago**: Sin registro de pago aún

---

### 5️⃣ **Gestión de Solicitudes (Admin/Responsable)**

**Página**: `/modules/inscripciones/index.php`

#### Vista Mejorada:

##### **Alerta de Solicitudes Pendientes**

Cuando hay inscripciones pendientes, se muestra un card destacado:

```
⚠️ Solicitudes Pendientes de Aprobación  [X solicitudes]

Hay X inscripciones pendientes que requieren tu atención.
Revisa cada solicitud, asigna el monto de pago y confirma la inscripción.

[📋 Ver Solicitudes Pendientes]
```

##### **Filtro por Estado**
- Todos
- **Pendiente** ← Aquí aparecen las pre-inscripciones
- Confirmada
- Cancelada

##### **Tabla de Inscripciones**

Columnas:
- ID
- Participante (nombre + email)
- DNI
- Evento
- Fecha Inscripción
- **Estado Inscripción** (badge con color)
- **Estado Pago** (badge o "Sin pago")
- **Acciones**:
  - 👁️ Ver detalles
  - 💳 Registrar pago

---

### 6️⃣ **Aprobación y Asignación de Pago**

**Flujo del Administrador/Responsable**:

#### Paso 1: Revisar Solicitud
```
Admin accede a Inscripciones
     ↓
Filtra por "Pendiente"
     ↓
Ve listado de pre-inscripciones
```

#### Paso 2: Registrar Pago
```
Click en "💳 Registrar pago"
     ↓
Formulario de pago:
  - Monto: Bs. XXXX
  - Método: efectivo/transferencia/qr/tarjeta
  - Fecha de pago
  - Comprobante (opcional)
  - Estado: pendiente/aprobado/rechazado
     ↓
Guardar pago
```

#### Paso 3: Confirmar Inscripción (opcional)

El administrador puede cambiar el estado de la inscripción de `pendiente` a `confirmada` una vez que:
- Haya registrado el pago
- El participante haya cumplido los requisitos

---

### 7️⃣ **Notificación al Participante**

Cuando el participante vuelve a su portal:

**Vista antes de aprobación**:
```
📝 Mis Inscripciones

[Evento X]
Estado: 🟡 Pendiente
Pago: ⏳ Esperando aprobación y asignación de pago
```

**Vista después de asignación de pago**:
```
📝 Mis Inscripciones

[Evento X]
Estado: 🟡 Pendiente (o 🟢 Confirmada)
Pago: 🟡 Pendiente | Monto: Bs. 100.00
```

**Vista después de confirmación completa**:
```
📝 Mis Inscripciones

[Evento X]
Estado: 🟢 Confirmada
Pago: 🟢 Aprobado | Monto: Bs. 100.00
```

---

## 🗂️ Archivos Creados/Modificados

### ✅ Archivos Nuevos

| Archivo | Descripción |
|---------|-------------|
| `/public/registro.php` | Formulario público de registro |
| `/public/registro_process.php` | Procesamiento de registro |
| `/modules/participante/mis_eventos.php` | Portal del participante |
| `/modules/participante/pre_inscribir.php` | Procesamiento de pre-inscripción |

### 📝 Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `/includes/header.php` | Agregada sección "Mi Portal" para participantes |
| `/auth/login.php` | Agregado link de registro |
| `/dashboard/index.php` | Redirect para participantes a su portal |
| `/modules/inscripciones/index.php` | Alerta de solicitudes pendientes |

---

## 🎨 Menú por Rol

### Participante ve:
```
📊 Dashboard (redirige a Mis Eventos)
🎯 Mis Eventos ← Su portal principal
🚪 Cerrar Sesión
```

### Admin/Responsable/Asistente ven:
```
📊 Dashboard
📅 Gestión (Eventos, Participantes, Inscripciones*, Pagos, etc.)
📈 Reportes
👤 Administración (solo Admin)
🚪 Cerrar Sesión

* Inscripciones muestra alerta de solicitudes pendientes
```

---

## 📊 Estados del Flujo

### Estado de Inscripción

| Estado | Significado | Color |
|--------|-------------|-------|
| `pendiente` | Pre-inscripción creada, esperando aprobación | 🟡 Amarillo |
| `confirmada` | Aprobada por admin/responsable | 🟢 Verde |
| `cancelada` | Rechazada o cancelada | 🔴 Rojo |

### Estado de Pago

| Estado | Significado | Color |
|--------|-------------|-------|
| NULL | No se ha asignado pago aún | 🔴 Rojo "Sin pago" |
| `pendiente` | Pago asignado, esperando comprobante | 🟡 Amarillo |
| `aprobado` | Pago verificado y aprobado | 🟢 Verde |
| `rechazado` | Pago rechazado | 🔴 Rojo |

---

## 🔐 Seguridad y Validaciones

### Registro Público
✅ Validación de email único  
✅ Validación de DNI único  
✅ Hash de contraseña con BCrypt  
✅ Sanitización de inputs  
✅ Transacciones de BD (rollback en caso de error)  
✅ Auditoría de registros  

### Pre-inscripción
✅ Verificación de participante válido  
✅ Verificación de evento activo  
✅ Control de capacidad del evento  
✅ Prevención de inscripciones duplicadas  
✅ Validación de cupos disponibles  

### Portal de Participante
✅ Solo ve sus propias inscripciones  
✅ Solo puede inscribirse si hay cupos  
✅ No puede inscribirse dos veces al mismo evento  
✅ Verificación de sesión y rol  

---

## 💡 Ejemplos de Uso

### Ejemplo 1: Participante Nuevo

**Juan** quiere asistir a un congreso:

1. Accede a `/auth/login.php`
2. Click en "Regístrate como Participante"
3. Completa formulario con sus datos
4. Crea cuenta exitosamente
5. Inicia sesión
6. Ve eventos disponibles
7. Click en "Pre-inscribirme" en el evento deseado
8. Mensaje: "¡Pre-inscripción exitosa!"
9. Espera notificación del administrador

### Ejemplo 2: Administrador Gestiona Solicitudes

**María** (administradora) revisa solicitudes:

1. Inicia sesión
2. Ve alerta: "⚠️ 5 Solicitudes Pendientes"
3. Click en "Ver Solicitudes Pendientes"
4. Revisa cada solicitud
5. Para cada una:
   - Click en "💳 Registrar pago"
   - Asigna monto: Bs. 150.00
   - Método: Transferencia
   - Guarda
6. Opcionalmente cambia estado de inscripción a "Confirmada"
7. Los participantes ven la actualización en su portal

---

## 📈 Beneficios del Sistema

| Beneficio | Descripción |
|-----------|-------------|
| **Autonomía** | Participantes se registran sin intervención |
| **Escalabilidad** | Reduce carga administrativa |
| **Trazabilidad** | Auditoría completa de solicitudes |
| **Control** | Admin aprueba antes de confirmar |
| **Transparencia** | Participante ve estado en tiempo real |
| **Eficiencia** | Proceso automatizado con validaciones |

---

## 🚀 Próximas Mejoras Posibles

1. **Notificaciones por Email**
   - Confirmación de registro
   - Aprobación de inscripción
   - Recordatorios de pago

2. **Portal de Pago Online**
   - Integración con pasarelas de pago
   - Upload de comprobantes por participante

3. **Chat/Mensajería**
   - Comunicación directa con organizadores

4. **Exportación de Credenciales**
   - Generar gafetes/credenciales para participantes

5. **Calendario Personal**
   - Ver agenda de eventos inscritos
   - Recordatorios de sesiones

---

## 🎯 Conclusión

El sistema ahora es **completamente auto-gestionable** para participantes:

✅ Registro público  
✅ Login automático  
✅ Pre-inscripción a eventos  
✅ Portal personalizado  
✅ Seguimiento de solicitudes  
✅ Flujo de aprobación claro  
✅ Gestión eficiente para administradores  

**El flujo está 100% funcional y listo para usar!** 🎉
