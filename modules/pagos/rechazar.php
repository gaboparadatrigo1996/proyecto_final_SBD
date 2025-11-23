<?php
require_once '../../config/config.php';
checkSession();

if (!hasRole(['Administrador', 'Responsable de Inscripción'])) {
    redirect('dashboard/index.php');
}

if (!isset($_GET['id'])) {
    redirect('modules/pagos/index.php');
}

try {
    $db = Database::getInstance()->getConnection();
    
    $pagoId = (int)$_GET['id'];
    
    // Get payment info
    $stmt = $db->prepare("SELECT * FROM pagos WHERE id_pago = ?");
    $stmt->execute([$pagoId]);
    $pago = $stmt->fetch();
    
    if (!$pago) {
        redirect('modules/pagos/index.php?error=notfound');
    }
    
    // Reject payment
    $stmt = $db->prepare("UPDATE pagos SET estado_pago = 'rechazado' WHERE id_pago = ?");
    $stmt->execute([$pagoId]);
    
    // Log action
    logAudit($_SESSION['user_id'], 'UPDATE', 'pagos', $pagoId, "Pago rechazado - Monto: Bs. " . $pago['monto']);
    
    redirect('modules/pagos/index.php?success=rejected');
    
} catch (Exception $e) {
    error_log("Payment rejection error: " . $e->getMessage());
    redirect('modules/pagos/index.php?error=system');
}
