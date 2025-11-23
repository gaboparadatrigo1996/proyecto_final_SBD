<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'pagos';
$pageTitle = 'Gestión de Pagos';

try {
    $db = Database::getInstance()->getConnection();
    
    $query = "
        SELECT pag.*, 
               i.id_evento,
               p.nombres, p.apellidos, p.dni,
               e.nombre_evento,
               u.nombre_completo as registrado_por_nombre
        FROM pagos pag
        INNER JOIN inscripciones i ON pag.id_inscripcion = i.id_inscripcion
        INNER JOIN participantes p ON i.id_participante = p.id_participante
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        LEFT JOIN usuarios u ON pag.registrado_por = u.id_usuario
        ORDER BY pag.fecha_pago DESC
    ";
    
    $pagos = $db->query($query)->fetchAll();
    
} catch (Exception $e) {
    error_log("Payments error: " . $e->getMessage());
    $pagos = [];
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">Pagos Registrados (<?php echo count($pagos); ?>)</h3>
        <a href="crear.php" class="btn btn-success">➕ Registrar Pago</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Participante</th>
                        <th>Evento</th>
                        <th>Monto</th>
                        <th>Fecha Pago</th>
                        <th>Método</th>
                        <th>Estado</th>
                        <th>Registrado Por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pagos)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--gray);">
                                No hay pagos registrados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pagos as $pago): ?>
                            <tr>
                                <td><?php echo $pago['id_pago']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($pago['nombres'] . ' ' . $pago['apellidos']); ?></strong>
                                    <br>
                                    <small style="color: var(--gray);"><?php echo htmlspecialchars($pago['dni']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($pago['nombre_evento']); ?></td>
                                <td><strong>Bs. <?php echo number_format($pago['monto'], 2); ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($pago['fecha_pago'])); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo ucfirst($pago['metodo_pago']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $pago['estado_pago'] == 'aprobado' ? 'success' : 
                                             ($pago['estado_pago'] == 'pendiente' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($pago['estado_pago']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($pago['registrado_por_nombre'] ?? 'N/A'); ?></small>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="ver.php?id=<?php echo $pago['id_pago']; ?>" class="btn btn-sm btn-primary" title="Ver">👁️</a>
                                        <?php if ($pago['estado_pago'] == 'pendiente'): ?>
                                            <a href="aprobar.php?id=<?php echo $pago['id_pago']; ?>" class="btn btn-sm btn-success" title="Aprobar">✓</a>
                                        <?php endif; ?>
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
