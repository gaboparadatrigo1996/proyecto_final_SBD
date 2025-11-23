<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'participantes';
$pageTitle = 'Gestión de Participantes';

try {
    $db = Database::getInstance()->getConnection();
    
    $search = $_GET['search'] ?? '';
    $tipo = $_GET['tipo'] ?? '';
    
    $query = "
        SELECT p.*,
               fn_total_eventos_participante(p.id_participante) as eventos_inscritos
        FROM participantes p
        WHERE 1=1
    ";
    
    if ($search) {
        $query .= " AND (p.nombres LIKE :search OR p.apellidos LIKE :search OR p.dni LIKE :search OR p.email LIKE :search)";
    }
    
    if ($tipo) {
        $query .= " AND p.tipo_participante = :tipo";
    }
    
    $query .= " ORDER BY p.apellidos, p.nombres";
    
    $stmt = $db->prepare($query);
    
    if ($search) $stmt->bindValue(':search', "%$search%");
    if ($tipo) $stmt->bindValue(':tipo', $tipo);
    
    $stmt->execute();
    $participantes = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Participants error: " . $e->getMessage());
    $participantes = [];
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr auto auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-control" placeholder="Nombre, DNI, email..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-control">
                    <option value="">Todos</option>
                    <option value="estudiante" <?php echo $tipo == 'estudiante' ? 'selected' : ''; ?>>Estudiante</option>
                    <option value="profesional" <?php echo $tipo == 'profesional' ? 'selected' : ''; ?>>Profesional</option>
                    <option value="ponente" <?php echo $tipo == 'ponente' ? 'selected' : ''; ?>>Ponente</option>
                    <option value="invitado" <?php echo $tipo == 'invitado' ? 'selected' : ''; ?>>Invitado</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">🔍 Buscar</button>
            <a href="crear.php" class="btn btn-success">➕ Nuevo Participante</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Participantes Registrados (<?php echo count($participantes); ?>)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Participante</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Institución</th>
                        <th>Tipo</th>
                        <th>Eventos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($participantes)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--gray);">
                                No se encontraron participantes
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($participantes as $part): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($part['dni']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($part['nombres'] . ' ' . $part['apellidos']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($part['email']); ?></td>
                                <td><?php echo htmlspecialchars($part['telefono'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($part['institucion'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo ucfirst($part['tipo_participante']); ?>
                                    </span>
                                </td>
                                <td><span class="badge badge-primary"><?php echo $part['eventos_inscritos']; ?></span></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="ver.php?id=<?php echo $part['id_participante']; ?>" class="btn btn-sm btn-primary" title="Ver">👁️</a>
                                        <a href="editar.php?id=<?php echo $part['id_participante']; ?>" class="btn btn-sm btn-secondary" title="Editar">✏️</a>
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
