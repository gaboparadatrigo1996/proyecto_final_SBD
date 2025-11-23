<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'reportes';
$pageTitle = 'Reporte de Pagos';

$export = $_GET['export'] ?? '';

try {
    $db = Database::getInstance()->getConnection();
    
    $eventoFilter = $_GET['evento'] ?? '';
    $estadoFilter = $_GET['estado'] ?? '';
    $metodoFilter = $_GET['metodo'] ?? '';
    
    $query = "
        SELECT 
            pag.*,
            p.dni,
            p.nombres,
            p.apellidos,
            p.email,
            e.nombre_evento,
            u.nombre_completo as registrado_por_nombre
        FROM pagos pag
        INNER JOIN inscripciones i ON pag.id_inscripcion = i.id_inscripcion
        INNER JOIN participantes p ON i.id_participante = p.id_participante
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        LEFT JOIN usuarios u ON pag.registrado_por = u.id_usuario
        WHERE 1=1
    ";
    
    if ($eventoFilter) {
        $query .= " AND i.id_evento = :evento";
    }
    if ($estadoFilter) {
        $query .= " AND pag.estado_pago = :estado";
    }
    if ($metodoFilter) {
        $query .= " AND pag.metodo_pago = :metodo";
    }
    
    $query .= " ORDER BY pag.fecha_pago DESC";
    
    $stmt = $db->prepare($query);
    if ($eventoFilter) $stmt->bindValue(':evento', $eventoFilter);
    if ($estadoFilter) $stmt->bindValue(':estado', $estadoFilter);
    if ($metodoFilter) $stmt->bindValue(':metodo', $metodoFilter);
    
    $stmt->execute();
    $pagos = $stmt->fetchAll();
    
    // Get events for filter
    $eventos = $db->query("SELECT id_evento, nombre_evento FROM eventos ORDER BY nombre_evento")->fetchAll();
    
    // Calculate totals
    $totalAprobado = 0;
    $totalPendiente = 0;
    $totalRechazado = 0;
    
    foreach ($pagos as $pago) {
        if ($pago['estado_pago'] == 'aprobado') {
            $totalAprobado += $pago['monto'];
        } elseif ($pago['estado_pago'] == 'pendiente') {
            $totalPendiente += $pago['monto'];
        } else {
            $totalRechazado += $pago['monto'];
        }
    }
    
    // Export to Excel
    if ($export == 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="reporte_pagos_' . date('Y-m-d') . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr>
                <th>ID</th>
                <th>Participante</th>
                <th>DNI</th>
                <th>Evento</th>
                <th>Monto</th>
                <th>Fecha Pago</th>
                <th>Método</th>
                <th>Estado</th>
                <th>Registrado Por</th>
              </tr>";
        
        foreach ($pagos as $row) {
            echo "<tr>";
            echo "<td>" . $row['id_pago'] . "</td>";
            echo "<td>" . htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) . "</td>";
            echo "<td>" . htmlspecialchars($row['dni']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre_evento']) . "</td>";
            echo "<td>Bs. " . number_format($row['monto'], 2) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($row['fecha_pago'])) . "</td>";
            echo "<td>" . ucfirst($row['metodo_pago']) . "</td>";
            echo "<td>" . ucfirst($row['estado_pago']) . "</td>";
            echo "<td>" . htmlspecialchars($row['registrado_por_nombre'] ?? 'Sistema') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        exit();
    }
    
} catch (Exception $e) {
    error_log("Payments report error: " . $e->getMessage());
    $pagos = [];
}

include '../../includes/header.php';
?>

<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 2rem;">
    <div class="stat-card success">
        <div class="stat-header">
            <div>
                <div class="stat-value">Bs. <?php echo number_format($totalAprobado, 2); ?></div>
                <div class="stat-label">Total Aprobado</div>
            </div>
            <div class="stat-icon">✅</div>
        </div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div>
                <div class="stat-value">Bs. <?php echo number_format($totalPendiente, 2); ?></div>
                <div class="stat-label">Total Pendiente</div>
            </div>
            <div class="stat-icon">⏳</div>
        </div>
    </div>
    
    <div class="stat-card danger">
        <div class="stat-header">
            <div>
                <div class="stat-value">Bs. <?php echo number_format($totalRechazado, 2); ?></div>
                <div class="stat-label">Total Rechazado</div>
            </div>
            <div class="stat-icon">❌</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto auto; gap: 1rem; align-items: end;">
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
                    <option value="aprobado" <?php echo $estadoFilter == 'aprobado' ? 'selected' : ''; ?>>Aprobado</option>
                    <option value="pendiente" <?php echo $estadoFilter == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="rechazado" <?php echo $estadoFilter == 'rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Método</label>
                <select name="metodo" class="form-control">
                    <option value="">Todos</option>
                    <option value="efectivo" <?php echo $metodoFilter == 'efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                    <option value="transferencia" <?php echo $metodoFilter == 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                    <option value="qr" <?php echo $metodoFilter == 'qr' ? 'selected' : ''; ?>>QR</option>
                    <option value="tarjeta" <?php echo $metodoFilter == 'tarjeta' ? 'selected' : ''; ?>>Tarjeta</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn btn-success">
                📥 Excel
            </a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Reporte de Pagos (<?php echo count($pagos); ?> registros)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-wrapper">
            <table id="reportTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Participante</th>
                        <th>DNI</th>
                        <th>Evento</th>
                        <th>Monto</th>
                        <th>Fecha</th>
                        <th>Método</th>
                        <th>Estado</th>
                        <th>Registrado Por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pagos)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--gray);">
                                No hay pagos registrados con los filtros seleccionados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pagos as $row): ?>
                            <tr>
                                <td><?php echo $row['id_pago']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($row['dni']); ?></td>
                                <td><?php echo htmlspecialchars($row['nombre_evento']); ?></td>
                                <td><strong>Bs. <?php echo number_format($row['monto'], 2); ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($row['fecha_pago'])); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo ucfirst($row['metodo_pago']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $row['estado_pago'] == 'aprobado' ? 'success' : 
                                             ($row['estado_pago'] == 'pendiente' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($row['estado_pago']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['registrado_por_nombre'] ?? 'Sistema'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
