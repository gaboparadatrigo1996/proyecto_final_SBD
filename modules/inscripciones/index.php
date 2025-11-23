<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'inscripciones';
$pageTitle = 'Gestión de Inscripciones';

// Get inscriptions with filters
try {
    $db = Database::getInstance()->getConnection();
    
    $eventoFilter = $_GET['evento'] ?? '';
    $estadoFilter = $_GET['estado'] ?? '';
    
    $query = "
        SELECT i.*, 
               p.nombres, p.apellidos, p.email, p.dni,
               e.nombre_evento,
               pag.estado_pago
        FROM inscripciones i
        INNER JOIN participantes p ON i.id_participante = p.id_participante
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        LEFT JOIN pagos pag ON i.id_inscripcion = pag.id_inscripcion
        WHERE 1=1
    ";
    
    if ($eventoFilter) {
        $query .= " AND i.id_evento = :evento";
    }
    if ($estadoFilter) {
        $query .= " AND i.estado_inscripcion = :estado";
    }
    
    $query .= " ORDER BY i.fecha_inscripcion DESC";
    
    $stmt = $db->prepare($query);
    if ($eventoFilter) $stmt->bindValue(':evento', $eventoFilter);
    if ($estadoFilter) $stmt->bindValue(':estado', $estadoFilter);
    
    $stmt->execute();
    $inscripciones = $stmt->fetchAll();
    
    // Get events for filter
    $eventos = $db->query("SELECT id_evento, nombre_evento FROM eventos ORDER BY nombre_evento")->fetchAll();
    
} catch (Exception $e) {
    error_log("Inscriptions error: " . $e->getMessage());
    $inscripciones = [];
    $eventos = [];
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Evento</label>
                <select name="evento" class="form-control">
                    <option value="">Todos los eventos</option>
                    <?php foreach ($eventos as $ev): ?>
                        <option value="<?php echo $ev['id_evento']; ?>" <?php echo $eventoFilter == $ev['id_evento'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ev['nombre_evento']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="pendiente" <?php echo $estadoFilter == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="confirmada" <?php echo $estadoFilter == 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                    <option value="cancelada" <?php echo $estadoFilter == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
            <a href="crear.php" class="btn btn-success">➕ Nueva Inscripción</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Inscripciones (<?php echo count($inscripciones); ?>)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Participante</th>
                        <th>DNI</th>
                        <th>Evento</th>
                        <th>Fecha Inscripción</th>
                        <th>Estado Inscripción</th>
                        <th>Estado Pago</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inscripciones)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--gray);">
                                No se encontraron inscripciones
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inscripciones as $insc): ?>
                            <tr>
                                <td><?php echo $insc['id_inscripcion']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($insc['nombres'] . ' ' . $insc['apellidos']); ?></strong>
                                    <br>
                                    <small style="color: var(--gray);"><?php echo htmlspecialchars($insc['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($insc['dni']); ?></td>
                                <td><?php echo htmlspecialchars($insc['nombre_evento']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($insc['fecha_inscripcion'])); ?></td>
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
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="ver.php?id=<?php echo $insc['id_inscripcion']; ?>" class="btn btn-sm btn-primary" title="Ver">👁️</a>
                                        <a href="../pagos/crear.php?inscripcion=<?php echo $insc['id_inscripcion']; ?>" class="btn btn-sm btn-success" title="Registrar pago">💳</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
