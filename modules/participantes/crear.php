<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'participantes';
$pageTitle = 'Registrar Participante';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = Database::getInstance()->getConnection();
        
        $dni = sanitizeInput($_POST['dni']);
        $nombres = sanitizeInput($_POST['nombres']);
        $apellidos = sanitizeInput($_POST['apellidos']);
        $email = sanitizeInput($_POST['email']);
        $telefono = sanitizeInput($_POST['telefono']);
        $institucion = sanitizeInput($_POST['institucion']);
        $tipo = $_POST['tipo_participante'];
        
        // Check if DNI or email already exists
        $stmt = $db->prepare("SELECT id_participante FROM participantes WHERE dni = ? OR email = ?");
        $stmt->execute([$dni, $email]);
        
        if ($stmt->fetch()) {
            $error = "Ya existe un participante con ese DNI o correo electrónico.";
        } else {
            $stmt = $db->prepare("
                INSERT INTO participantes (dni, nombres, apellidos, email, telefono, institucion, tipo_participante)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $dni,
                $nombres,
                $apellidos,
                $email,
                $telefono,
                $institucion,
                $tipo
            ]);
            
            $participanteId = $db->lastInsertId();
            
            logAudit($_SESSION['user_id'], 'CREATE', 'participantes', $participanteId, "Participante creado: $nombres $apellidos");
            
            redirect('modules/participantes/ver.php?id=' . $participanteId . '&success=created');
        }
        
    } catch (Exception $e) {
        error_log("Participant creation error: " . $e->getMessage());
        $error = "Error al registrar el participante.";
    }
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Registrar Nuevo Participante</h3>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">DNI / Cédula de Identidad *</label>
                    <input type="text" name="dni" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipo de Participante *</label>
                    <select name="tipo_participante" class="form-control" required>
                        <option value="estudiante">Estudiante</option>
                        <option value="profesional">Profesional</option>
                        <option value="ponente">Ponente</option>
                        <option value="invitado">Invitado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nombres *</label>
                    <input type="text" name="nombres" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Apellidos *</label>
                    <input type="text" name="apellidos" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Correo Electrónico *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" name="telefono" class="form-control" placeholder="Ej: +591 70000000">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Institución</label>
                    <input type="text" name="institucion" class="form-control" placeholder="Universidad, empresa, etc.">
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-success">💾 Guardar Participante</button>
                <a href="index.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
