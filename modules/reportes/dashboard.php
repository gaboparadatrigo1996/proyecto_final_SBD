<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'reportes';
$pageTitle = 'Dashboard General';

try {
    $db = Database::getInstance()->getConnection();
    
    // General Statistics
    $stats = [];
    
    // Total Events
    $stats['total_eventos'] = $db->query("SELECT COUNT(*) FROM eventos")->fetchColumn();
    $stats['eventos_activos'] = $db->query("SELECT COUNT(*) FROM eventos WHERE estado = 'activo'")->fetchColumn();
    $stats['eventos_finalizados'] = $db->query("SELECT COUNT(*) FROM eventos WHERE estado = 'finalizado'")->fetchColumn();
    
    // Total Participants
    $stats['total_participantes'] = $db->query("SELECT COUNT(*) FROM participantes")->fetchColumn();
    $stats['participantes_estudiantes'] = $db->query("SELECT COUNT(*) FROM participantes WHERE tipo_participante = 'estudiante'")->fetchColumn();
    $stats['participantes_profesionales'] = $db->query("SELECT COUNT(*) FROM participantes WHERE tipo_participante = 'profesional'")->fetchColumn();
    
    // Registrations
    $stats['total_inscripciones'] = $db->query("SELECT COUNT(*) FROM inscripciones")->fetchColumn();
    $stats['inscripciones_confirmadas'] = $db->query("SELECT COUNT(*) FROM inscripciones WHERE estado_inscripcion = 'confirmada'")->fetchColumn();
    $stats['inscripciones_pendientes'] = $db->query("SELECT COUNT(*) FROM inscripciones WHERE estado_inscripcion = 'pendiente'")->fetchColumn();
    
    // Payments
    $pagosStats = $db->query("
        SELECT 
            SUM(CASE WHEN estado_pago = 'aprobado' THEN monto ELSE 0 END) as total_aprobado,
            SUM(CASE WHEN estado_pago = 'pendiente' THEN monto ELSE 0 END) as total_pendiente,
            COUNT(CASE WHEN estado_pago = 'aprobado' THEN 1 END) as count_aprobado,
            COUNT(CASE WHEN estado_pago = 'pendiente' THEN 1 END) as count_pendiente
        FROM pagos
    ")->fetch();
    
    $stats['total_ingresos'] = $pagosStats['total_aprobado'] ?? 0;
    $stats['pagos_pendientes'] = $pagosStats['total_pendiente'] ?? 0;
    $stats['count_pagos_aprobados'] = $pagosStats['count_aprobado'] ?? 0;
    $stats['count_pagos_pendientes'] = $pagosStats['count_pendiente'] ?? 0;
    
    // Certificates
    $stats['total_certificados'] = $db->query("SELECT COUNT(*) FROM certificados")->fetchColumn();
    
    // Sessions
    $stats['total_sesiones'] = $db->query("SELECT COUNT(*) FROM sesiones")->fetchColumn();
    
    // Attendance
    $stats['total_asistencias'] = $db->query("SELECT COUNT(*) FROM asistencias")->fetchColumn();
    
    // Top Events (by registrations)
    $topEventos = $db->query("
        SELECT 
            e.nombre_evento,
            e.fecha_inicio,
            e.fecha_fin,
            COUNT(i.id_inscripcion) as total_inscripciones,
            e.capacidad_maxima
        FROM eventos e
        LEFT JOIN inscripciones i ON e.id_evento = i.id_evento
        WHERE e.estado = 'activo'
        GROUP BY e.id_evento
        ORDER BY total_inscripciones DESC
        LIMIT 5
    ")->fetchAll();
    
    // Recent Registrations
    $recentInscripciones = $db->query("
        SELECT 
            p.nombres,
            p.apellidos,
            e.nombre_evento,
            i.fecha_inscripcion,
            i.estado_inscripcion
        FROM inscripciones i
        INNER JOIN participantes p ON i.id_participante = p.id_participante
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        ORDER BY i.fecha_inscripcion DESC
        LIMIT 10
    ")->fetchAll();
    
    // Payments by Method
    $pagosPorMetodo = $db->query("
        SELECT 
            metodo_pago,
            COUNT(*) as cantidad,
            SUM(monto) as total
        FROM pagos
        WHERE estado_pago = 'aprobado'
        GROUP BY metodo_pago
        ORDER BY total DESC
    ")->fetchAll();
    
    // Monthly Registrations (last 6 months)
    $registrosMensuales = $db->query("
        SELECT 
            DATE_FORMAT(fecha_inscripcion, '%Y-%m') as mes,
            COUNT(*) as cantidad
        FROM inscripciones
        WHERE fecha_inscripcion >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY mes
        ORDER BY mes DESC
    ")->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $stats = [];
}

include '../../includes/header.php';
?>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.metric-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark, #4f46e5) 100%);
    padding: 1.5rem;
    border-radius: 12px;
    color: white;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.metric-card.success {
    background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
}

.metric-card.warning {
    background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%);
}

.metric-card.info {
    background: linear-gradient(135deg, var(--info) 0%, #0284c7 100%);
}

.metric-card.danger {
    background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
}

.metric-card.secondary {
    background: linear-gradient(135deg, #64748b 0%, #475569 100%);
}

.metric-icon {
    font-size: 2.5rem;
    opacity: 0.9;
    margin-bottom: 0.5rem;
}

.metric-value {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.metric-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.chart-container {
    padding: 1rem;
    background: var(--light);
    border-radius: 8px;
    margin-top: 1rem;
}

.bar {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.bar-label {
    min-width: 120px;
    font-weight: 500;
}

.bar-fill {
    flex: 1;
    height: 30px;
    background: var(--primary);
    border-radius: 4px;
    display: flex;
    align-items: center;
    padding: 0 0.75rem;
    color: white;
    font-weight: bold;
    font-size: 0.9rem;
}
</style>

<!-- Main Metrics -->
<div class="dashboard-grid">
    <div class="metric-card">
        <div class="metric-icon">📅</div>
        <div class="metric-value"><?php echo $stats['total_eventos']; ?></div>
        <div class="metric-label">Total Eventos</div>
        <small style="opacity: 0.8;"><?php echo $stats['eventos_activos']; ?> activos</small>
    </div>
    
    <div class="metric-card success">
        <div class="metric-icon">👥</div>
        <div class="metric-value"><?php echo $stats['total_participantes']; ?></div>
        <div class="metric-label">Participantes</div>
        <small style="opacity: 0.8;"><?php echo $stats['participantes_estudiantes']; ?> estudiantes</small>
    </div>
    
    <div class="metric-card info">
        <div class="metric-icon">📝</div>
        <div class="metric-value"><?php echo $stats['total_inscripciones']; ?></div>
        <div class="metric-label">Inscripciones</div>
        <small style="opacity: 0.8;"><?php echo $stats['inscripciones_confirmadas']; ?> confirmadas</small>
    </div>
    
    <div class="metric-card warning">
        <div class="metric-icon">💰</div>
        <div class="metric-value">Bs. <?php echo number_format($stats['total_ingresos'], 2); ?></div>
        <div class="metric-label">Ingresos Totales</div>
        <small style="opacity: 0.8;"><?php echo $stats['count_pagos_aprobados']; ?> pagos aprobados</small>
    </div>
    
    <div class="metric-card secondary">
        <div class="metric-icon">🎖️</div>
        <div class="metric-value"><?php echo $stats['total_certificados']; ?></div>
        <div class="metric-label">Certificados</div>
        <small style="opacity: 0.8;">Emitidos</small>
    </div>
    
    <div class="metric-card danger">
        <div class="metric-icon">⏳</div>
        <div class="metric-value"><?php echo $stats['count_pagos_pendientes']; ?></div>
        <div class="metric-label">Pagos Pendientes</div>
        <small style="opacity: 0.8;">Bs. <?php echo number_format($stats['pagos_pendientes'], 2); ?></small>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Top Events -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📊 Top Eventos (Activos)</h3>
        </div>
        <div class="card-body">
            <?php if (empty($topEventos)): ?>
                <p style="text-align: center; color: var(--gray);">No hay eventos activos</p>
            <?php else: ?>
                <?php foreach ($topEventos as $evento): ?>
                    <?php 
                    $porcentaje = $evento['capacidad_maxima'] > 0 ? 
                        ($evento['total_inscripciones'] / $evento['capacidad_maxima']) * 100 : 0;
                    ?>
                    <div style="margin-bottom: 1rem; padding: 1rem; background: var(--light); border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <strong><?php echo htmlspecialchars($evento['nombre_evento']); ?></strong>
                            <span class="badge badge-primary"><?php echo $evento['total_inscripciones']; ?> inscritos</span>
                        </div>
                        <div style="background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="width: <?php echo min($porcentaje, 100); ?>%; background: var(--primary); height: 100%;"></div>
                        </div>
                        <small style="color: var(--gray);">
                            <?php echo round($porcentaje); ?>% de <?php echo $evento['capacidad_maxima']; ?> | 
                            <?php echo date('d/m/Y', strtotime($evento['fecha_inicio'])); ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Payments by Method -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">💳 Pagos por Método</h3>
        </div>
        <div class="card-body">
            <?php if (empty($pagosPorMetodo)): ?>
                <p style="text-align: center; color: var(--gray);">No hay pagos registrados</p>
            <?php else: ?>
                <?php 
                $maxTotal = max(array_column($pagosPorMetodo, 'total'));
                foreach ($pagosPorMetodo as $metodo): 
                    $ancho = $maxTotal > 0 ? ($metodo['total'] / $maxTotal) * 100 : 0;
                ?>
                    <div class="bar">
                        <div class="bar-label"><?php echo ucfirst($metodo['metodo_pago']); ?></div>
                        <div class="bar-fill" style="width: <?php echo $ancho; ?>%;">
                            Bs. <?php echo number_format($metodo['total'], 2); ?> (<?php echo $metodo['cantidad']; ?>)
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Registrations -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">🕒 Inscripciones Recientes</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Participante</th>
                        <th>Evento</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentInscripciones)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--gray);">
                                No hay inscripciones recientes
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentInscripciones as $inscr): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($inscr['nombres'] . ' ' . $inscr['apellidos']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($inscr['nombre_evento']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($inscr['fecha_inscripcion'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $inscr['estado_inscripcion'] == 'confirmada' ? 'success' : 
                                             ($inscr['estado_inscripcion'] == 'pendiente' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($inscr['estado_inscripcion']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Monthly Registrations -->
<?php if (!empty($registrosMensuales)): ?>
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">📈 Inscripciones Mensuales (Últimos 6 Meses)</h3>
    </div>
    <div class="card-body">
        <div class="chart-container">
            <?php 
            $maxCantidad = max(array_column($registrosMensuales, 'cantidad'));
            foreach (array_reverse($registrosMensuales) as $mes): 
                $ancho = $maxCantidad > 0 ? ($mes['cantidad'] / $maxCantidad) * 100 : 0;
                $fecha = DateTime::createFromFormat('Y-m', $mes['mes']);
                $mesNombre = $fecha ? $fecha->format('M Y') : $mes['mes'];
            ?>
                <div class="bar">
                    <div class="bar-label"><?php echo $mesNombre; ?></div>
                    <div class="bar-fill" style="width: <?php echo $ancho; ?>%; background: var(--success);">
                        <?php echo $mes['cantidad']; ?> inscripciones
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
