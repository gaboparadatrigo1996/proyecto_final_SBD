<?php
require_once '../../config/config.php';
checkSession();

// Only for participants
if (!hasRole('Participante')) {
    redirect('dashboard/index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/participante/mis_eventos.php');
}

$idEvento = $_POST['id_evento'] ?? '';
$idParticipante = $_POST['id_participante'] ?? '';

if (empty($idEvento) || empty($idParticipante)) {
    $_SESSION['error_message'] = 'Datos incompletos';
    redirect('modules/participante/mis_eventos.php');
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Verify participant belongs to current user
    $stmt = $db->prepare("
        SELECT p.id_participante 
        FROM participantes p
        INNER JOIN usuarios u ON p.email = u.email
        WHERE u.id_usuario = ? AND p.id_participante = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $idParticipante]);
    if (!$stmt->fetch()) {
        $_SESSION['error_message'] = 'Participante no válido';
        redirect('modules/participante/mis_eventos.php');
    }
    
    // Check if event exists and is active
    $stmt = $db->prepare("SELECT * FROM eventos WHERE id_evento = ? AND estado = 'activo'");
    $stmt->execute([$idEvento]);
    $evento = $stmt->fetch();
    
    if (!$evento) {
        $_SESSION['error_message'] = 'Evento no disponible';
        redirect('modules/participante/mis_eventos.php');
    }
    
    // Check if already registered
    $stmt = $db->prepare("
        SELECT id_inscripcion 
        FROM inscripciones 
        WHERE id_evento = ? AND id_participante = ?
    ");
    $stmt->execute([$idEvento, $idParticipante]);
    if ($stmt->fetch()) {
        $_SESSION['error_message'] = 'Ya estás inscrito en este evento';
        redirect('modules/participante/mis_eventos.php');
    }
    
    // Check capacity
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM inscripciones
        WHERE id_evento = ?
    ");
    $stmt->execute([$idEvento]);
    $count = $stmt->fetch();
    
    if ($count['total'] >= $evento['capacidad_maxima']) {
        $_SESSION['error_message'] = 'El evento ha alcanzado su capacidad máxima';
        redirect('modules/participante/mis_eventos.php');
    }
    
    // Create pre-registration (status: pendiente)
    $stmt = $db->prepare("
        INSERT INTO inscripciones 
        (id_evento, id_participante, estado_inscripcion)
        VALUES (?, ?, 'pendiente')
    ");
    $stmt->execute([$idEvento, $idParticipante]);
    
    $inscripcionId = $db->lastInsertId();
    
    // Log audit
    logAudit(
        $_SESSION['user_id'], 
        'PRE_INSCRIPCION', 
        'inscripciones', 
        $inscripcionId, 
        "Pre-inscripción en evento: {$evento['nombre_evento']}"
    );
    
    $_SESSION['success_message'] = '¡Pre-inscripción exitosa! Un administrador revisará tu solicitud y te asignará los datos de pago.';
    redirect('modules/participante/mis_eventos.php');
    
} catch (Exception $e) {
    error_log("Pre-registration error: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error al procesar la pre-inscripción';
    redirect('modules/participante/mis_eventos.php');
}
