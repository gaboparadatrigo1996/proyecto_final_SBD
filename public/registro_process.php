<?php
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('public/registro.php');
}

// Get form data
$nombres = sanitizeInput($_POST['nombres'] ?? '');
$apellidos = sanitizeInput($_POST['apellidos'] ?? '');
$dni = sanitizeInput($_POST['dni'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$telefono = sanitizeInput($_POST['telefono'] ?? '');
$institucion = sanitizeInput($_POST['institucion'] ?? '');
$tipo_participante = $_POST['tipo_participante'] ?? '';
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// Validate required fields
if (empty($nombres) || empty($apellidos) || empty($dni) || empty($email) || empty($tipo_participante) || empty($password)) {
    redirect('public/registro.php?error=missing_fields');
}

// Validate password match
if ($password !== $password_confirm) {
    redirect('public/registro.php?error=password_mismatch');
}

// Validate password length
if (strlen($password) < 6) {
    redirect('public/registro.php?error=password_short');
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if email already exists in participantes
    $stmt = $db->prepare("SELECT id_participante FROM participantes WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        redirect('public/registro.php?error=email_exists');
    }
    
    // Check if DNI already exists
    $stmt = $db->prepare("SELECT id_participante FROM participantes WHERE dni = ?");
    $stmt->execute([$dni]);
    if ($stmt->fetch()) {
        redirect('public/registro.php?error=dni_exists');
    }
    
    // Check if email already exists in usuarios
    $stmt = $db->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        redirect('public/registro.php?error=email_exists');
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Insert into participantes table
    $stmt = $db->prepare("
        INSERT INTO participantes 
        (dni, nombres, apellidos, email, telefono, institucion, tipo_participante)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $dni,
        $nombres,
        $apellidos,
        $email,
        $telefono,
        $institucion,
        $tipo_participante
    ]);
    
    $participanteId = $db->lastInsertId();
    
    // Create user account with Participante role (id_rol = 4)
    $hashedPassword = password_hash($password, HASH_ALGO, ['cost' => HASH_COST]);
    
    $stmt = $db->prepare("
        INSERT INTO usuarios 
        (nombre_completo, email, password, id_rol, estado)
        VALUES (?, ?, ?, 4, 'activo')
    ");
    
    $nombreCompleto = $nombres . ' ' . $apellidos;
    $stmt->execute([$nombreCompleto, $email, $hashedPassword]);
    
    $usuarioId = $db->lastInsertId();
    
    // Commit transaction
    $db->commit();
    
    // Log audit
    logAudit($usuarioId, 'USER_REGISTERED', 'usuarios', $usuarioId, "Nuevo participante registrado: $nombreCompleto");
    
    // Redirect to success
    redirect('public/registro.php?success=1');
    
} catch (Exception $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Registration error: " . $e->getMessage());
    redirect('public/registro.php?error=system');
}
