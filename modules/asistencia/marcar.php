<?php
require_once '../../config/config.php';
checkSession();

if (!isset($_GET['sesion']) || !isset($_GET['participante'])) {
    redirect('modules/asistencia/index.php');
}

try {
    $db = Database::getInstance()->getConnection();
    
    $sesionId = (int)$_GET['sesion'];
    $participanteId = (int)$_GET['participante'];
    
    // Check if already marked
    $stmt = $db->prepare("SELECT id_asistencia FROM asistencias WHERE id_sesion = ? AND id_participante = ?");
    $stmt->execute([$sesionId, $participanteId]);
    
    if ($stmt->fetch()) {
        redirect('modules/asistencia/index.php?evento=' . ($_GET['evento'] ?? '') . '&sesion=' . $sesionId . '&error=duplicate');
    }
    
    // Mark attendance
    $stmt = $db->prepare("
        INSERT INTO asistencias (id_sesion, id_participante, estado)
        VALUES (?, ?, 'presente')
    ");
    $stmt->execute([$sesionId, $participanteId]);
    
    $asistenciaId = $db->lastInsertId();
    
    logAudit($_SESSION['user_id'], 'CREATE', 'asistencias', $asistenciaId, "Asistencia registrada");
    
    redirect('modules/asistencia/index.php?evento=' . ($_GET['evento'] ?? '') . '&sesion=' . $sesionId . '&success=marked');
    
} catch (Exception $e) {
    error_log("Attendance marking error: " . $e->getMessage());
    redirect('modules/asistencia/index.php?error=system');
}
