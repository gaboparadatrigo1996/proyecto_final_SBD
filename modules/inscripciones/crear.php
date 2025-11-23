<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'inscripciones';
$pageTitle = 'Nueva Inscripción';

// Get events and participants
try {
    $db = Database::getInstance()->getConnection();
    
    $eventos = $db->query("SELECT * FROM eventos WHERE estado = 'activo' ORDER BY nombre_evento")->fetchAll();
    $participantes = $db->query("SELECT * FROM participantes ORDER BY apellidos, nombres")->fetchAll();
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $eventoId = (int)$_POST['evento_id'];
        $participanteId = (int)$_POST['participante_id'];
        
        // Check if already registered
        $stmt = $db->prepare("SELECT id_inscripcion FROM inscripciones WHERE id_evento = ? AND id_participante = ?");
        $stmt->execute([$eventoId, $participanteId]);
        
        if ($stmt->fetch()) {
            $error = "Este participante ya está inscrito en el evento seleccionado.";
        } else {
            // Check capacity
            $stmt = $db->prepare("
                SELECT e.capacidad_maxima, COUNT(i.id_inscripcion) as inscritos
                FROM eventos e
                LEFT JOIN inscripciones i ON e.id_evento = i.id_evento AND i.estado_inscripcion != 'cancelada'
                WHERE e.id_evento = ?
                GROUP BY e.id_evento
            ");
            $stmt->execute([$eventoId]);
            $evento = $stmt->fetch();
            
            if ($evento['capacidad_maxima'] && $evento['inscritos'] >= $evento['capacidad_maxima']) {
                $error = "El evento ha alcanzado su capacidad máxima.";
            } else {
                // Create inscription
                $stmt = $db->prepare("
                    INSERT INTO inscripciones (id_evento, id_participante, estado_inscripcion)
                    VALUES (?, ?, 'confirmada')
                ");
                $stmt->execute([$eventoId, $participanteId]);
                
                $inscripcionId = $db->lastInsertId();
                
                logAudit($_SESSION['user_id'], 'CREATE', 'inscripciones', $inscripcionId, "Inscripción creada");
                
                redirect('modules/inscripciones/ver.php?id=' . $inscripcionId . '&success=created');
            }
        }
    }
    
} catch (Exception $e) {
    error_log("Inscription creation error: " . $e->getMessage());
    $error = "Error al crear la inscripción.";
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Registrar Nueva Inscripción</h3>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Evento *</label>
                <select name="evento_id" class="form-control" required>
                    <option value="">Seleccione un evento</option>
                    <?php foreach ($eventos as $ev): ?>
                        <option value="<?php echo $ev['id_evento']; ?>">
                            <?php echo htmlspecialchars($ev['nombre_evento']); ?> 
                            (<?php echo date('d/m/Y', strtotime($ev['fecha_inicio'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Participante *</label>
                <select name="participante_id" class="form-control" required>
                    <option value="">Seleccione un participante</option>
                    <?php foreach ($participantes as $part): ?>
                        <option value="<?php echo $part['id_participante']; ?>">
                            <?php echo htmlspecialchars($part['apellidos'] . ', ' . $part['nombres']); ?> 
                            (<?php echo htmlspecialchars($part['dni']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: var(--gray);">
                    ¿No encuentra al participante? 
                    <a href="../participantes/crear.php" target="_blank">Registrar nuevo participante</a>
                </small>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-success">💾 Guardar Inscripción</button>
                <a href="index.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
