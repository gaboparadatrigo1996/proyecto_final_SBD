<?php
require_once '../../config/config.php';
checkSession();

if (!hasRole('Administrador')) {
    redirect('dashboard/index.php');
}

if (!isset($_GET['id']) || !isset($_GET['estado'])) {
    redirect('modules/usuarios/index.php');
}

try {
    $db = Database::getInstance()->getConnection();
    
    $userId = (int)$_GET['id'];
    $nuevoEstado = $_GET['estado'] == 'activo' ? 'activo' : 'inactivo';
    
    // Cannot deactivate yourself
    if ($userId == $_SESSION['user_id']) {
        redirect('modules/usuarios/index.php?error=self');
    }
    
    $stmt = $db->prepare("UPDATE usuarios SET estado = ? WHERE id_usuario = ?");
    $stmt->execute([$nuevoEstado, $userId]);
    
    logAudit($_SESSION['user_id'], 'UPDATE', 'usuarios', $userId, "Estado cambiado a: $nuevoEstado");
    
    redirect('modules/usuarios/index.php?success=updated');
    
} catch (Exception $e) {
    error_log("User status change error: " . $e->getMessage());
    redirect('modules/usuarios/index.php?error=system');
}
