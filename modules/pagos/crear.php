<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'pagos';
$pageTitle = 'Registrar Pago';

// Get inscriptions without payments or with pending payments
try {
    $db = Database::getInstance()->getConnection();
    
    $inscripcionPreselect = $_GET['inscripcion'] ?? '';
    
    $inscripciones = $db->query("
        SELECT i.id_inscripcion, 
               p.nombres, p.apellidos, p.dni,
               e.nombre_evento,
               e.fecha_inicio
        FROM inscripciones i
        INNER JOIN participantes p ON i.id_participante = p.id_participante
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        WHERE i.estado_inscripcion = 'confirmada'
        ORDER BY e.fecha_inicio DESC, p.apellidos
    ")->fetchAll();
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $inscripcionId = (int)$_POST['inscripcion_id'];
        $monto = (float)$_POST['monto'];
        $fecha = $_POST['fecha_pago'];
        $metodo = $_POST['metodo_pago'];
        
        if ($monto <= 0) {
            $error = "El monto debe ser mayor a cero.";
        } else {
            $stmt = $db->prepare("
                INSERT INTO pagos (id_inscripcion, monto, fecha_pago, metodo_pago, estado_pago, registrado_por)
                VALUES (?, ?, ?, ?, 'pendiente', ?)
            ");
            
            $stmt->execute([
                $inscripcionId,
                $monto,
                $fecha,
                $metodo,
                $_SESSION['user_id']
            ]);
            
            $pagoId = $db->lastInsertId();
            
            logAudit($_SESSION['user_id'], 'CREATE', 'pagos', $pagoId, "Pago registrado: Bs. $monto");
            
            redirect('modules/pagos/index.php?success=created');
        }
    }
    
} catch (Exception $e) {
    error_log("Payment creation error: " . $e->getMessage());
    $error = "Error al registrar el pago.";
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Registrar Nuevo Pago</h3>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Inscripción *</label>
                    <select name="inscripcion_id" class="form-control" required>
                        <option value="">Seleccione una inscripción</option>
                        <?php foreach ($inscripciones as $insc): ?>
                            <option value="<?php echo $insc['id_inscripcion']; ?>" <?php echo $inscripcionPreselect == $insc['id_inscripcion'] ? 'selected' : ''; ?>>
                                [<?php echo $insc['dni']; ?>] <?php echo htmlspecialchars($insc['nombres'] . ' ' . $insc['apellidos']); ?> 
                                - <?php echo htmlspecialchars($insc['nombre_evento']); ?>
                                (<?php echo date('d/m/Y', strtotime($insc['fecha_inicio'])); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Monto (Bs.) *</label>
                    <input type="number" step="0.01" name="monto" class="form-control" required placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Fecha de Pago *</label>
                    <input type="date" name="fecha_pago" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Método de Pago *</label>
                    <select name="metodo_pago" class="form-control" required>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia Bancaria</option>
                        <option value="qr">Código QR</option>
                        <option value="tarjeta">Tarjeta de Crédito/Débito</option>
                    </select>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-success">💾 Registrar Pago</button>
                <a href="index.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
        
        <div style="margin-top: 2rem; padding: 1rem; background: var(--light); border-radius: 8px; border-left: 4px solid var(--info);">
            <strong>ℹ️ Nota:</strong> Los pagos se registran con estado "Pendiente" y deben ser aprobados manualmente por un administrador.
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
