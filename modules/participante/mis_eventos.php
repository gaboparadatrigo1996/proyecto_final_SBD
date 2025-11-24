<?php
require_once '../../config/config.php';
checkSession();

// Only for participants
if (!hasRole('Participante')) {
    redirect('dashboard/index.php');
}

$currentPage = 'mis-eventos';
$pageTitle = 'Mis Eventos';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get participant ID based on user email
    $stmt = $db->prepare("
        SELECT p.id_participante, p.nombres, p.apellidos, p.tipo_participante
        FROM participantes p
        INNER JOIN usuarios u ON p.email = u.email
        WHERE u.id_usuario = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $participante = $stmt->fetch();
    
    if (!$participante) {
        throw new Exception("Participante no encontrado");
    }
    
    $idParticipante = $participante['id_participante'];
    
    // Get participant's registrations
    $stmt = $db->prepare("
        SELECT 
            i.*,
            e.nombre_evento,
            e.descripcion,
            e.fecha_inicio,
            e.fecha_fin,
            e.lugar,
            e.estado as estado_evento,
            p.estado_pago,
            p.monto,
            p.metodo_pago,
            c.id_certificado,
            c.codigo_validacion as certificado_codigo,
            c.fecha_emision as certificado_fecha
        FROM inscripciones i
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        LEFT JOIN pagos p ON i.id_inscripcion = p.id_inscripcion
        LEFT JOIN certificados c ON i.id_inscripcion = c.id_inscripcion
        WHERE i.id_participante = ?
        ORDER BY i.fecha_inscripcion DESC
    ");
    $stmt->execute([$idParticipante]);
    $misInscripciones = $stmt->fetchAll();
    
    // Get available events for registration
    $stmt = $db->query("
        SELECT 
            e.*,
            COUNT(i.id_inscripcion) as total_inscritos,
            (e.capacidad_maxima - COUNT(i.id_inscripcion)) as cupos_disponibles
        FROM eventos e
        LEFT JOIN inscripciones i ON e.id_evento = i.id_evento
        WHERE e.estado = 'activo' 
        AND e.fecha_inicio >= CURDATE()
        GROUP BY e.id_evento
        HAVING cupos_disponibles > 0
        ORDER BY e.fecha_inicio ASC
    ");
    $eventosDisponibles = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Participant events error: " . $e->getMessage());
    $misInscripciones = [];
    $eventosDisponibles = [];
}

include '../../includes/header.php';
?>

<style>
.event-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
    transition: transform 0.2s;
}

.event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.event-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}

.event-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--dark);
    margin: 0;
}

.event-meta {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.event-meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--gray);
    font-size: 0.9rem;
}

.event-description {
    color: var(--gray);
    margin-bottom: 1rem;
}

.event-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}
</style>

<div class="stats-grid" style="grid-template-columns: repeat(5, 1fr); margin-bottom: 2rem;">
    <div class="stat-card info">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo count($misInscripciones); ?></div>
                <div class="stat-label">Mis Inscripciones</div>
            </div>
            <div class="stat-icon">📝</div>
        </div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div>
                <div class="stat-value">
                    <?php 
                    echo count(array_filter($misInscripciones, function($i) { 
                        return $i['estado_inscripcion'] == 'confirmada'; 
                    })); 
                    ?>
                </div>
                <div class="stat-label">Confirmadas</div>
            </div>
            <div class="stat-icon">✅</div>
        </div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div>
                <div class="stat-value">
                    <?php 
                    echo count(array_filter($misInscripciones, function($i) { 
                        return $i['estado_inscripcion'] == 'pendiente'; 
                    })); 
                    ?>
                </div>
                <div class="stat-label">Pendientes</div>
            </div>
            <div class="stat-icon">⏳</div>
        </div>
    </div>
    
    <div class="stat-card primary">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo count($eventosDisponibles); ?></div>
                <div class="stat-label">Eventos Disponibles</div>
            </div>
            <div class="stat-icon">📅</div>
        </div>
    </div>
    
    <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
        <div class="stat-header">
            <div>
                <div class="stat-value">
                    <?php 
                    echo count(array_filter($misInscripciones, function($i) { 
                        return !empty($i['certificado_codigo']); 
                    })); 
                    ?>
                </div>
                <div class="stat-label">Certificados</div>
            </div>
            <div class="stat-icon">🎖️</div>
        </div>
    </div>
</div>

<!-- Mis Inscripciones -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3 class="card-title">📋 Mis Inscripciones</h3>
    </div>
    <div class="card-body">
        <?php if (empty($misInscripciones)): ?>
            <div style="text-align: center; padding: 3rem; color: var(--gray);">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
                <h3>No tienes inscripciones aún</h3>
                <p>Explora los eventos disponibles abajo y realiza tu primera pre-inscripción</p>
            </div>
        <?php else: ?>
            <?php foreach ($misInscripciones as $inscr): ?>
                <div class="event-card">
                    <div class="event-header">
                        <div>
                            <h4 class="event-title"><?php echo htmlspecialchars($inscr['nombre_evento']); ?></h4>
                        </div>
                        <span class="badge badge-<?php 
                            echo $inscr['estado_inscripcion'] == 'confirmada' ? 'success' : 
                                 ($inscr['estado_inscripcion'] == 'pendiente' ? 'warning' : 'danger'); 
                        ?>">
                            <?php echo ucfirst($inscr['estado_inscripcion']); ?>
                        </span>
                    </div>
                    
                    <div class="event-meta">
                        <div class="event-meta-item">
                            <span>📅</span>
                            <span><?php echo date('d/m/Y', strtotime($inscr['fecha_inicio'])); ?></span>
                        </div>
                        <div class="event-meta-item">
                            <span>📍</span>
                            <span><?php echo htmlspecialchars($inscr['lugar']); ?></span>
                        </div>
                        <div class="event-meta-item">
                            <span>🕒</span>
                            <span>Inscrito: <?php echo date('d/m/Y', strtotime($inscr['fecha_inscripcion'])); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($inscr['descripcion']): ?>
                        <p class="event-description"><?php echo htmlspecialchars($inscr['descripcion']); ?></p>
                    <?php endif; ?>
                    
                    <div style="display: flex; gap: 1rem; align-items: center; padding: 1rem; background: var(--light); border-radius: 8px;">
                        <div style="flex: 1;">
                            <?php if ($inscr['estado_pago']): ?>
                                <strong>Estado de Pago:</strong>
                                <span class="badge badge-<?php 
                                    echo $inscr['estado_pago'] == 'aprobado' ? 'success' : 
                                         ($inscr['estado_pago'] == 'pendiente' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($inscr['estado_pago']); ?>
                                </span>
                                <?php if ($inscr['monto']): ?>
                                    <span style="margin-left: 1rem;">
                                        <strong>Monto:</strong> Bs. <?php echo number_format($inscr['monto'], 2); ?>
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: var(--gray);">⏳ Esperando aprobación y asignación de pago</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($inscr['certificado_codigo']): ?>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <a href="../certificados/descargar.php?id=<?php echo $inscr['id_certificado']; ?>" 
                                   class="btn btn-success" target="_blank" title="Descargar Certificado">
                                    📥 Descargar Certificado
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($inscr['certificado_codigo']): ?>
                    <div style="margin-top: 1rem; padding: 1rem; background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius: 8px; border-left: 4px solid #10b981;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="color: #065f46;">🎖️ ¡Certificado Disponible!</strong>
                                <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #047857;">
                                    Código: <code style="background: white; padding: 2px 8px; border-radius: 4px;"><?php echo $inscr['certificado_codigo']; ?></code>
                                    <?php if ($inscr['certificado_fecha']): ?>
                                        <span style="margin-left: 1rem;">Emitido: <?php echo date('d/m/Y', strtotime($inscr['certificado_fecha'])); ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <a href="../certificados/descargar.php?id=<?php echo $inscr['id_certificado']; ?>" 
                               class="btn btn-sm btn-success" target="_blank">
                                🖨️ Imprimir PDF
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Eventos Disponibles -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">🎯 Eventos Disponibles para Inscripción</h3>
    </div>
    <div class="card-body">
        <?php if (empty($eventosDisponibles)): ?>
            <div style="text-align: center; padding: 3rem; color: var(--gray);">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📅</div>
                <h3>No hay eventos disponibles</h3>
                <p>Por el momento no hay eventos activos con cupos disponibles</p>
            </div>
        <?php else: ?>
            <?php foreach ($eventosDisponibles as $evento): ?>
                <?php
                // Check if already registered
                $yaInscrito = false;
                foreach ($misInscripciones as $inscr) {
                    if ($inscr['id_evento'] == $evento['id_evento']) {
                        $yaInscrito = true;
                        break;
                    }
                }
                ?>
                
                <div class="event-card">
                    <div class="event-header">
                        <div>
                            <h4 class="event-title"><?php echo htmlspecialchars($evento['nombre_evento']); ?></h4>
                        </div>
                        <?php if ($yaInscrito): ?>
                            <span class="badge badge-info">Ya inscrito</span>
                        <?php else: ?>
                            <span class="badge badge-success"><?php echo $evento['cupos_disponibles']; ?> cupos</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="event-meta">
                        <div class="event-meta-item">
                            <span>📅</span>
                            <span>
                                <?php 
                                echo date('d/m/Y', strtotime($evento['fecha_inicio']));
                                if ($evento['fecha_inicio'] != $evento['fecha_fin']) {
                                    echo ' - ' . date('d/m/Y', strtotime($evento['fecha_fin']));
                                }
                                ?>
                            </span>
                        </div>
                        <div class="event-meta-item">
                            <span>📍</span>
                            <span><?php echo htmlspecialchars($evento['lugar']); ?></span>
                        </div>
                        <div class="event-meta-item">
                            <span>👥</span>
                            <span><?php echo $evento['total_inscritos']; ?> / <?php echo $evento['capacidad_maxima']; ?> inscritos</span>
                        </div>
                    </div>
                    
                    <?php if ($evento['descripcion']): ?>
                        <p class="event-description"><?php echo htmlspecialchars($evento['descripcion']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!$yaInscrito): ?>
                        <div class="event-actions">
                            <form method="POST" action="pre_inscribir.php" style="margin: 0;">
                                <input type="hidden" name="id_evento" value="<?php echo $evento['id_evento']; ?>">
                                <input type="hidden" name="id_participante" value="<?php echo $idParticipante; ?>">
                                <button type="submit" class="btn btn-primary">
                                    📝 Pre-inscribirme
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
