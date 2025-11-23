<?php
require_once '../../config/config.php';
checkSession();

if (!hasRole('Administrador')) {
    redirect('dashboard/index.php');
}

$currentPage = 'usuarios';
$pageTitle = 'Gestión de Usuarios';

try {
    $db = Database::getInstance()->getConnection();
    
    $search = $_GET['search'] ?? '';
    $rolFilter = $_GET['rol'] ?? '';
    
    $query = "
        SELECT u.*, r.nombre_rol
        FROM usuarios u
        INNER JOIN roles r ON u.id_rol = r.id_rol
        WHERE 1=1
    ";
    
    if ($search) {
        $query .= " AND (u.nombre_completo LIKE :search OR u.email LIKE :search)";
    }
    
    if ($rolFilter) {
        $query .= " AND u.id_rol = :rol";
    }
    
    $query .= " ORDER BY u.fecha_creacion DESC";
    
    $stmt = $db->prepare($query);
    
    if ($search) $stmt->bindValue(':search', "%$search%");
    if ($rolFilter) $stmt->bindValue(':rol', $rolFilter);
    
    $stmt->execute();
    $usuarios = $stmt->fetchAll();
    
    // Get roles for filter
    $roles = $db->query("SELECT * FROM roles ORDER BY nombre_rol")->fetchAll();
    
} catch (Exception $e) {
    error_log("Users error: " . $e->getMessage());
    $usuarios = [];
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr auto auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-control" placeholder="Nombre o email..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Rol</label>
                <select name="rol" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?php echo $rol['id_rol']; ?>" <?php echo $rolFilter == $rol['id_rol'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">🔍 Buscar</button>
            <a href="crear.php" class="btn btn-success">➕ Nuevo Usuario</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Usuarios del Sistema (<?php echo count($usuarios); ?>)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--gray);">
                                No se encontraron usuarios
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $user): ?>
                            <tr>
                                <td><?php echo $user['id_usuario']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['nombre_completo']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $user['id_rol'] == 1 ? 'danger' : 
                                             ($user['id_rol'] == 2 ? 'primary' : 
                                             ($user['id_rol'] == 3 ? 'success' : 'info')); 
                                    ?>">
                                        <?php echo htmlspecialchars($user['nombre_rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['estado'] == 'activo' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($user['estado']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($user['fecha_creacion'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="editar.php?id=<?php echo $user['id_usuario']; ?>" class="btn btn-sm btn-secondary" title="Editar">✏️</a>
                                        <?php if ($user['id_usuario'] != $_SESSION['user_id']): ?>
                                            <a href="cambiar_estado.php?id=<?php echo $user['id_usuario']; ?>&estado=<?php echo $user['estado'] == 'activo' ? 'inactivo' : 'activo'; ?>" 
                                               class="btn btn-sm btn-<?php echo $user['estado'] == 'activo' ? 'danger' : 'success'; ?>" 
                                               title="<?php echo $user['estado'] == 'activo' ? 'Desactivar' : 'Activar'; ?>"
                                               onclick="return confirm('¿Está seguro?')">
                                                <?php echo $user['estado'] == 'activo' ? '🔒' : '✅'; ?>
                                            </a>
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
