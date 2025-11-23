<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - Sistema de Eventos</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
            background: var(--light);
        }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: var(--white);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform var(--transition-base);
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: var(--gradient-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .logo-text h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--white);
        }
        
        .logo-text p {
            margin: 0;
            font-size: 0.75rem;
            color: var(--gray-light);
        }
        
        .user-info {
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            margin: 1rem 1.5rem;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
        }
        
        .user-name {
            font-weight: 600;
            margin: 0;
            font-size: 0.95rem;
        }
        
        .user-role {
            color: var(--gray-light);
            font-size: 0.8rem;
            margin: 0;
        }
        
        .nav-menu {
            padding: 1rem 0;
        }
        
        .nav-section {
            margin-bottom: 1.5rem;
        }
        
        .nav-section-title {
            padding: 0 1.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--gray-light);
            margin-bottom: 0.5rem;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.5rem;
            color: var(--gray-lighter);
            text-decoration: none;
            transition: all var(--transition-fast);
            border-left: 3px solid transparent;
        }
        
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--white);
            border-left-color: var(--primary);
        }
        
        .nav-item.active {
            background: rgba(99, 102, 241, 0.1);
            color: var(--white);
            border-left-color: var(--primary);
        }
        
        .nav-icon {
            font-size: 1.25rem;
            width: 24px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            transition: margin-left var(--transition-base);
        }
        
        .top-bar {
            background: var(--white);
            padding: 1.25rem 2rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: var(--dark);
        }
        
        .top-bar-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .content-area {
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border-left: 4px solid;
            transition: transform var(--transition-base);
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
        }
        
        .stat-card.primary { border-color: var(--primary); }
        .stat-card.success { border-color: var(--success); }
        .stat-card.warning { border-color: var(--warning); }
        .stat-card.danger { border-color: var(--danger); }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-card.primary .stat-icon { background: rgba(99, 102, 241, 0.1); }
        .stat-card.success .stat-icon { background: rgba(16, 185, 129, 0.1); }
        .stat-card.warning .stat-icon { background: rgba(245, 158, 11, 0.1); }
        .stat-card.danger .stat-icon { background: rgba(239, 68, 68, 0.1); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-icon">🎓</div>
                    <div class="logo-text">
                        <h3>Eventos</h3>
                        <p>Académicos</p>
                    </div>
                </div>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                </div>
                <p class="user-name"><?php echo $_SESSION['user_name'] ?? 'Usuario'; ?></p>
                <p class="user-role"><?php echo $_SESSION['role_name'] ?? 'Rol'; ?></p>
            </div>
            
            <nav class="nav-menu">
                <div class="nav-section">
                    <div class="nav-section-title">Principal</div>
                    <a href="<?php echo BASE_URL; ?>/dashboard/index.php" class="nav-item <?php echo ($currentPage ?? '') == 'dashboard' ? 'active' : ''; ?>">
                        <span class="nav-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                </div>
                
                <?php if (hasRole(['Administrador', 'Responsable de Inscripción', 'Asistente'])): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Gestión</div>
                    <a href="<?php echo BASE_URL; ?>/modules/eventos/index.php" class="nav-item <?php echo ($currentPage ?? '') == 'eventos' ? 'active' : ''; ?>">
                        <span class="nav-icon">📅</span>
                        <span>Eventos</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/modules/participantes/index.php" class="nav-item <?php echo ($currentPage ?? '') == 'participantes' ? 'active' : ''; ?>">
                        <span class="nav-icon">👥</span>
                        <span>Participantes</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/modules/inscripciones/index.php" class="nav-item <?php echo ($currentPage ?? '') == 'inscripciones' ? 'active' : ''; ?>">
                        <span class="nav-icon">✍️</span>
                        <span>Inscripciones</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/modules/pagos/index.php" class="nav-item <?php echo ($currentPage ?? '') == 'pagos' ? 'active' : ''; ?>">
                        <span class="nav-icon">💳</span>
                        <span>Pagos</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/modules/asistencia/index.php" class="nav-item <?php echo ($currentPage ?? '') == 'asistencia' ? 'active' : ''; ?>">
                        <span class="nav-icon">✅</span>
                        <span>Asistencia</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/modules/certificados/index.php" class="nav-item <?php echo ($currentPage ?? '') == 'certificados' ? 'active' : ''; ?>">
                        <span class="nav-icon">🎖️</span>
                        <span>Certificados</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (hasRole('Participante')): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Mi Portal</div>
                    <a href="<?php echo BASE_URL; ?>/modules/participante/mis_eventos.php" class="nav-item <?php echo ($currentPage ?? '') == 'mis-eventos' ? 'active' : ''; ?>">
                        <span class="nav-icon">🎯</span>
                        <span>Mis Eventos</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (hasRole(['Administrador', 'Responsable de Inscripción', 'Asistente'])): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Reportes</div>
                    <a href="<?php echo BASE_URL; ?>/modules/reportes/index.php" class="nav-item <?php echo ($currentPage ?? '') == 'reportes' ? 'active' : ''; ?>">
                        <span class="nav-icon">📈</span>
                        <span>Reportes</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/modules/consultas/index.php" class="nav-item <?php echo ($currentPage ?? '') == 'consultas' ? 'active' : ''; ?>">
                        <span class="nav-icon">🔍</span>
                        <span>Consultas SQL</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (hasRole('Administrador')): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Administración</div>
                    <a href="<?php echo BASE_URL; ?>/modules/usuarios/index.php" class="nav-item <?php echo ($currentPage ?? '') == 'usuarios' ? 'active' : ''; ?>">
                        <span class="nav-icon">👤</span>
                        <span>Usuarios</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/modules/auditoria/index.php" class="nav-item <?php echo ($currentPage ?? '') == 'auditoria' ? 'active' : ''; ?>">
                        <span class="nav-icon">📋</span>
                        <span>Auditoría</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="nav-section">
                    <div class="nav-section-title">Cuenta</div>
                    <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="nav-item">
                        <span class="nav-icon">🚪</span>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h1 class="page-title"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                <div class="top-bar-actions">
                    <button class="btn btn-sm btn-outline" onclick="toggleSidebar()">☰</button>
                </div>
            </div>
            
            <div class="content-area">
