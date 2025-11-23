<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'eventos';
$pageTitle = 'Gestión de Sesiones';

if (!isset($_GET['id'])) {
    redirect('modules/eventos/index.php');
}

$eventoId = (int)$_GET['id'];

try {
    $db = Database::getInstance()->getConnection();
    
    // Get event info
    $stmt = $db->prepare("SELECT * FROM eventos WHERE id_evento = ?");
    $stmt->execute([$eventoId]);
    $evento = $stmt->fetch();
    
    if (!$evento) {
        redirect('modules/eventos/index.php?error=notfound');
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nombre = sanitizeInput($_POST['nombre_sesion']);
        $descripcion = sanitizeInput($_POST['descripcion']);
        $fecha = $_POST['fecha'];
        $horaInicio = $_POST['hora_inicio'];
        $horaFin = $_POST['hora_fin'];
        $lugar = sanitizeInput($_POST['lugar_sesion']);
        $capacidad = !empty($_POST['capacidad']) ? (int)$_POST['capacidad'] : null;
        
        $stmt = $db->prepare("
            INSERT INTO sesiones (id_evento, nombre_sesion, descripcion, fecha, hora_inicio, hora_fin, lugar_sesion, capacidad)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $eventoId,
            $nombre,
            $descripcion,
            $fecha,
            $horaInicio,
            $horaFin,
            $lugar,
            $capacidad
        ]);
        
        $sesionId = $db->lastInsertId();
        
        logAudit($_SESSION['user_id'], 'CREATE', 'sesiones', $sesionId, "Sesión creada: $nombre");
        
        $success = "Sesión creada exitosamente!";
    }
    
    // Get sessions
    $stmt = $db->prepare("
        SELECT s.*,
               COUNT(DISTINCT a.id_asistencia) as total_asistencias
        FROM sesiones s
        LEFT JOIN asistencias a ON s.id_sesion = a.id_sesion
        WHERE s.id_evento = ?
        GROUP BY s.id_sesion
        ORDER BY s.fecha, s.hora_inicio
    ");
    $stmt->execute([$eventoId]);
    $sesiones = $stmt->fetchAll();
    
    $pageTitle = 'Sesiones - ' . $evento['nombre_evento'];
    
} catch (Exception $e) {
    error_log("Sessions error: " . $e->getMessage());
    $error = "Error al gestionar las sesiones.";
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Evento: <?php echo htmlspecialchars($evento['nombre_evento']); ?></h3>
    </div>
    <div class="card-body">
        <p><strong>Fechas:</strong> <?php echo date('d/m/Y', strtotime($evento['fecha_inicio'])); ?> al <?php echo date('d/m/Y', strtotime($evento['fecha_fin'])); ?></p>
        <p><strong>Lugar:</strong> <?php echo htmlspecialchars($evento['lugar'] ?? 'No especificado'); ?></p>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Crear Nueva Sesión</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Nombre de la Sesión *</label>
                    <input type="text" name="nombre_sesion" class="form-control" required placeholder="Ej: Conferencia Inaugural">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Fecha *</label>
                    <input type="date" name="fecha" class="form-control" required 
                           min="<?php echo $evento['fecha_inicio']; ?>" 
                           max="<?php echo $evento['fecha_fin']; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Lugar de la Sesión</label>
                    <input type="text" name="lugar_sesion" class="form-control" placeholder="Ej: Auditorio A">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Hora de Inicio *</label>
                    <input type="time" name="hora_inicio" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Hora de Fin *</label>
                    <input type="time" name="hora_fin" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Capacidad (opcional)</label>
                    <input type="number" name="capacidad" class="form-control" placeholder="Dejar vacío para sin límite">
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="submit" class="btn btn-success">💾 Crear Sesión</button>
                <a href="ver.php?id=<?php echo $eventoId; ?>" class="btn btn-outline">← Volver al Evento</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Sesiones Creadas (<?php echo count($sesiones); ?>)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($sesiones)): ?>
            <div class="alert alert-info" style="margin: 1rem;">
                ℹ️ <strong>No hay sesiones creadas aún.</strong><br>
                Las sesiones son necesarias para controlar la asistencia por cada actividad del evento.<br>
                <small>Ejemplo: Conferencia Magistral, Taller Práctico, Panel de Discusión, etc.</small>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre de la Sesión</th>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Lugar</th>
                            <th>Capacidad</th>
                            <th>Asistencias</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sesiones as $sesion): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($sesion['nombre_sesion']); ?></strong>
                                    <?php if ($sesion['descripcion']): ?>
                                        <br><small style="color: var(--gray);"><?php echo htmlspecialchars($sesion['descripcion']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($sesion['fecha'])); ?></td>
                                <td>
                                    <?php echo date('H:i', strtotime($sesion['hora_inicio'])); ?> - 
                                    <?php echo date('H:i', strtotime($sesion['hora_fin'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($sesion['lugar_sesion'] ?? 'N/A'); ?></td>
                                <td><?php echo $sesion['capacidad'] ?? 'Sin límite'; ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $sesion['total_asistencias']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="../asistencia/index.php?evento=<?php echo $eventoId; ?>&sesion=<?php echo $sesion['id_sesion']; ?>" 
                                       class="btn btn-sm btn-primary" title="Control de Asistencia">
                                        ✅ Asistencia
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
