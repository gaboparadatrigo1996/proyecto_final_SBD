<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'reportes';
$pageTitle = 'Reporte de Participantes';

$export = $_GET['export'] ?? '';

try {
    $db = Database::getInstance()->getConnection();
    
    $tipoFilter = $_GET['tipo'] ?? '';
    $institucionFilter = $_GET['institucion'] ?? '';
    $eventoFilter = $_GET['evento'] ?? '';
    
    $query = "
        SELECT 
            p.*,
            COUNT(DISTINCT i.id_inscripcion) as total_inscripciones,
            COUNT(DISTINCT CASE WHEN i.estado_inscripcion = 'confirmada' THEN i.id_inscripcion END) as inscripciones_confirmadas,
            COUNT(DISTINCT c.id_certificado) as certificados_obtenidos,
            GROUP_CONCAT(DISTINCT e.nombre_evento SEPARATOR ', ') as eventos_inscritos
        FROM participantes p
        LEFT JOIN inscripciones i ON p.id_participante = i.id_participante
        LEFT JOIN eventos e ON i.id_evento = e.id_evento
        LEFT JOIN certificados c ON i.id_inscripcion = c.id_inscripcion
        WHERE 1=1
    ";
    
    if ($tipoFilter) {
        $query .= " AND p.tipo_participante = :tipo";
    }
    if ($institucionFilter) {
        $query .= " AND p.institucion LIKE :institucion";
    }
    if ($eventoFilter) {
        $query .= " AND i.id_evento = :evento";
    }
    
    $query .= " GROUP BY p.id_participante ORDER BY p.fecha_registro DESC";
    
    $stmt = $db->prepare($query);
    if ($tipoFilter) $stmt->bindValue(':tipo', $tipoFilter);
    if ($institucionFilter) $stmt->bindValue(':institucion', "%$institucionFilter%");
    if ($eventoFilter) $stmt->bindValue(':evento', $eventoFilter);
    
    $stmt->execute();
    $participantes = $stmt->fetchAll();
    
    // Get unique institutions for filter
    $instituciones = $db->query("
        SELECT DISTINCT institucion 
        FROM participantes 
        WHERE institucion IS NOT NULL AND institucion != '' 
        ORDER BY institucion
    ")->fetchAll();
    
    // Get events for filter
    $eventos = $db->query("SELECT id_evento, nombre_evento FROM eventos ORDER BY nombre_evento")->fetchAll();
    
    // Calculate statistics
    $totalParticipantes = count($participantes);
    $porTipo = [
        'estudiante' => 0,
        'profesional' => 0,
        'ponente' => 0,
        'invitado' => 0
    ];
    
    $porInstitucion = [];
    
    foreach ($participantes as $p) {
        if (isset($porTipo[$p['tipo_participante']])) {
            $porTipo[$p['tipo_participante']]++;
        }
        
        if (!empty($p['institucion'])) {
            if (!isset($porInstitucion[$p['institucion']])) {
                $porInstitucion[$p['institucion']] = 0;
            }
            $porInstitucion[$p['institucion']]++;
        }
    }
    
    arsort($porInstitucion);
    $topInstituciones = array_slice($porInstitucion, 0, 5, true);
    
    // Export to Excel
    if ($export == 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="reporte_participantes_' . date('Y-m-d') . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr>
                <th>ID</th>
                <th>DNI</th>
                <th>Nombres</th>
                <th>Apellidos</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Institución</th>
                <th>Tipo</th>
                <th>Inscripciones</th>
                <th>Confirmadas</th>
                <th>Certificados</th>
                <th>Fecha Registro</th>
              </tr>";
        
        foreach ($participantes as $row) {
            echo "<tr>";
            echo "<td>" . $row['id_participante'] . "</td>";
            echo "<td>" . htmlspecialchars($row['dni']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nombres']) . "</td>";
            echo "<td>" . htmlspecialchars($row['apellidos']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['telefono']) . "</td>";
            echo "<td>" . htmlspecialchars($row['institucion']) . "</td>";
            echo "<td>" . ucfirst($row['tipo_participante']) . "</td>";
            echo "<td>" . $row['total_inscripciones'] . "</td>";
            echo "<td>" . $row['inscripciones_confirmadas'] . "</td>";
            echo "<td>" . $row['certificados_obtenidos'] . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($row['fecha_registro'])) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        exit();
    }
    
} catch (Exception $e) {
    error_log("Participants report error: " . $e->getMessage());
    $participantes = [];
}

include '../../includes/header.php';
?>

<div class="stats-grid" style="grid-template-columns: repeat(5, 1fr); margin-bottom: 2rem;">
    <div class="stat-card primary">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?php echo $totalParticipantes; ?></div>
                <div class="stat-label">Total Participantes</div>
            </div>
            <div class="stat-icon">👥</div>
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

<?php if (!empty($topInstituciones)): ?>
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3 class="card-title">Top 5 Instituciones</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; gap: 0.75rem;">
            <?php foreach ($topInstituciones as $inst => $count): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--light); border-radius: 8px;">
                    <span><strong><?php echo htmlspecialchars($inst); ?></strong></span>
                    <span class="badge badge-primary"><?php echo $count; ?> participantes</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: 1fr 1.5fr 1.5fr auto auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-control">
                    <option value="">Todos los tipos</option>
                    <option value="estudiante" <?php echo $tipoFilter == 'estudiante' ? 'selected' : ''; ?>>Estudiante</option>
                    <option value="profesional" <?php echo $tipoFilter == 'profesional' ? 'selected' : ''; ?>>Profesional</option>
                    <option value="ponente" <?php echo $tipoFilter == 'ponente' ? 'selected' : ''; ?>>Ponente</option>
                    <option value="invitado" <?php echo $tipoFilter == 'invitado' ? 'selected' : ''; ?>>Invitado</option>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Institución</label>
                <input type="text" name="institucion" class="form-control" placeholder="Buscar institución..." value="<?php echo htmlspecialchars($institucionFilter); ?>">
            </div>
            
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
            
            <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn btn-success">
                📥 Excel
            </a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Participantes (<?php echo count($participantes); ?> registros)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-wrapper">
            <table id="reportTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Participante</th>
                        <th>DNI</th>
                        <th>Contacto</th>
                        <th>Institución</th>
                        <th>Tipo</th>
                        <th>Actividad</th>
                        <th>Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($participantes)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--gray);">
                                No hay participantes registrados con los filtros seleccionados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($participantes as $row): ?>
                            <tr>
                                <td><?php echo $row['id_participante']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($row['dni']); ?></td>
                                <td>
                                    <div style="font-size: 0.9rem;">
                                        📧 <?php echo htmlspecialchars($row['email']); ?>
                                        <?php if ($row['telefono']): ?>
                                            <br>📱 <?php echo htmlspecialchars($row['telefono']); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['institucion'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $row['tipo_participante'] == 'ponente' ? 'warning' : 
                                             ($row['tipo_participante'] == 'profesional' ? 'success' : 
                                             ($row['tipo_participante'] == 'invitado' ? 'secondary' : 'info')); 
                                    ?>">
                                        <?php echo ucfirst($row['tipo_participante']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem;">
                                        📝 <?php echo $row['total_inscripciones']; ?> inscripciones
                                        <br>✅ <?php echo $row['inscripciones_confirmadas']; ?> confirmadas
                                        <br>🎖️ <?php echo $row['certificados_obtenidos']; ?> certificados
                                    </div>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['fecha_registro'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
