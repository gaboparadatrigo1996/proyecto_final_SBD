<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'inscripciones';
$pageTitle = 'Detalles de Inscripción';

if (!isset($_GET['id'])) {
    redirect('modules/inscripciones/index.php');
}

try {
    $db = Database::getInstance()->getConnection();
    
    $inscripcionId = (int)$_GET['id'];
    
    $stmt = $db->prepare("
        SELECT i.*, 
               p.nombres, p.apellidos, p.dni, p.email, p.telefono, p.institucion,
               e.nombre_evento, e.fecha_inicio, e.fecha_fin, e.lugar
        FROM inscripciones i
        INNER JOIN participantes p ON i.id_participante = p.id_participante
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        WHERE i.id_inscripcion = ?
    ");
    $stmt->execute([$inscripcionId]);
    $inscripcion = $stmt->fetch();
    
    if (!$inscripcion) {
        redirect('modules/inscripciones/index.php?error=notfound');
    }
    
    // Get payment if exists
    $stmt = $db->prepare("SELECT * FROM pagos WHERE id_inscripcion = ?");
    $stmt->execute([$inscripcionId]);
    $pago = $stmt->fetch();
    
    // Get certificate if exists
    $stmt = $db->prepare("SELECT * FROM certificados WHERE id_inscripcion = ?");
    $stmt->execute([$inscripcionId]);
    $certificado = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Inscription view error: " . $e->getMessage());
    redirect('modules/inscripciones/index.php?error=system');
}

include '../../includes/header.php';
?>

<?php if (isset($_GET['success']) && $_GET['success'] == 'created'): ?>
    <div class="alert alert-success">
        ✅ Inscripción registrada exitosamente!
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">Detalles de la Inscripción #<?php echo $inscripcion['id_inscripcion']; ?></h3>
        <a href="index.php" class="btn btn-outline">← Volver</a>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <h4 style="margin-bottom: 1rem; color: var(--primary);">Participante</h4>
                <p><strong><?php echo htmlspecialchars($inscripcion['nombres'] . ' ' . $inscripcion['apellidos']); ?></strong></p>
                <p>DNI: <?php echo htmlspecialchars($inscripcion['dni']); ?></p>
                <p>Email: <?php echo htmlspecialchars($inscripcion['email']); ?></p>
                <p>Teléfono: <?php echo htmlspecialchars($inscripcion['telefono'] ?? 'N/A'); ?></p>
            </div>
            
            <div>
                <h4 style="margin-bottom: 1rem; color: var(--primary);">Evento</h4>
                <p><strong><?php echo htmlspecialchars($inscripcion['nombre_evento']); ?></strong></p>
                <p>Fecha: <?php echo date('d/m/Y', strtotime($inscripcion['fecha_inicio'])); ?> al <?php echo date('d/m/Y', strtotime($inscripcion['fecha_fin'])); ?></p>
                <p>Lugar: <?php echo htmlspecialchars($inscripcion['lugar'] ?? 'N/A'); ?></p>
            </div>
        </div>
        
        <hr style="margin: 2rem 0;">
        
        <div>
            <h4 style="margin-bottom: 1rem; color: var(--primary);">Estado de Inscripción</h4>
            <p>
                <span class="badge badge-<?php 
                    echo $inscripcion['estado_inscripcion'] == 'confirmada' ? 'success' : 
                         ($inscripcion['estado_inscripcion'] == 'pendiente' ? 'warning' : 'danger'); 
                ?>" style="font-size: 1.1rem; padding: 0.5rem 1rem;">
                    <?php echo ucfirst($inscripcion['estado_inscripcion']); ?>
                </span>
            </p>
            <p>Fecha de inscripción: <?php echo date('d/m/Y H:i', strtotime($inscripcion['fecha_inscripcion'])); ?></p>
        </div>
    </div>
</div>

<?php if ($pago): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Información de Pago</h3>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;">
                <div>
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Monto:</label>
                    <p style="font-size: 1.3rem; font-weight: 700; margin: 0;">Bs. <?php echo number_format($pago['monto'], 2); ?></p>
                </div>
                <div>
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Método:</label>
                    <p style="margin: 0;">
                        <span class="badge badge-info"><?php echo ucfirst($pago['metodo_pago']); ?></span>
                    </p>
                </div>
                <div>
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Fecha:</label>
                    <p style="margin: 0;"><?php echo date('d/m/Y', strtotime($pago['fecha_pago'])); ?></p>
                </div>
                <div>
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Estado:</label>
                    <p style="margin: 0;">
                        <span class="badge badge-<?php 
                            echo $pago['estado_pago'] == 'aprobado' ? 'success' : 
                                 ($pago['estado_pago'] == 'pendiente' ? 'warning' : 'danger'); 
                        ?>">
                            <?php echo ucfirst($pago['estado_pago']); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        ⚠️ No hay pago registrado para esta inscripción.
        <a href="../pagos/crear.php?inscripcion=<?php echo $inscripcion['id_inscripcion']; ?>" class="btn btn-success btn-sm" style="margin-left: 1rem;">
            💳 Registrar Pago
        </a>
    </div>
<?php endif; ?>

<?php if ($certificado): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Certificado</h3>
        </div>
        <div class="card-body">
            <p>✅ Certificado emitido</p>
            <p>Código de validación: <code><?php echo htmlspecialchars($certificado['codigo_validacion']); ?></code></p>
            <p>Fecha de emisión: <?php echo date('d/m/Y', strtotime($certificado['fecha_emision'])); ?></p>
        </div>
    </div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
