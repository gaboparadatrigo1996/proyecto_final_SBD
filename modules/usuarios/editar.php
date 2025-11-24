<?php
require_once '../../config/config.php';
checkSession();

if (!hasRole('Administrador')) {
    redirect('dashboard/index.php');
}

$currentPage = 'usuarios';
$pageTitle = 'Editar Usuario';

if (!isset($_GET['id'])) {
    redirect('modules/usuarios/index.php');
}

$userId = (int)$_GET['id'];

try {
    $db = Database::getInstance()->getConnection();
    
    // Get user data
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$userId]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        redirect('modules/usuarios/index.php?error=notfound');
    }
    
    // Get roles
    $roles = $db->query("SELECT * FROM roles ORDER BY nombre_rol")->fetchAll();
    
} catch (Exception $e) {
    error_log("User edit error: " . $e->getMessage());
    redirect('modules/usuarios/index.php?error=system');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $nombre = sanitizeInput($_POST['nombre_completo']);
        $email = sanitizeInput($_POST['email']);
        $idRol = (int)$_POST['id_rol'];
        $estado = sanitizeInput($_POST['estado']);
        $password = $_POST['password'] ?? '';
        $confirmar = $_POST['confirmar_password'] ?? '';
        
        // Validations
        $error = null;
        
        // Check if email exists for another user
        $stmt = $db->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?");
        $stmt->execute([$email, $userId]);
        
        if ($stmt->fetch()) {
            $error = "Ya existe otro usuario con ese correo electrónico.";
        }
        
        // Validate password if provided
        if (!$error && !empty($password)) {
            if ($password !== $confirmar) {
                $error = "Las contraseñas no coinciden.";
            } elseif (strlen($password) < 6) {
                $error = "La contraseña debe tener al menos 6 caracteres.";
            }
        }
        
        if (!$error) {
            // Update user
            if (!empty($password)) {
                // Update with new password
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $db->prepare("
                    UPDATE usuarios 
                    SET nombre_completo = ?, email = ?, password = ?, id_rol = ?, estado = ?
                    WHERE id_usuario = ?
                ");
                $stmt->execute([$nombre, $email, $hash, $idRol, $estado, $userId]);
            } else {
                // Update without changing password
                $stmt = $db->prepare("
                    UPDATE usuarios 
                    SET nombre_completo = ?, email = ?, id_rol = ?, estado = ?
                    WHERE id_usuario = ?
                ");
                $stmt->execute([$nombre, $email, $idRol, $estado, $userId]);
            }
            
            logAudit($_SESSION['user_id'], 'UPDATE', 'usuarios', $userId, "Usuario actualizado: $email");
            
            redirect('modules/usuarios/index.php?success=updated');
        }
        
    } catch (Exception $e) {
        error_log("User update error: " . $e->getMessage());
        $error = "Error al actualizar el usuario.";
    }
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Editar Usuario: <?php echo htmlspecialchars($usuario['nombre_completo']); ?></h3>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="nombre_completo" class="form-control" required
                           value="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Correo Electrónico *</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?php echo htmlspecialchars($usuario['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Rol *</label>
                    <select name="id_rol" class="form-control" required>
                        <option value="">Seleccione un rol</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo $rol['id_rol']; ?>" 
                                    <?php echo $usuario['id_rol'] == $rol['id_rol'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Estado *</label>
                    <select name="estado" class="form-control" required>
                        <option value="activo" <?php echo $usuario['estado'] == 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo $usuario['estado'] == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nueva Contraseña</label>
                    <input type="password" name="password" class="form-control" minlength="6">
                    <small style="color: var(--gray);">Dejar en blanco para mantener la contraseña actual</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirmar Nueva Contraseña</label>
                    <input type="password" name="confirmar_password" class="form-control" minlength="6">
                </div>
            </div>
            
            <div class="alert alert-info" style="margin-top: 1.5rem;">
                <strong>ℹ️ Información del usuario:</strong><br>
                <small>
                    ID: <?php echo $usuario['id_usuario']; ?> | 
                    Creado: <?php echo date('d/m/Y H:i', strtotime($usuario['fecha_creacion'])); ?>
                    <?php if ($usuario['ultimo_acceso']): ?>
                        | Último acceso: <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])); ?>
                    <?php endif; ?>
                </small>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-success">💾 Guardar Cambios</button>
                <a href="index.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
