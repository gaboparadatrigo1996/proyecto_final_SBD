<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'pagos';
$pageTitle = 'Detalles del Pago';

if (!isset($_GET['id'])) {
    redirect('modules/pagos/index.php');
}

try {
    $db = Database::getInstance()->getConnection();
    
    $pagoId = (int)$_GET['id'];
    
    $stmt = $db->prepare("
        SELECT pag.*, 
               i.id_evento,
               p.nombres, p.apellidos, p.dni, p.email,
               e.nombre_evento,
               u.nombre_completo as registrado_por_nombre
        FROM pagos pag
        INNER JOIN inscripciones i ON pag.id_inscripcion = i.id_inscripcion
        INNER JOIN participantes p ON i.id_participante = p.id_participante
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        LEFT JOIN usuarios u ON pag.registrado_por = u.id_usuario
        WHERE pag.id_pago = ?
    ");
    $stmt->execute([$pagoId]);
    $pago = $stmt->fetch();
    
    if (!$pago) {
        redirect('modules/pagos/index.php?error=notfound');
    }
    
} catch (Exception $e) {
    error_log("Payment view error: " . $e->getMessage());
    redirect('modules/pagos/index.php?error=system');
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">Detalles del Pago #<?php echo $pago['id_pago']; ?></h3>
        <a href="index.php" class="btn btn-outline">← Volver</a>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <h4 style="margin-bottom: 1rem; color: var(--primary);">Información del Pago</h4>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Monto:</label>
                    <p style="margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--primary);">
                        Bs. <?php echo number_format($pago['monto'], 2); ?>
                    </p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Método de Pago:</label>
                    <p style="margin: 0;">
                        <span class="badge badge-info" style="font-size: 1rem;">
                            <?php echo ucfirst($pago['metodo_pago']); ?>
                        </span>
                    </p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Fecha de Pago:</label>
                    <p style="margin: 0;"><?php echo date('d/m/Y', strtotime($pago['fecha_pago'])); ?></p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Estado:</label>
                    <p style="margin: 0;">
                        <span class="badge badge-<?php 
                            echo $pago['estado_pago'] == 'aprobado' ? 'success' : 
                                 ($pago['estado_pago'] == 'pendiente' ? 'warning' : 'danger'); 
                        ?>" style="font-size: 1.1rem; padding: 0.5rem 1rem;">
                            <?php echo ucfirst($pago['estado_pago']); ?>
                        </span>
                    </p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Registrado por:</label>
                    <p style="margin: 0;"><?php echo htmlspecialchars($pago['registrado_por_nombre'] ?? 'Sistema'); ?></p>
                </div>
            </div>
            
            <div>
                <h4 style="margin-bottom: 1rem; color: var(--primary);">Participante y Evento</h4>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Participante:</label>
                    <p style="margin: 0;">
                        <strong><?php echo htmlspecialchars($pago['nombres'] . ' ' . $pago['apellidos']); ?></strong>
                    </p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">DNI:</label>
                    <p style="margin: 0;"><?php echo htmlspecialchars($pago['dni']); ?></p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Email:</label>
                    <p style="margin: 0;"><?php echo htmlspecialchars($pago['email']); ?></p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Evento:</label>
                    <p style="margin: 0;">
                        <strong><?php echo htmlspecialchars($pago['nombre_evento']); ?></strong>
                    </p>
                </div>
            </div>
        </div>
        
        <?php if ($pago['estado_pago'] == 'pendiente' && hasRole(['Administrador', 'Responsable de Inscripción'])): ?>
            <hr style="margin: 2rem 0;">
            <div style="display: flex; gap: 1rem;">
                <a href="aprobar.php?id=<?php echo $pago['id_pago']; ?>" 
                   class="btn btn-success"
                   onclick="return confirm('¿Está seguro de aprobar este pago?')">
                    ✓ Aprobar Pago
                </a>
                <a href="rechazar.php?id=<?php echo $pago['id_pago']; ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('¿Está seguro de rechazar este pago?')">
                    ✗ Rechazar Pago
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
