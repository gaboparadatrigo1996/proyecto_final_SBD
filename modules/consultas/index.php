<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'consultas';
$pageTitle = 'Consultas SQL Personalizadas';

$resultado = [];
$error = '';
$query = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = $_POST['query'] ?? '';
    
    if (!empty($query)) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Security check - only allow SELECT queries
            $firstWord = strtoupper(trim(explode(' ', $query)[0]));
            
            if ($firstWord !== 'SELECT' && $firstWord !== 'SHOW' && $firstWord !== 'DESCRIBE' && $firstWord !== 'CALL') {
                $error = "Por seguridad, solo se permiten consultas SELECT, SHOW, DESCRIBE y CALL (procedimientos).";
            } else {
                $stmt = $db->prepare($query);
                $stmt->execute();
                $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Log the query
                logAudit($_SESSION['user_id'], 'SQL_QUERY', null, null, substr($query, 0, 500));
            }
            
        } catch (PDOException $e) {
            $error = "Error en la consulta: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Ejecutar Consulta SQL</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>ℹ️ Información de Seguridad:</strong> Solo se permiten consultas de tipo SELECT, SHOW, DESCRIBE y CALL para proteger la integridad de la base de datos.
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Consulta SQL</label>
                <textarea name="query" class="form-control" rows="6" placeholder="Escriba su consulta SQL aquí..." required><?php echo htmlspecialchars($query); ?></textarea>
                <small style="color: var(--gray);">
                    Ejemplo: SELECT * FROM eventos WHERE estado = 'activo'
                </small>
            </div>
            
            <button type="submit" class="btn btn-primary">▶️ Ejecutar Consulta</button>
        </form>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if (!empty($resultado)): ?>
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">Resultados (<?php echo count($resultado); ?> filas)</h3>
            <button onclick="exportTableToExcel('resultTable', 'consulta_sql')" class="btn btn-sm btn-success">
                📥 Exportar a Excel
            </button>
        </div>
        <div class="card-body" style="padding: 0; overflow-x: auto;">
            <table id="resultTable">
                <thead>
                    <tr>
                        <?php if (isset($resultado[0])): ?>
                            <?php foreach (array_keys($resultado[0]) as $column): ?>
                                <th><?php echo htmlspecialchars($column); ?></th>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultado as $row): ?>
                        <tr>
                            <?php foreach ($row as $value): ?>
                                <td><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)): ?>
    <div class="alert alert-warning">
        La consulta se ejecutó correctamente pero no devolvió ningún resultado.
    </div>
<?php endif; ?>

<!-- Predefined Queries -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Consultas Predefinidas</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; gap: 0.5rem;">
            <button onclick="document.querySelector('textarea[name=query]').value = this.getAttribute('data-query')" 
                    data-query="SELECT e.nombre_evento, COUNT(i.id_inscripcion) as total_inscritos FROM eventos e LEFT JOIN inscripciones i ON e.id_evento = i.id_evento GROUP BY e.id_evento ORDER BY total_inscritos DESC"
                    class="btn btn-outline btn-sm" style="text-align: left;">
                1. Total de inscritos por evento
            </button>
            
            <button onclick="document.querySelector('textarea[name=query]').value = this.getAttribute('data-query')" 
                    data-query="SELECT p.*, COUNT(i.id_inscripcion) as eventos_participados FROM participantes p LEFT JOIN inscripciones i ON p.id_participante = i.id_participante GROUP BY p.id_participante ORDER BY eventos_participados DESC"
                    class="btn btn-outline btn-sm" style="text-align: left;">
                2. Participantes más activos
            </button>
            
            <button onclick="document.querySelector('textarea[name=query]').value = this.getAttribute('data-query')" 
                    data-query="SELECT e.nombre_evento, s.nombre_sesion, COUNT(a.id_asistencia) as total_asistentes FROM sesiones s INNER JOIN eventos e ON s.id_evento = e.id_evento LEFT JOIN asistencias a ON s.id_sesion = a.id_sesion GROUP BY s.id_sesion ORDER BY total_asistentes DESC"
                    class="btn btn-outline btn-sm" style="text-align: left;">
                3. Asistencia por sesión
            </button>
            
            <button onclick="document.querySelector('textarea[name=query]').value = this.getAttribute('data-query')" 
                    data-query="SELECT DATE(fecha_pago) as fecha, COUNT(*) as total_pagos, SUM(monto) as monto_total FROM pagos WHERE estado_pago = 'aprobado' GROUP BY DATE(fecha_pago) ORDER BY fecha DESC"
                    class="btn btn-outline btn-sm" style="text-align: left;">
                4. Pagos por fecha
            </button>
            
            <button onclick="document.querySelector('textarea[name=query]').value = this.getAttribute('data-query')" 
                    data-query="SELECT tipo_participante, COUNT(*) as total FROM participantes GROUP BY tipo_participante"
                    class="btn btn-outline btn-sm" style="text-align: left;">
                5. Participantes por tipo
            </button>
            
            <button onclick="document.querySelector('textarea[name=query]').value = this.getAttribute('data-query')" 
                    data-query="SELECT e.nombre_evento, e.capacidad_maxima, COUNT(i.id_inscripcion) as inscritos, (e.capacidad_maxima - COUNT(i.id_inscripcion)) as espacios_disponibles FROM eventos e LEFT JOIN inscripciones i ON e.id_evento = i.id_evento WHERE e.capacidad_maxima IS NOT NULL GROUP BY e.id_evento"
                    class="btn btn-outline btn-sm" style="text-align: left;">
                6. Capacidad vs inscritos
            </button>
            
            <button onclick="document.querySelector('textarea[name=query]').value = this.getAttribute('data-query')" 
                    data-query="SELECT u.nombre_completo, r.nombre_rol, COUNT(a.id_auditoria) as acciones_realizadas FROM usuarios u INNER JOIN roles r ON u.id_rol = r.id_rol LEFT JOIN auditoria a ON u.id_usuario = a.id_usuario GROUP BY u.id_usuario ORDER BY acciones_realizadas DESC"
                    class="btn btn-outline btn-sm" style="text-align: left;">
                7. Actividad de usuarios
            </button>

            <button onclick="document.querySelector('textarea[name=query]').value = this.getAttribute('data-query')" 
                    data-query="SELECT p.nombres, p.apellidos, e.nombre_evento, i.estado_inscripcion, COALESCE(pag.monto, 0) as monto_pagado, pag.metodo_pago, CASE WHEN c.id_certificado IS NOT NULL THEN 'SI' ELSE 'NO' END as tiene_certificado FROM participantes p JOIN inscripciones i ON p.id_participante = i.id_participante JOIN eventos e ON i.id_evento = e.id_evento LEFT JOIN pagos pag ON i.id_inscripcion = pag.id_inscripcion LEFT JOIN certificados c ON i.id_inscripcion = c.id_inscripcion ORDER BY e.fecha_inicio DESC, p.apellidos ASC"
                    class="btn btn-outline btn-sm" style="text-align: left;">
                8. Reporte completo (Participantes + Pagos + Certificados)
            </button>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
