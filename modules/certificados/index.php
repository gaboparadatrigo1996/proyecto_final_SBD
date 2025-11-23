<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'certificados';
$pageTitle = 'Gestión de Certificados';

try {
    $db = Database::getInstance()->getConnection();
    
    $eventoFilter = $_GET['evento'] ?? '';
    
    $query = "
        SELECT c.*, 
               i.id_evento,
               p.nombres, p.apellidos, p.dni, p.email,
               e.nombre_evento
        FROM certificados c
        INNER JOIN inscripciones i ON c.id_inscripcion = i.id_inscripcion
        INNER JOIN participantes p ON i.id_participante = p.id_participante
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        WHERE 1=1
    ";
    
    if ($eventoFilter) {
        $query .= " AND i.id_evento = :evento";
    }
    
    $query .= " ORDER BY c.fecha_emision DESC";
    
    $stmt = $db->prepare($query);
    if ($eventoFilter) $stmt->bindValue(':evento', $eventoFilter);
    
    $stmt->execute();
    $certificados = $stmt->fetchAll();
    
    // Get events for filter
    $eventos = $db->query("SELECT id_evento, nombre_evento FROM eventos ORDER BY nombre_evento")->fetchAll();
    
    // Get pending certificates count
    $pendientes = $db->query("SELECT COUNT(*) as total FROM vista_certificados_pendientes")->fetch()['total'];
    
} catch (Exception $e) {
    error_log("Certificates error: " . $e->getMessage());
    $certificados = [];
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: 2fr auto auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Filtrar por Evento</label>
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
            
            <?php if ($pendientes > 0): ?>
                <a href="generar.php" class="btn btn-success">
                    ⚡ Generar Certificados Pendientes (<?php echo $pendientes; ?>)
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Certificados Emitidos (<?php echo count($certificados); ?>)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Participante</th>
                        <th>DNI</th>
                        <th>Evento</th>
                        <th>Fecha Emisión</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($certificados)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--gray);">
                                No hay certificados emitidos
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($certificados as $cert): ?>
                            <tr>
                                <td><?php echo $cert['id_certificado']; ?></td>
                                <td>
                                    <code style="background: var(--light); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">
                                        <?php echo htmlspecialchars($cert['codigo_validacion']); ?>
                                    </code>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($cert['nombres'] . ' ' . $cert['apellidos']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($cert['dni']); ?></td>
                                <td><?php echo htmlspecialchars($cert['nombre_evento']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($cert['fecha_emision'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="descargar.php?id=<?php echo $cert['id_certificado']; ?>" class="btn btn-sm btn-primary" title="Descargar PDF">
                                            📄 PDF
                                        </a>
                                        <a href="validar.php?codigo=<?php echo urlencode($cert['codigo_validacion']); ?>" class="btn btn-sm btn-success" title="Validar" target="_blank">
                                            ✓ Validar
                                        </a>
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

<!-- Pending Certificates View -->
<?php if ($pendientes > 0): ?>
    <div class="alert alert-info">
        <strong>ℹ️ Certificados Pendientes:</strong> 
        Hay <?php echo $pendientes; ?> participante(s) que califican para certificado. 
        <a href="generar.php">Generar ahora</a>
    </div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
