<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'certificados';
$pageTitle = 'Generar Certificados';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get pending certificates using the view
    $stmt = $db->query("SELECT * FROM vista_certificados_pendientes");
    $pendientes = $stmt->fetchAll();
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $generados = 0;
        
        foreach ($pendientes as $cert) {
            // Generate unique code
            $codigo = 'CERT-' . $cert['id_inscripcion'] . '-' . time() . '-' . rand(1000, 9999);
            
            // Insert certificate
            $stmt = $db->prepare("
                INSERT INTO certificados (id_inscripcion, codigo_validacion, fecha_emision)
                VALUES (?, ?, CURDATE())
            ");
            
            if ($stmt->execute([$cert['id_inscripcion'], $codigo])) {
                $generados++;
                logAudit($_SESSION['user_id'], 'CREATE', 'certificados', $db->lastInsertId(), 
                        "Certificado generado para: " . $cert['nombres'] . ' ' . $cert['apellidos']);
            }
        }
        
        redirect('modules/certificados/index.php?success=generated&count=' . $generados);
    }
    
} catch (Exception $e) {
    error_log("Certificate generation error: " . $e->getMessage());
    $error = "Error al generar los certificados.";
}

include '../../includes/header.php';
?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Generar Certificados Pendientes</h3>
    </div>
    <div class="card-body">
        <?php if (empty($pendientes)): ?>
            <div class="alert alert-info">
                ℹ️ No hay certificados pendientes de generar.<br><br>
                <strong>Requisitos para que un certificado sea generado:</strong>
                <ul>
                    <li>Inscripción en estado "confirmada"</li>
                    <li>Asistencia ≥ 80% de las sesiones del evento</li>
                    <li>Pago aprobado</li>
                </ul>
            </div>
            <a href="index.php" class="btn btn-outline">← Volver a Certificados</a>
        <?php else: ?>
            <div class="alert alert-success">
                ✅ Se encontraron <strong><?php echo count($pendientes); ?></strong> participantes que califican para certificado.
            </div>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Participante</th>
                            <th>Email</th>
                            <th>Evento</th>
                            <th>% Asistencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendientes as $cert): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($cert['nombres'] . ' ' . $cert['apellidos']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($cert['email']); ?></td>
                                <td><?php echo htmlspecialchars($cert['nombre_evento']); ?></td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo number_format($cert['porcentaje_asistencia'], 1); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <form method="POST" style="margin-top: 2rem;">
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-success" onclick="return confirm('¿Generar certificados para <?php echo count($pendientes); ?> participante(s)?')">
                        ⚡ Generar <?php echo count($pendientes); ?> Certificado(s)
                    </button>
                    <a href="index.php" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
