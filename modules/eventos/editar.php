<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'eventos';
$pageTitle = 'Editar Evento';

$id = $_GET['id'] ?? null;

if (!$id) {
    redirect('modules/eventos/index.php');
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get event data
    $stmt = $db->prepare("SELECT * FROM eventos WHERE id_evento = ?");
    $stmt->execute([$id]);
    $evento = $stmt->fetch();
    
    if (!$evento) {
        redirect('modules/eventos/index.php?error=not_found');
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nombre = sanitizeInput($_POST['nombre_evento']);
        $descripcion = sanitizeInput($_POST['descripcion']);
        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin = $_POST['fecha_fin'];
        $lugar = sanitizeInput($_POST['lugar']);
        $capacidad = !empty($_POST['capacidad_maxima']) ? (int)$_POST['capacidad_maxima'] : null;
        $estado = $_POST['estado'];
        
        $stmt = $db->prepare("
            UPDATE eventos 
            SET nombre_evento = ?, 
                descripcion = ?, 
                fecha_inicio = ?, 
                fecha_fin = ?, 
                lugar = ?, 
                capacidad_maxima = ?,
                estado = ?
            WHERE id_evento = ?
        ");
        
        $stmt->execute([
            $nombre,
            $descripcion,
            $fecha_inicio,
            $fecha_fin,
            $lugar,
            $capacidad,
            $estado,
            $id
        ]);
        
        logAudit($_SESSION['user_id'], 'UPDATE', 'eventos', $id, "Evento actualizado: $nombre");
        
        redirect('modules/eventos/ver.php?id=' . $id . '&success=updated');
    }
    
} catch (Exception $e) {
    error_log("Event update error: " . $e->getMessage());
    $error = "Error al actualizar el evento.";
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Editar Evento: <?php echo htmlspecialchars($evento['nombre_evento']); ?></h3>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Nombre del Evento *</label>
                    <input type="text" name="nombre_evento" class="form-control" required value="<?php echo htmlspecialchars($evento['nombre_evento']); ?>">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="4"><?php echo htmlspecialchars($evento['descripcion']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Fecha de Inicio *</label>
                    <input type="date" name="fecha_inicio" class="form-control" required value="<?php echo $evento['fecha_inicio']; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Fecha de Fin *</label>
                    <input type="date" name="fecha_fin" class="form-control" required value="<?php echo $evento['fecha_fin']; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Lugar</label>
                    <input type="text" name="lugar" class="form-control" value="<?php echo htmlspecialchars($evento['lugar']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Capacidad Máxima</label>
                    <input type="number" name="capacidad_maxima" class="form-control" value="<?php echo $evento['capacidad_maxima']; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-control">
                        <option value="activo" <?php echo $evento['estado'] == 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="finalizado" <?php echo $evento['estado'] == 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                        <option value="cancelado" <?php echo $evento['estado'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
                <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
