<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'reportes';
$pageTitle = 'Reporte de Eventos';

$export = $_GET['export'] ?? '';

try {
    $db = Database::getInstance()->getConnection();
    
    $estadoFilter = $_GET['estado'] ?? '';
    $fechaDesde = $_GET['fecha_desde'] ?? '';
    $fechaHasta = $_GET['fecha_hasta'] ?? '';
    
    $query = "
        SELECT 
            e.*,
            u.nombre_completo as creador,
            COUNT(DISTINCT i.id_inscripcion) as total_inscripciones,
            COUNT(DISTINCT CASE WHEN i.estado_inscripcion = 'confirmada' THEN i.id_inscripcion END) as inscripciones_confirmadas,
            COUNT(DISTINCT CASE WHEN i.estado_inscripcion = 'pendiente' THEN i.id_inscripcion END) as inscripciones_pendientes,
            COUNT(DISTINCT s.id_sesion) as total_sesiones,
            COUNT(DISTINCT c.id_certificado) as certificados_emitidos
        FROM eventos e
        LEFT JOIN inscripciones i ON e.id_evento = i.id_evento
        LEFT JOIN usuarios u ON e.creado_por = u.id_usuario
        LEFT JOIN sesiones s ON e.id_evento = s.id_evento
        LEFT JOIN certificados c ON i.id_inscripcion = c.id_inscripcion
        WHERE 1=1
    ";
    
    if ($estadoFilter) {
        $query .= " AND e.estado = :estado";
    }
    if ($fechaDesde) {
        $query .= " AND e.fecha_inicio >= :fecha_desde";
    }
    if ($fechaHasta) {
        $query .= " AND e.fecha_fin <= :fecha_hasta";
    }
    
    $query .= " GROUP BY e.id_evento ORDER BY e.fecha_inicio DESC";
    
    $stmt = $db->prepare($query);
    if ($estadoFilter) $stmt->bindValue(':estado', $estadoFilter);
    if ($fechaDesde) $stmt->bindValue(':fecha_desde', $fechaDesde);
    if ($fechaHasta) $stmt->bindValue(':fecha_hasta', $fechaHasta);
    
    $stmt->execute();
    $eventos = $stmt->fetchAll();
    
    // Calculate statistics
    $totalEventos = count($eventos);
    $totalInscripciones = 0;
    $eventoActivo = 0;
    $eventoCancelado = 0;
    $eventoFinalizado = 0;
    
    foreach ($eventos as $evento) {
        $totalInscripciones += $evento['total_inscripciones'];
        if ($evento['estado'] == 'activo') $eventoActivo++;
        if ($evento['estado'] == 'cancelado') $eventoCancelado++;
        if ($evento['estado'] == 'finalizado') $eventoFinalizado++;
    }
    
    // Export to Excel
    if ($export == 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="reporte_eventos_' . date('Y-m-d') . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr>
                <th>ID</th>
                <th>Nombre Evento</th>
                <th>Fecha Inicio</th>
                <th>Fecha Fin</th>
                <th>Lugar</th>
                <th>Capacidad</th>
                <th>Inscripciones</th>
                <th>Confirmadas</th>
                <th>Pendientes</th>
                <th>Sesiones</th>
                <th>Certificados</th>
                <th>Estado</th>
                <th>Creado Por</th>
              </tr>";
        
        foreach ($eventos as $row) {
            echo "<tr>";
            echo "<td>" . $row['id_evento'] . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre_evento']) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($row['fecha_inicio'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($row['fecha_fin'])) . "</td>";
            echo "<td>" . htmlspecialchars($row['lugar']) . "</td>";
            echo "<td>" . $row['capacidad_maxima'] . "</td>";
            echo "<td>" . $row['total_inscripciones'] . "</td>";
            echo "<td>" . $row['inscripciones_confirmadas'] . "</td>";
            echo "<td>" . $row['inscripciones_pendientes'] . "</td>";
            echo "<td>" . $row['total_sesiones'] . "</td>";
            echo "<td>" . $row['certificados_emitidos'] . "</td>";
            echo "<td>" . ucfirst($row['estado']) . "</td>";
            echo "<td>" . htmlspecialchars($row['creador']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        exit();
    }
    
} catch (Exception $e) {
    error_log("Events report error: " . $e->getMessage());
    $eventos = [];
}

include '../../includes/header.php';
?>

<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 2rem;">
    <div class="stat-card primary">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $totalEventos; ?></div>
                <div class="stat-label">Total Eventos</div>
            </div>
            <div class="stat-icon">📅</div>
        </div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $eventoActivo; ?></div>
                <div class="stat-label">Activos</div>
            </div>
            <div class="stat-icon">✅</div>
        </div>
    </div>
    
    <div class="stat-card info">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $eventoFinalizado; ?></div>
                <div class="stat-label">Finalizados</div>
            </div>
            <div class="stat-icon">🏁</div>
        </div>
    </div>
    
    <div class="stat-card danger">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $eventoCancelado; ?></div>
                <div class="stat-label">Cancelados</div>
            </div>
            <div class="stat-icon">❌</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: 1.5fr 1fr 1fr auto auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="activo" <?php echo $estadoFilter == 'activo' ? 'selected' : ''; ?>>Activo</option>
                    <option value="finalizado" <?php echo $estadoFilter == 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                    <option value="cancelado" <?php echo $estadoFilter == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Desde</label>
                <input type="date" name="fecha_desde" class="form-control" value="<?php echo $fechaDesde; ?>">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $fechaHasta; ?>">
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
        <h3 class="card-title">Reporte de Eventos (<?php echo count($eventos); ?> registros)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-wrapper">
            <table id="reportTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Evento</th>
                        <th>Fechas</th>
                        <th>Lugar</th>
                        <th>Capacidad</th>
                        <th>Inscripciones</th>
                        <th>Sesiones</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($eventos)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--gray);">
                                No hay eventos registrados con los filtros seleccionados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($eventos as $row): ?>
                            <tr>
                                <td><?php echo $row['id_evento']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['nombre_evento']); ?></strong>
                                    <?php if ($row['descripcion']): ?>
                                        <br><small style="color: var(--gray);"><?php echo htmlspecialchars(substr($row['descripcion'], 0, 60)) . '...'; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo date('d/m/Y', strtotime($row['fecha_inicio'])); ?></strong>
                                    <?php if ($row['fecha_inicio'] != $row['fecha_fin']): ?>
                                        <br><small style="color: var(--gray);">hasta <?php echo date('d/m/Y', strtotime($row['fecha_fin'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['lugar']); ?></td>
                                <td>
                                    <?php 
                                    $porcentaje = $row['capacidad_maxima'] > 0 ? 
                                        ($row['total_inscripciones'] / $row['capacidad_maxima']) * 100 : 0;
                                    $color = $porcentaje >= 90 ? 'danger' : ($porcentaje >= 70 ? 'warning' : 'success');
                                    ?>
                                    <strong><?php echo $row['total_inscripciones']; ?></strong> / <?php echo $row['capacidad_maxima']; ?>
                                    <br><small class="badge badge-<?php echo $color; ?>"><?php echo round($porcentaje); ?>%</small>
                                </td>
                                <td>
                                    <div style="font-size: 0.9rem;">
                                        ✅ <strong><?php echo $row['inscripciones_confirmadas']; ?></strong> Confirmadas
                                        <br>⏳ <?php echo $row['inscripciones_pendientes']; ?> Pendientes
                                        <br>🎖️ <?php echo $row['certificados_emitidos']; ?> Certificados
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $row['total_sesiones']; ?> sesiones
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $row['estado'] == 'activo' ? 'success' : 
                                             ($row['estado'] == 'finalizado' ? 'info' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($row['estado']); ?>
                                    </span>
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
