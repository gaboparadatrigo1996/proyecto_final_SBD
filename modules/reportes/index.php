<?php
require_once '../../config/config.php';
checkSession();

$currentPage = 'reportes';
$pageTitle = 'Reportes del Sistema';

include '../../includes/header.php';
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Attendance Report -->
    <div class="card" style="cursor: pointer;" onclick="window.location='asistencia.php'">
        <div class="card-body">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 60px; height: 60px; background: rgba(99, 102, 241, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                    ✅
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem;">Reporte de Asistencia</h3>
                    <p style="margin: 0; color: var(--gray); font-size: 0.9rem;">Por evento y sesión</p>
                </div>
            </div>
            <a href="asistencia.php" class="btn btn-primary btn-sm" style="width: 100%;">Ver Reporte</a>
        </div>
    </div>
    
    <!-- Payments Report -->
    <div class="card" style="cursor: pointer;" onclick="window.location='pagos.php'">
        <div class="card-body">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                    💳
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem;">Estado de Pagos</h3>
                    <p style="margin: 0; color: var(--gray); font-size: 0.9rem;">Aprobados, pendientes, rechazados</p>
                </div>
            </div>
            <a href="pagos.php" class="btn btn-success btn-sm" style="width: 100%;">Ver Reporte</a>
        </div>
    </div>
    
    <!-- Certificates Report -->
    <div class="card" style="cursor: pointer;" onclick="window.location='certificados.php'">
        <div class="card-body">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 60px; height: 60px; background: rgba(245, 158, 11, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                    🎖️
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem;">Certificados Emitidos</h3>
                    <p style="margin: 0; color: var(--gray); font-size: 0.9rem;">Lista de certificados generados</p>
                </div>
            </div>
            <a href="certificados.php" class="btn btn-warning btn-sm" style="width: 100%;">Ver Reporte</a>
        </div>
    </div>
    
    <!-- Events Report -->
    <div class="card" style="cursor: pointer;" onclick="window.location='eventos.php'">
        <div class="card-body">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 60px; height: 60px; background: rgba(59, 130, 246, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                    📅
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem;">Reporte de Eventos</h3>
                    <p style="margin: 0; color: var(--gray); font-size: 0.9rem;">Inscripciones por evento</p>
                </div>
            </div>
            <a href="eventos.php" class="btn btn-primary btn-sm" style="width: 100%;">Ver Reporte</a>
        </div>
    </div>
    
    <!-- Participants Report -->
    <div class="card" style="cursor: pointer;" onclick="window.location='participantes.php'">
        <div class="card-body">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 60px; height: 60px; background: rgba(236, 72, 153, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                    👥
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem;">Reporte de Participantes</h3>
                    <p style="margin: 0; color: var(--gray); font-size: 0.9rem;">Por tipo e institución</p>
                </div>
            </div>
            <a href="participantes.php" class="btn btn-secondary btn-sm" style="width: 100%;">Ver Reporte</a>
        </div>
    </div>
    
    <!-- Dashboard Report -->
    <div class="card" style="cursor: pointer;" onclick="window.location='dashboard.php'">
        <div class="card-body">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 60px; height: 60px; background: rgba(139, 92, 246, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                    📊
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem;">Dashboard General</h3>
                    <p style="margin: 0; color: var(--gray); font-size: 0.9rem;">Estadísticas generales</p>
                </div>
            </div>
            <a href="dashboard.php" class="btn btn-outline btn-sm" style="width: 100%;">Ver Reporte</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Información</h3>
    </div>
    <div class="card-body">
        <p>Seleccione un tipo de reporte para visualizar la información. Los reportes pueden ser exportados a:</p>
        <ul>
            <li><strong>PDF:</strong> Para impresión y archivos formales</li>
            <li><strong>Excel:</strong> Para análisis de datos</li>
        </ul>
        <p style="margin-top: 1rem; padding: 1rem; background: var(--light); border-radius: 8px; border-left: 4px solid var(--primary);">
            💡 <strong>Consejo:</strong> Los reportes se generan en tiempo real con los datos más actualizados del sistema.
        </p>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
