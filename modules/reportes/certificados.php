<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'reportes';
$pageTitle = 'Reporte de Certificados';

$export = $_GET['export'] ?? '';

try {
    $db = Database::getInstance()->getConnection();
    
    $eventoFilter = $_GET['evento'] ?? '';
    $fechaDesde = $_GET['fecha_desde'] ?? '';
    $fechaHasta = $_GET['fecha_hasta'] ?? '';
    
    $query = "
        SELECT 
            c.*,
            p.dni,
            p.nombres,
            p.apellidos,
            p.email,
            p.tipo_participante,
            e.nombre_evento,
            e.fecha_inicio,
            e.fecha_fin
        FROM certificados c
        INNER JOIN inscripciones i ON c.id_inscripcion = i.id_inscripcion
        INNER JOIN participantes p ON i.id_participante = p.id_participante
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        WHERE 1=1
    ";
    
    if ($eventoFilter) {
        $query .= " AND i.id_evento = :evento";
    }
    if ($fechaDesde) {
        $query .= " AND c.fecha_emision >= :fecha_desde";
    }
    if ($fechaHasta) {
        $query .= " AND c.fecha_emision <= :fecha_hasta";
    }
    
    $query .= " ORDER BY c.fecha_emision DESC";
    
    $stmt = $db->prepare($query);
    if ($eventoFilter) $stmt->bindValue(':evento', $eventoFilter);
    if ($fechaDesde) $stmt->bindValue(':fecha_desde', $fechaDesde);
    if ($fechaHasta) $stmt->bindValue(':fecha_hasta', $fechaHasta);
    
    $stmt->execute();
    $certificados = $stmt->fetchAll();
    
    // Get events for filter
    $eventos = $db->query("SELECT id_evento, nombre_evento FROM eventos ORDER BY nombre_evento")->fetchAll();
    
    // Calculate statistics
    $totalCertificados = count($certificados);
    $porTipo = [
        'estudiante' => 0,
        'profesional' => 0,
        'ponente' => 0,
        'invitado' => 0
    ];
    
    foreach ($certificados as $cert) {
        if (isset($porTipo[$cert['tipo_participante']])) {
            $porTipo[$cert['tipo_participante']]++;
        }
    }
    
    // Export to Excel
    if ($export == 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="reporte_certificados_' . date('Y-m-d') . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr>
                <th>Código</th>
                <th>Participante</th>
                <th>DNI</th>
                <th>Email</th>
                <th>Tipo</th>
                <th>Evento</th>
                <th>Fecha Emisión</th>
                <th>Archivo</th>
              </tr>";
        
        foreach ($certificados as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['codigo_validacion']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) . "</td>";
            echo "<td>" . htmlspecialchars($row['dni']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . ucfirst($row['tipo_participante']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre_evento']) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($row['fecha_emision'])) . "</td>";
            echo "<td>" . ($row['archivo_url'] ? 'Disponible' : 'N/A') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        exit();
    }
    
} catch (Exception $e) {
    error_log("Certificates report error: " . $e->getMessage());
    $certificados = [];
}

include '../../includes/header.php';
?>

<div class="stats-grid" style="grid-template-columns: repeat(5, 1fr); margin-bottom: 2rem;">
    <div class="stat-card primary">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $totalCertificados; ?></div>
                <div class="stat-label">Total Certificados</div>
            </div>
            <div class="stat-icon">🎖️</div>
        </div>
    </div>
    
    <div class="stat-card info">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $porTipo['estudiante']; ?></div>
                <div class="stat-label">Estudiantes</div>
            </div>
            <div class="stat-icon">🎓</div>
        </div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $porTipo['profesional']; ?></div>
                <div class="stat-label">Profesionales</div>
            </div>
            <div class="stat-icon">💼</div>
        </div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $porTipo['ponente']; ?></div>
                <div class="stat-label">Ponentes</div>
            </div>
            <div class="stat-icon">🎤</div>
        </div>
    </div>
    
    <div class="stat-card secondary">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $porTipo['invitado']; ?></div>
                <div class="stat-label">Invitados</div>
            </div>
            <div class="stat-icon">🌟</div>
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
        <h3 class="card-title">Certificados Emitidos (<?php echo count($certificados); ?> registros)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-wrapper">
            <table id="reportTable">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Participante</th>
                        <th>DNI</th>
                        <th>Tipo</th>
                        <th>Evento</th>
                        <th>Fecha Evento</th>
                        <th>Fecha Emisión</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($certificados)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--gray);">
                                No hay certificados emitidos con los filtros seleccionados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($certificados as $row): ?>
                            <tr>
                                <td>
                                    <strong style="font-family: monospace; color: var(--primary);">
                                        <?php echo htmlspecialchars($row['codigo_validacion']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']); ?></strong>
                                    <br><small style="color: var(--gray);"><?php echo htmlspecialchars($row['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['dni']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $row['tipo_participante'] == 'ponente' ? 'warning' : 
                                             ($row['tipo_participante'] == 'profesional' ? 'success' : 
                                             ($row['tipo_participante'] == 'invitado' ? 'secondary' : 'info')); 
                                    ?>">
                                        <?php echo ucfirst($row['tipo_participante']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['nombre_evento']); ?></td>
                                <td>
                                    <?php 
                                    echo date('d/m/Y', strtotime($row['fecha_inicio']));
                                    if ($row['fecha_inicio'] != $row['fecha_fin']) {
                                        echo ' - ' . date('d/m/Y', strtotime($row['fecha_fin']));
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['fecha_emision'])); ?></td>
                                <td>
                                    <?php if ($row['archivo_url']): ?>
                                        <a href="<?php echo htmlspecialchars($row['archivo_url']); ?>" target="_blank" class="btn btn-sm btn-outline">
                                            📄 Ver
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--gray); font-size: 0.85rem;">N/A</span>
                                    <?php endif; ?>
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
