<?php
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('auth/login.php');
}

$email = sanitizeInput($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    redirect('auth/login.php?error=invalid');
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Query to get user with role information
    $stmt = $db->prepare("
        SELECT u.id_usuario, u.nombre_completo, u.email, u.password, 
               u.estado, u.id_rol, r.nombre_rol
        FROM usuarios u
        INNER JOIN roles r ON u.id_rol = r.id_rol
        WHERE u.email = ?
    ");
    
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // User not found
        logAudit(null, 'LOGIN_FAILED', 'usuarios', null, "Email no encontrado: $email");
        redirect('auth/login.php?error=invalid');
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Invalid password
        logAudit($user['id_usuario'], 'LOGIN_FAILED', 'usuarios', $user['id_usuario'], 'Contraseña incorrecta');
        redirect('auth/login.php?error=invalid');
    }
    
    // Check if account is active
    if ($user['estado'] !== 'activo') {
        logAudit($user['id_usuario'], 'LOGIN_FAILED', 'usuarios', $user['id_usuario'], 'Cuenta inactiva');
        redirect('auth/login.php?error=inactive');
    }
    
    // Successful login - Set session variables
    $_SESSION['user_id'] = $user['id_usuario'];
    $_SESSION['user_name'] = $user['nombre_completo'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role_id'] = $user['id_rol'];
    $_SESSION['role_name'] = $user['nombre_rol'];
    $_SESSION['last_activity'] = time();
    
    // Log successful login
    logAudit($user['id_usuario'], 'LOGIN_SUCCESS', 'usuarios', $user['id_usuario'], 'Login exitoso');
    
    // Redirect to dashboard
    redirect('dashboard/index.php');
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    redirect('auth/login.php?error=system');
}
