<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'eventos';
$pageTitle = 'Crear Evento';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = Database::getInstance()->getConnection();
        
        $nombre = sanitizeInput($_POST['nombre_evento']);
        $descripcion = sanitizeInput($_POST['descripcion']);
        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin = $_POST['fecha_fin'];
        $lugar = sanitizeInput($_POST['lugar']);
        $capacidad = !empty($_POST['capacidad_maxima']) ? (int)$_POST['capacidad_maxima'] : null;
        
        $stmt = $db->prepare("
            INSERT INTO eventos (nombre_evento, descripcion, fecha_inicio, fecha_fin, lugar, capacidad_maxima, creado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $nombre,
            $descripcion,
            $fecha_inicio,
            $fecha_fin,
            $lugar,
            $capacidad,
            $_SESSION['user_id']
        ]);
        
        $eventoId = $db->lastInsertId();
        
        logAudit($_SESSION['user_id'], 'CREATE', 'eventos', $eventoId, "Evento creado: $nombre");
        
        redirect('modules/eventos/ver.php?id=' . $eventoId . '&success=created');
        
    } catch (Exception $e) {
        error_log("Event creation error: " . $e->getMessage());
        $error = "Error al crear el evento. Por favor, intente nuevamente.";
    }
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Crear Nuevo Evento</h3>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" id="createEventForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Nombre del Evento *</label>
                    <input type="text" name="nombre_evento" class="form-control" required>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Fecha de Inicio *</label>
                    <input type="date" name="fecha_inicio" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Fecha de Fin *</label>
                    <input type="date" name="fecha_fin" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Lugar</label>
                    <input type="text" name="lugar" class="form-control" placeholder="Ej: Auditorio Principal">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Capacidad Máxima</label>
                    <input type="number" name="capacidad_maxima" class="form-control" placeholder="Dejar vacío para sin límite">
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-success">💾 Guardar Evento</button>
                <a href="index.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
