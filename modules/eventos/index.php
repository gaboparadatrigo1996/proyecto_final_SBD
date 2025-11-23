<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'eventos';
$pageTitle = 'Gestión de Eventos';

// Get all events
try {
    $db = Database::getInstance()->getConnection();
    
    $search = $_GET['search'] ?? '';
    $estado = $_GET['estado'] ?? '';
    
    $query = "
        SELECT e.*, u.nombre_completo as creador,
               (SELECT COUNT(*) FROM inscripciones WHERE id_evento = e.id_evento) as total_inscripciones
        FROM eventos e
        LEFT JOIN usuarios u ON e.creado_por = u.id_usuario
        WHERE 1=1
    ";
    
    if ($search) {
        $query .= " AND e.nombre_evento LIKE :search";
    }
    
    if ($estado) {
        $query .= " AND e.estado = :estado";
    }
    
    $query .= " ORDER BY e.fecha_creacion DESC";
    
    $stmt = $db->prepare($query);
    
    if ($search) {
        $stmt->bindValue(':search', "%$search%");
    }
    if ($estado) {
        $stmt->bindValue(':estado', $estado);
    }
    
    $stmt->execute();
    $eventos = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Events error: " . $e->getMessage());
    $eventos = [];
}

include '../../includes/header.php';
?>

<!-- Filters -->
<div class="card">
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: 1fr 200px 150px auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-control" placeholder="Nombre del evento..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="activo" <?php echo $estado == 'activo' ? 'selected' : ''; ?>>Activo</option>
                    <option value="cancelado" <?php echo $estado == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    <option value="finalizado" <?php echo $estado == 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">🔍 Buscar</button>
            <a href="crear.php" class="btn btn-success">➕ Nuevo Evento</a>
        </form>
    </div>
</div>

<!-- Events Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Eventos (<?php echo count($eventos); ?>)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Evento</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Lugar</th>
                        <th>Capacidad</th>
                        <th>Inscritos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($eventos)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--gray);">
                                No se encontraron eventos
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($eventos as $evento): ?>
                            <tr>
                                <td><?php echo $evento['id_evento']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($evento['nombre_evento']); ?></strong>
                                    <br>
                                    <small style="color: var(--gray);">
                                        Por: <?php echo htmlspecialchars($evento['creador']); ?>
                                    </small>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($evento['fecha_inicio'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($evento['fecha_fin'])); ?></td>
                                <td><?php echo htmlspecialchars($evento['lugar'] ?? 'No especificado'); ?></td>
                                <td><?php echo $evento['capacidad_maxima'] ?? 'Sin límite'; ?></td>
                                <td><span class="badge badge-info"><?php echo $evento['total_inscripciones']; ?></span></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $evento['estado'] == 'activo' ? 'success' : 
                                             ($evento['estado'] == 'finalizado' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($evento['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="ver.php?id=<?php echo $evento['id_evento']; ?>" class="btn btn-sm btn-primary" title="Ver detalles">👁️</a>
                                        <a href="editar.php?id=<?php echo $evento['id_evento']; ?>" class="btn btn-sm btn-secondary" title="Editar">✏️</a>
                                        <a href="sesiones.php?id=<?php echo $evento['id_evento']; ?>" class="btn btn-sm btn-success" title="Sesiones">📋</a>
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
