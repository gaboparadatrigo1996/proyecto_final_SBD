<?php
/**
 * Global Configuration File
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('America/La_Paz');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', 'http://localhost:8080/proyectoBDv2');

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Upload configuration
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Application settings
define('APP_NAME', 'Sistema de Gestión de Eventos Académicos');
define('APP_VERSION', '1.0.0');

// Security
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 12);

// Include database connection
require_once BASE_PATH . '/config/database.php';

// Helper functions
function redirect($url) {
    header("Location: " . BASE_URL . "/" . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['last_activity']);
}

function checkSession() {
    if (!isLoggedIn()) {
        redirect('auth/login.php');
    }
    
    // Check for session timeout
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        redirect('auth/login.php?timeout=1');
    }
    
    $_SESSION['last_activity'] = time();
}

function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['role_name'] ?? '';
    
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }
    
    return $userRole === $roles;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function logAudit($userId, $action, $table = null, $recordId = null, $details = null) {
    try {
        $db = Database::getInstance()->getConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $stmt = $db->prepare("
            INSERT INTO auditoria (id_usuario, accion, tabla_afectada, id_registro_afectado, detalles, ip_origen)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $action, $table, $recordId, $details, $ip]);
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}
