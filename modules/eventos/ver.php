<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'eventos';
$pageTitle = 'Detalles del Evento';

if (!isset($_GET['id'])) {
    redirect('modules/eventos/index.php');
}

try {
    $db = Database::getInstance()->getConnection();
    
    $eventoId = (int)$_GET['id'];
    
    $stmt = $db->prepare("
        SELECT e.*, u.nombre_completo as creador,
               fn_total_recaudado_evento(e.id_evento) as total_recaudado,
               fn_espacios_disponibles(e.id_evento) as espacios_disponibles
        FROM eventos e
        LEFT JOIN usuarios u ON e.creado_por = u.id_usuario
        WHERE e.id_evento = ?
    ");
    $stmt->execute([$eventoId]);
    $evento = $stmt->fetch();
    
    if (!$evento) {
        redirect('modules/eventos/index.php?error=notfound');
    }
    
    // Get statistics
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM inscripciones WHERE id_evento = ?");
    $stmt->execute([$eventoId]);
    $totalInscripciones = $stmt->fetch()['total'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM sesiones WHERE id_evento = ?");
    $stmt->execute([$eventoId]);
    $totalSesiones = $stmt->fetch()['total'];
    
    $pageTitle = $evento['nombre_evento'];
    
} catch (Exception $e) {
    error_log("Event view error: " . $e->getMessage());
    redirect('modules/eventos/index.php?error=system');
}

include '../../includes/header.php';
?>

<?php if (isset($_GET['success']) && $_GET['success'] == 'created'): ?>
    <div class="alert alert-success">
        ✅ Evento creado exitosamente!
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title"><?php echo htmlspecialchars($evento['nombre_evento']); ?></h3>
        <div style="display: flex; gap: 0.5rem;">
            <a href="editar.php?id=<?php echo $evento['id_evento']; ?>" class="btn btn-secondary">✏️ Editar</a>
            <a href="index.php" class="btn btn-outline">← Volver</a>
        </div>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <h4 style="margin-bottom: 1rem; color: var(--primary);">Información General</h4>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Descripción:</label>
                    <p style="margin: 0;"><?php echo nl2br(htmlspecialchars($evento['descripcion'] ?? 'Sin descripción')); ?></p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Estado:</label>
                    <p style="margin: 0;">
                        <span class="badge badge-<?php 
                            echo $evento['estado'] == 'activo' ? 'success' : 
                                 ($evento['estado'] == 'finalizado' ? 'warning' : 'danger'); 
                        ?>" style="font-size: 1.1rem;">
                            <?php echo ucfirst($evento['estado']); ?>
                        </span>
                    </p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Creado por:</label>
                    <p style="margin: 0;"><?php echo htmlspecialchars($evento['creador']); ?></p>
                </div>
            </div>
            
            <div>
                <h4 style="margin-bottom: 1rem; color: var(--primary);">Fechas y Lugar</h4>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Fecha de Inicio:</label>
                    <p style="margin: 0; font-size: 1.1rem;"><?php echo date('d/m/Y', strtotime($evento['fecha_inicio'])); ?></p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Fecha de Fin:</label>
                    <p style="margin: 0; font-size: 1.1rem;"><?php echo date('d/m/Y', strtotime($evento['fecha_fin'])); ?></p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Lugar:</label>
                    <p style="margin: 0;"><?php echo htmlspecialchars($evento['lugar'] ?? 'No especificado'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card primary">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $totalInscripciones; ?></div>
                <div class="stat-label">Total Inscripciones</div>
            </div>
            <div class="stat-icon">✍️</div>
        </div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $evento['espacios_disponibles']; ?></div>
                <div class="stat-label">Espacios Disponibles</div>
            </div>
            <div class="stat-icon">📊</div>
        </div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $totalSesiones; ?></div>
                <div class="stat-label">Total Sesiones</div>
            </div>
            <div class="stat-icon">📅</div>
        </div>
    </div>
    
    <div class="stat-card danger">
        <div class="stat-header">
            <div>
                <div class="stat-value">Bs. <?php echo number_format($evento['total_recaudado'], 2); ?></div>
                <div class="stat-label">Total Recaudado</div>
            </div>
            <div class="stat-icon">💰</div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
