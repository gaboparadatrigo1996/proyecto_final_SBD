<?php
require_once '../config/config.php';
checkSession();

$currentPage = 'dashboard';
$pageTitle = 'Dashboard';

// Get statistics
try {
    $db = Database::getInstance()->getConnection();
    
    // Total events
    $stmt = $db->query("SELECT COUNT(*) as total FROM eventos WHERE estado = 'activo'");
    $totalEventos = $stmt->fetch()['total'];
    
    // Total participants
    $stmt = $db->query("SELECT COUNT(*) as total FROM participantes");
    $totalParticipantes = $stmt->fetch()['total'];
    
    // Total registrations
    $stmt = $db->query("SELECT COUNT(*) as total FROM inscripciones WHERE estado_inscripcion = 'confirmada'");
    $totalInscripciones = $stmt->fetch()['total'];
    
    // Pending payments
    $stmt = $db->query("SELECT COUNT(*) as total FROM pagos WHERE estado_pago = 'pendiente'");
    $pagosPendientes = $stmt->fetch()['total'];
    
    // Recent events
    $stmt = $db->query("
        SELECT e.*, u.nombre_completo as creador
        FROM eventos e
        LEFT JOIN usuarios u ON e.creado_por = u.id_usuario
        ORDER BY e.fecha_creacion DESC
        LIMIT 5
    ");
    $eventosRecientes = $stmt->fetchAll();
    
    // Recent registrations
    $stmt = $db->query("
        SELECT i.*, p.nombres, p.apellidos, e.nombre_evento
        FROM inscripciones i
        INNER JOIN participantes p ON i.id_participante = p.id_participante
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        ORDER BY i.fecha_inscripcion DESC
        LIMIT 5
    ");
    $inscripcionesRecientes = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $totalEventos = $totalParticipantes = $totalInscripciones = $pagosPendientes = 0;
    $eventosRecientes = $inscripcionesRecientes = [];
}

include '../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $totalEventos; ?></div>
                <div class="stat-label">Eventos Activos</div>
            </div>
            <div class="stat-icon">📅</div>
        </div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $totalParticipantes; ?></div>
                <div class="stat-label">Total Participantes</div>
            </div>
            <div class="stat-icon">👥</div>
        </div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $totalInscripciones; ?></div>
                <div class="stat-label">Inscripciones Confirmadas</div>
            </div>
            <div class="stat-icon">✍️</div>
        </div>
    </div>
    
    <div class="stat-card danger">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $pagosPendientes; ?></div>
                <div class="stat-label">Pagos Pendientes</div>
            </div>
            <div class="stat-icon">💳</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <!-- Recent Events -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Eventos Recientes</h3>
        </div>
        <div class="card-body">
            <?php if (empty($eventosRecientes)): ?>
                <p style="color: var(--gray); text-align: center;">No hay eventos registrados</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($eventosRecientes as $evento): ?>
                        <div style="padding: 1rem; border-left: 3px solid var(--primary); background: var(--light); border-radius: 8px;">
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem;"><?php echo htmlspecialchars($evento['nombre_evento']); ?></h4>
                            <div style="display: flex; gap: 1rem; font-size: 0.85rem; color: var(--gray);">
                                <span>📅 <?php echo date('d/m/Y', strtotime($evento['fecha_inicio'])); ?></span>
                                <span class="badge badge-<?php echo $evento['estado'] == 'activo' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($evento['estado']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Registrations -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Inscripciones Recientes</h3>
        </div>
        <div class="card-body">
            <?php if (empty($inscripcionesRecientes)): ?>
                <p style="color: var(--gray); text-align: center;">No hay inscripciones registradas</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($inscripcionesRecientes as $inscripcion): ?>
                        <div style="padding: 1rem; border-left: 3px solid var(--success); background: var(--light); border-radius: 8px;">
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem;">
                                <?php echo htmlspecialchars($inscripcion['nombres'] . ' ' . $inscripcion['apellidos']); ?>
                            </h4>
                            <div style="font-size: 0.85rem; color: var(--gray);">
                                <div><?php echo htmlspecialchars($inscripcion['nombre_evento']); ?></div>
                                <div style="margin-top: 0.25rem;">
                                    📅 <?php echo date('d/m/Y H:i', strtotime($inscripcion['fecha_inscripcion'])); ?>
                                    <span class="badge badge-<?php 
                                        echo $inscripcion['estado_inscripcion'] == 'confirmada' ? 'success' : 
                                             ($inscripcion['estado_inscripcion'] == 'pendiente' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($inscripcion['estado_inscripcion']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Acciones Rápidas</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <?php if (hasRole(['Administrador', 'Responsable de Inscripción'])): ?>
                <a href="<?php echo BASE_URL; ?>/modules/eventos/crear.php" class="btn btn-primary">
                    ➕ Nuevo Evento
                </a>
                <a href="<?php echo BASE_URL; ?>/modules/participantes/crear.php" class="btn btn-secondary">
                    👤 Registrar Participante
                </a>
                <a href="<?php echo BASE_URL; ?>/modules/inscripciones/crear.php" class="btn btn-success">
                    ✍️ Nueva Inscripción
                </a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/modules/reportes/index.php" class="btn btn-outline">
                📊 Ver Reportes
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
