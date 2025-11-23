<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'participantes';
$pageTitle = 'Detalles del Participante';

if (!isset($_GET['id'])) {
    redirect('modules/participantes/index.php');
}

$participanteId = (int)$_GET['id'];

try {
    $db = Database::getInstance()->getConnection();
    
    // Get participant data
    $stmt = $db->prepare("
        SELECT p.*,
               fn_total_eventos_participante(p.id_participante) as total_eventos
        FROM participantes p
        WHERE p.id_participante = ?
    ");
    $stmt->execute([$participanteId]);
    $participante = $stmt->fetch();
    
    if (!$participante) {
        redirect('modules/participantes/index.php?error=notfound');
    }
    
    // Get participant's inscriptions
    $stmt = $db->prepare("
        SELECT i.*, e.nombre_evento, e.fecha_inicio, e.fecha_fin,
               pag.estado_pago, c.codigo_validacion
        FROM inscripciones i
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        LEFT JOIN pagos pag ON i.id_inscripcion = pag.id_inscripcion
        LEFT JOIN certificados c ON i.id_inscripcion = c.id_inscripcion
        WHERE i.id_participante = ?
        ORDER BY e.fecha_inicio DESC
    ");
    $stmt->execute([$participanteId]);
    $inscripciones = $stmt->fetchAll();
    
    $pageTitle = $participante['nombres'] . ' ' . $participante['apellidos'];
    
} catch (Exception $e) {
    error_log("Participant view error: " . $e->getMessage());
    redirect('modules/participantes/index.php?error=system');
}

include '../../includes/header.php';
?>

<?php if (isset($_GET['success']) && $_GET['success'] == 'created'): ?>
    <div class="alert alert-success">
        ✅ Participante registrado exitosamente!
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">Información del Participante</h3>
        <div style="display: flex; gap: 0.5rem;">
            <a href="editar.php?id=<?php echo $participante['id_participante']; ?>" class="btn btn-secondary">✏️ Editar</a>
            <a href="index.php" class="btn btn-outline">← Volver</a>
        </div>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <h4 style="margin-bottom: 1rem; color: var(--primary);">Datos Personales</h4>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">DNI / Cédula:</label>
                    <p style="margin: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($participante['dni']); ?></p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Nombres:</label>
                    <p style="margin: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($participante['nombres']); ?></p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Apellidos:</label>
                    <p style="margin: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($participante['apellidos']); ?></p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Email:</label>
                    <p style="margin: 0;"><?php echo htmlspecialchars($participante['email']); ?></p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Teléfono:</label>
                    <p style="margin: 0;"><?php echo htmlspecialchars($participante['telefono'] ?? 'No especificado'); ?></p>
                </div>
            </div>
            
            <div>
                <h4 style="margin-bottom: 1rem; color: var(--primary);">Información Adicional</h4>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Tipo de Participante:</label>
                    <p style="margin: 0;">
                        <span class="badge badge-info">
                            <?php echo ucfirst($participante['tipo_participante']); ?>
                        </span>
                    </p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Institución:</label>
                    <p style="margin: 0;"><?php echo htmlspecialchars($participante['institucion'] ?? 'No especificada'); ?></p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Fecha de Registro:</label>
                    <p style="margin: 0;"><?php echo date('d/m/Y H:i', strtotime($participante['fecha_registro'])); ?></p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--gray); font-size: 0.9rem;">Total de Eventos:</label>
                    <p style="margin: 0;">
                        <span class="badge badge-primary" style="font-size: 1.2rem;">
                            <?php echo $participante['total_eventos']; ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">Inscripciones (<?php echo count($inscripciones); ?>)</h3>
        <a href="../inscripciones/crear.php?participante=<?php echo $participante['id_participante']; ?>" class="btn btn-success btn-sm">
            ➕ Nueva Inscripción
        </a>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($inscripciones)): ?>
            <p style="text-align: center; padding: 2rem; color: var(--gray);">
                Este participante no tiene inscripciones aún.
            </p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th>Fecha Evento</th>
                            <th>Estado Inscripción</th>
                            <th>Estado Pago</th>
                            <th>Certificado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inscripciones as $insc): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($insc['nombre_evento']); ?></strong>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($insc['fecha_inicio'])); ?>
                                    al
                                    <?php echo date('d/m/Y', strtotime($insc['fecha_fin'])); ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $insc['estado_inscripcion'] == 'confirmada' ? 'success' : 
                                             ($insc['estado_inscripcion'] == 'pendiente' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($insc['estado_inscripcion']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($insc['estado_pago']): ?>
                                        <span class="badge badge-<?php 
                                            echo $insc['estado_pago'] == 'aprobado' ? 'success' : 
                                                 ($insc['estado_pago'] == 'pendiente' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($insc['estado_pago']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Sin pago</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($insc['codigo_validacion']): ?>
                                        <span class="badge badge-success">✓ Emitido</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Pendiente</span>
                                    <?php endif; ?>
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
