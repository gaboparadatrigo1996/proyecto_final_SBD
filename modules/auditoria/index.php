<?php
require_once '../../config/config.php';
checkSession();

if (!hasRole('Administrador')) {
    redirect('dashboard/index.php');
}

$currentPage = 'auditoria';
$pageTitle = 'Auditoría del Sistema';

try {
    $db = Database::getInstance()->getConnection();
    
    $usuario = $_GET['usuario'] ?? '';
    $accion = $_GET['accion'] ?? '';
    $fecha_desde = $_GET['fecha_desde'] ?? '';
    $fecha_hasta = $_GET['fecha_hasta'] ?? '';
    
    $query = "
        SELECT a.*, u.nombre_completo, u.email
        FROM auditoria a
        LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario
        WHERE 1=1
    ";
    
    if ($usuario) {
        $query .= " AND a.id_usuario = :usuario";
    }
    
    if ($accion) {
        $query .= " AND a.accion LIKE :accion";
    }
    
    if ($fecha_desde) {
        $query .= " AND DATE(a.fecha_hora) >= :fecha_desde";
    }
    
    if ($fecha_hasta) {
        $query .= " AND DATE(a.fecha_hora) <= :fecha_hasta";
    }
    
    $query .= " ORDER BY a.fecha_hora DESC LIMIT 500";
    
    $stmt = $db->prepare($query);
    
    if ($usuario) $stmt->bindValue(':usuario', $usuario);
    if ($accion) $stmt->bindValue(':accion', "%$accion%");
    if ($fecha_desde) $stmt->bindValue(':fecha_desde', $fecha_desde);
    if ($fecha_hasta) $stmt->bindValue(':fecha_hasta', $fecha_hasta);
    
    $stmt->execute();
    $registros = $stmt->fetchAll();
    
    // Get users for filter
    $usuarios = $db->query("SELECT id_usuario, nombre_completo FROM usuarios ORDER BY nombre_completo")->fetchAll();
    
} catch (Exception $e) {
    error_log("Audit error: " . $e->getMessage());
    $registros = [];
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: repeat(4, 1fr) auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Usuario</label>
                <select name="usuario" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo $u['id_usuario']; ?>" <?php echo $usuario == $u['id_usuario'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['nombre_completo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Acción</label>
                <input type="text" name="accion" class="form-control" placeholder="LOGIN, CREATE..." value="<?php echo htmlspecialchars($accion); ?>">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Desde</label>
                <input type="date" name="fecha_desde" class="form-control" value="<?php echo htmlspecialchars($fecha_desde); ?>">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Registros de Auditoría (<?php echo count($registros); ?>)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Tabla</th>
                        <th>Registro ID</th>
                        <th>Detalles</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--gray);">
                                No se encontraron registros
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registros as $reg): ?>
                            <tr>
                                <td><?php echo $reg['id_auditoria']; ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($reg['fecha_hora'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($reg['nombre_completo'] ?? 'Sistema'); ?></strong>
                                    <br>
                                    <small style="color: var(--gray);"><?php echo htmlspecialchars($reg['email'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo strpos($reg['accion'], 'LOGIN') !== false ? 'primary' : 
                                             (strpos($reg['accion'], 'CREATE') !== false ? 'success' : 
                                             (strpos($reg['accion'], 'DELETE') !== false ? 'danger' : 'warning')); 
                                    ?>">
                                        <?php echo htmlspecialchars($reg['accion']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($reg['tabla_afectada'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($reg['id_registro_afectado'] ?? 'N/A'); ?></td>
                                <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars($reg['detalles'] ?? ''); ?>
                                </td>
                                <td><?php echo htmlspecialchars($reg['ip_origen'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
