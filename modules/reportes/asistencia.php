<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'reportes';
$pageTitle = 'Reporte de Asistencia';

$export = $_GET['export'] ?? '';

try {
    $db = Database::getInstance()->getConnection();
    
    $eventoFilter = $_GET['evento'] ?? '';
    $sesionFilter = $_GET['sesion'] ?? '';
    
    $query = "
        SELECT 
            e.nombre_evento,
            s.nombre_sesion,
            s.fecha,
            s.hora_inicio,
            s.hora_fin,
            p.dni,
            p.nombres,
            p.apellidos,
            p.email,
            p.institucion,
            a.fecha_hora_entrada,
            a.estado
        FROM asistencias a
        INNER JOIN sesiones s ON a.id_sesion = s.id_sesion
        INNER JOIN eventos e ON s.id_evento = e.id_evento
        INNER JOIN participantes p ON a.id_participante = p.id_participante
        WHERE 1=1
    ";
    
    if ($eventoFilter) {
        $query .= " AND e.id_evento = :evento";
    }
    if ($sesionFilter) {
        $query .= " AND s.id_sesion = :sesion";
    }
    
    $query .= " ORDER BY e.nombre_evento, s.fecha, s.hora_inicio, a.fecha_hora_entrada";
    
    $stmt = $db->prepare($query);
    if ($eventoFilter) $stmt->bindValue(':evento', $eventoFilter);
    if ($sesionFilter) $stmt->bindValue(':sesion', $sesionFilter);
    
    $stmt->execute();
    $asistencias = $stmt->fetchAll();
    
    // Get events for filter
    $eventos = $db->query("SELECT id_evento, nombre_evento FROM eventos ORDER BY nombre_evento")->fetchAll();
    
    // Get sessions for filter
    $sesiones = [];
    if ($eventoFilter) {
        $stmt = $db->prepare("SELECT id_sesion, nombre_sesion, fecha FROM sesiones WHERE id_evento = ? ORDER BY fecha, hora_inicio");
        $stmt->execute([$eventoFilter]);
        $sesiones = $stmt->fetchAll();
    }
    
    // Export to Excel
    if ($export == 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="reporte_asistencia_' . date('Y-m-d') . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr>
                <th>Evento</th>
                <th>Sesión</th>
                <th>Fecha Sesión</th>
                <th>DNI</th>
                <th>Nombres</th>
                <th>Apellidos</th>
                <th>Email</th>
                <th>Institución</th>
                <th>Hora Entrada</th>
                <th>Estado</th>
              </tr>";
        
        foreach ($asistencias as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['nombre_evento']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre_sesion']) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>";
            echo "<td>" . htmlspecialchars($row['dni']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nombres']) . "</td>";
            echo "<td>" . htmlspecialchars($row['apellidos']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['institucion']) . "</td>";
            echo "<td>" . date('d/m/Y H:i:s', strtotime($row['fecha_hora_entrada'])) . "</td>";
            echo "<td>" . ucfirst($row['estado']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        exit();
    }
    
} catch (Exception $e) {
    error_log("Attendance report error: " . $e->getMessage());
    $asistencias = [];
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: 2fr 2fr auto auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Evento</label>
                <select name="evento" class="form-control" onchange="this.form.submit()">
                    <option value="">Todos los eventos</option>
                    <?php foreach ($eventos as $ev): ?>
                        <option value="<?php echo $ev['id_evento']; ?>" <?php echo $eventoFilter == $ev['id_evento'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ev['nombre_evento']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Sesión</label>
                <select name="sesion" class="form-control" <?php echo empty($sesiones) ? 'disabled' : ''; ?>>
                    <option value="">Todas las sesiones</option>
                    <?php foreach ($sesiones as $ses): ?>
                        <option value="<?php echo $ses['id_sesion']; ?>" <?php echo $sesionFilter == $ses['id_sesion'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ses['nombre_sesion']); ?> (<?php echo date('d/m/Y', strtotime($ses['fecha'])); ?>)
                        </option>
                    <?php endforeach; ?>
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
        <h3 class="card-title">Reporte de Asistencia (<?php echo count($asistencias); ?> registros)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-wrapper">
            <table id="reportTable">
                <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Sesión</th>
                        <th>Fecha</th>
                        <th>DNI</th>
                        <th>Participante</th>
                        <th>Email</th>
                        <th>Institución</th>
                        <th>Hora Entrada</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($asistencias)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--gray);">
                                No hay asistencias registradas con los filtros seleccionados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($asistencias as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nombre_evento']); ?></td>
                                <td><?php echo htmlspecialchars($row['nombre_sesion']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                                <td><?php echo htmlspecialchars($row['dni']); ?></td>
                                <td><?php echo htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['institucion'] ?? 'N/A'); ?></td>
                                <td><?php echo date('H:i:s', strtotime($row['fecha_hora_entrada'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $row['estado'] == 'presente' ? 'success' : 'warning'; ?>">
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
