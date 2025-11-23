<?php
require_once '../config/config.php';

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    logAudit($userId, 'LOGOUT', 'usuarios', $userId, 'Cierre de sesión');
}

// Destroy session
session_unset();
session_destroy();

redirect('auth/login.php?logout=1');
