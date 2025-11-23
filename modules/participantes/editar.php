<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'participantes';
$pageTitle = 'Editar Participante';

$id = $_GET['id'] ?? null;

if (!$id) {
    redirect('modules/participantes/index.php');
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get participant data
    $stmt = $db->prepare("SELECT * FROM participantes WHERE id_participante = ?");
    $stmt->execute([$id]);
    $participante = $stmt->fetch();
    
    if (!$participante) {
        redirect('modules/participantes/index.php?error=not_found');
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $dni = sanitizeInput($_POST['dni']);
        $nombres = sanitizeInput($_POST['nombres']);
        $apellidos = sanitizeInput($_POST['apellidos']);
        $email = sanitizeInput($_POST['email']);
        $telefono = sanitizeInput($_POST['telefono']);
        $institucion = sanitizeInput($_POST['institucion']);
        $tipo = $_POST['tipo_participante'];
        
        // Check if DNI or email already exists (excluding current participant)
        $stmt = $db->prepare("
            SELECT id_participante 
            FROM participantes 
            WHERE (dni = ? OR email = ?) AND id_participante != ?
        ");
        $stmt->execute([$dni, $email, $id]);
        
        if ($stmt->fetch()) {
            $error = "Ya existe otro participante con ese DNI o correo electrónico.";
        } else {
            $stmt = $db->prepare("
                UPDATE participantes 
                SET dni = ?, 
                    nombres = ?, 
                    apellidos = ?, 
                    email = ?, 
                    telefono = ?, 
                    institucion = ?, 
                    tipo_participante = ?
                WHERE id_participante = ?
            ");
            
            $stmt->execute([
                $dni,
                $nombres,
                $apellidos,
                $email,
                $telefono,
                $institucion,
                $tipo,
                $id
            ]);
            
            logAudit($_SESSION['user_id'], 'UPDATE', 'participantes', $id, "Participante actualizado: $nombres $apellidos");
            
            redirect('modules/participantes/ver.php?id=' . $id . '&success=updated');
        }
    }
    
} catch (Exception $e) {
    error_log("Participant update error: " . $e->getMessage());
    $error = "Error al actualizar el participante.";
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Editar Participante: <?php echo htmlspecialchars($participante['nombres'] . ' ' . $participante['apellidos']); ?></h3>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">DNI / Cédula de Identidad *</label>
                    <input type="text" name="dni" class="form-control" required value="<?php echo htmlspecialchars($participante['dni']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipo de Participante *</label>
                    <select name="tipo_participante" class="form-control" required>
                        <option value="estudiante" <?php echo $participante['tipo_participante'] == 'estudiante' ? 'selected' : ''; ?>>Estudiante</option>
                        <option value="profesional" <?php echo $participante['tipo_participante'] == 'profesional' ? 'selected' : ''; ?>>Profesional</option>
                        <option value="ponente" <?php echo $participante['tipo_participante'] == 'ponente' ? 'selected' : ''; ?>>Ponente</option>
                        <option value="invitado" <?php echo $participante['tipo_participante'] == 'invitado' ? 'selected' : ''; ?>>Invitado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nombres *</label>
                    <input type="text" name="nombres" class="form-control" required value="<?php echo htmlspecialchars($participante['nombres']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Apellidos *</label>
                    <input type="text" name="apellidos" class="form-control" required value="<?php echo htmlspecialchars($participante['apellidos']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Correo Electrónico *</label>
                    <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($participante['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" name="telefono" class="form-control" value="<?php echo htmlspecialchars($participante['telefono']); ?>">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Institución</label>
                    <input type="text" name="institucion" class="form-control" value="<?php echo htmlspecialchars($participante['institucion']); ?>">
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
                <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
