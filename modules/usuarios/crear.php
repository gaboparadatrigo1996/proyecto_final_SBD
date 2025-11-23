<?php
require_once '../../config/config.php';
checkSession();

if (!hasRole('Administrador')) {
    redirect('dashboard/index.php');
}

$currentPage = 'usuarios';
$pageTitle = 'Crear Usuario';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = Database::getInstance()->getConnection();
        
        $nombre = sanitizeInput($_POST['nombre_completo']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $confirmar = $_POST['confirmar_password'];
        $idRol = (int)$_POST['id_rol'];
        
        // Validations
        if ($password !== $confirmar) {
            $error = "Las contraseñas no coinciden.";
        } elseif (strlen($password) < 6) {
            $error = "La contraseña debe tener al menos 6 caracteres.";
        } else {
            // Check if email exists
            $stmt = $db->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = "Ya existe un usuario con ese correo electrónico.";
            } else {
                // Create user
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                
                $stmt = $db->prepare("
                    INSERT INTO usuarios (nombre_completo, email, password, id_rol, estado)
                    VALUES (?, ?, ?, ?, 'activo')
                ");
                
                $stmt->execute([$nombre, $email, $hash, $idRol]);
                
                $userId = $db->lastInsertId();
                
                logAudit($_SESSION['user_id'], 'CREATE', 'usuarios', $userId, "Usuario creado: $email");
                
                redirect('modules/usuarios/index.php?success=created');
            }
        }
        
    } catch (Exception $e) {
        error_log("User creation error: " . $e->getMessage());
        $error = "Error al crear el usuario.";
    }
}

// Get roles
try {
    $db = Database::getInstance()->getConnection();
    $roles = $db->query("SELECT * FROM roles ORDER BY nombre_rol")->fetchAll();
} catch (Exception $e) {
    $roles = [];
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Crear Nuevo Usuario</h3>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="nombre_completo" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Correo Electrónico *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Rol *</label>
                    <select name="id_rol" class="form-control" required>
                        <option value="">Seleccione un rol</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo $rol['id_rol']; ?>">
                                <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contraseña *</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                    <small style="color: var(--gray);">Mínimo 6 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirmar Contraseña *</label>
                    <input type="password" name="confirmar_password" class="form-control" required minlength="6">
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-success">💾 Crear Usuario</button>
                <a href="index.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
