<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'asistencia';
$pageTitle = 'Control de Asistencia';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get events with sessions
    $eventos = $db->query("
        SELECT e.*, 
               (SELECT COUNT(*) FROM sesiones WHERE id_evento = e.id_evento) as total_sesiones
        FROM eventos e 
        WHERE e.estado = 'activo' 
        ORDER BY e.fecha_inicio DESC
    ")->fetchAll();
    
    $selectedEvento = $_GET['evento'] ?? '';
    $selectedSesion = $_GET['sesion'] ?? '';
    
    $sesiones = [];
    $asistencias = [];
    
    if ($selectedEvento) {
        $stmt = $db->prepare("SELECT * FROM sesiones WHERE id_evento = ? ORDER BY fecha, hora_inicio");
        $stmt->execute([$selectedEvento]);
        $sesiones = $stmt->fetchAll();
    }
    
    if ($selectedSesion) {
        // Get attendance for this session
        $stmt = $db->prepare("
            SELECT a.*, 
                   p.nombres, p.apellidos, p.dni, p.email,
                   s.nombre_sesion, s.fecha, s.hora_inicio
            FROM asistencias a
            INNER JOIN participantes p ON a.id_participante = p.id_participante
            INNER JOIN sesiones s ON a.id_sesion = s.id_sesion
            WHERE a.id_sesion = ?
            ORDER BY a.fecha_hora_entrada DESC
        ");
        $stmt->execute([$selectedSesion]);
        $asistencias = $stmt->fetchAll();
        
        // Get enrolled participants not yet marked
        $stmt = $db->prepare("
            SELECT p.*, i.id_inscripcion
            FROM inscripciones i
            INNER JOIN participantes p ON i.id_participante = p.id_participante
            INNER JOIN sesiones s ON i.id_evento = s.id_evento
            WHERE s.id_sesion = ? 
              AND i.estado_inscripcion = 'confirmada'
              AND NOT EXISTS (
                  SELECT 1 FROM asistencias 
                  WHERE id_sesion = s.id_sesion 
                  AND id_participante = p.id_participante
              )
            ORDER BY p.apellidos, p.nombres
        ");
        $stmt->execute([$selectedSesion]);
        $participantesPendientes = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    error_log("Attendance error: " . $e->getMessage());
    $eventos = [];
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Registro de Asistencia en Tiempo Real</h3>
    </div>
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Evento</label>
                <select name="evento" class="form-control" onchange="this.form.submit()" required>
                    <option value="">Seleccione un evento</option>
                    <?php foreach ($eventos as $ev): ?>
                        <option value="<?php echo $ev['id_evento']; ?>" <?php echo $selectedEvento == $ev['id_evento'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ev['nombre_evento']); ?>
                            (<?php echo $ev['total_sesiones']; ?> sesiones)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Sesión</label>
                <select name="sesion" class="form-control" <?php echo empty($sesiones) ? 'disabled' : ''; ?>>
                    <option value="">Seleccione una sesión</option>
                    <?php foreach ($sesiones as $ses): ?>
                        <option value="<?php echo $ses['id_sesion']; ?>" <?php echo $selectedSesion == $ses['id_sesion'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ses['nombre_sesion']); ?>
                            (<?php echo date('d/m/Y H:i', strtotime($ses['fecha'] . ' ' . $ses['hora_inicio'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">🔍 Ver</button>
        </form>
    </div>
</div>

<?php if ($selectedSesion && isset($participantesPendientes)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Marcar Asistencia (<?php echo count($participantesPendientes); ?> pendientes)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($participantesPendientes)): ?>
            <p style="color: var(--gray); text-align: center;">Todos los participantes han sido registrados</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>DNI</th>
                            <th>Participante</th>
                            <th>Email</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participantesPendientes as $part): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($part['dni']); ?></td>
                                <td><?php echo htmlspecialchars($part['nombres'] . ' ' . $part['apellidos']); ?></td>
                                <td><?php echo htmlspecialchars($part['email']); ?></td>
                                <td>
                                    <a href="marcar.php?sesion=<?php echo $selectedSesion; ?>&participante=<?php echo $part['id_participante']; ?>" 
                                       class="btn btn-sm btn-success">
                                        ✓ Marcar Presente
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Asistencias Registradas (<?php echo count($asistencias); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($asistencias)): ?>
            <p style="color: var(--gray); text-align: center;">No hay asistencias registradas aún</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>DNI</th>
                            <th>Participante</th>
                            <th>Hora de Entrada</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asistencias as $asis): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($asis['dni']); ?></td>
                                <td><?php echo htmlspecialchars($asis['nombres'] . ' ' . $asis['apellidos']); ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($asis['fecha_hora_entrada'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $asis['estado'] == 'presente' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($asis['estado']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
