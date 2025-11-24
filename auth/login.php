<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Eventos Académicos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e5e7eb;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0,0,0,0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
        }
        
        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            background: #4b5563;
            border-radius: 20px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 10px 30px rgba(75, 85, 99, 0.3);
        }
        
        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            color: var(--gray);
            font-size: 0.95rem;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 1.2rem;
        }
        
        .form-control-icon {
            padding-left: 3rem;
        }
        
        .forgot-password {
            text-align: right;
            margin-top: -0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .forgot-password a {
            color: var(--primary);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-lightest);
            color: var(--gray);
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">🎓</div>
                <h1 class="login-title">Bienvenido</h1>
                <p class="login-subtitle">Sistema de Gestión de Eventos Académicos</p>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    if ($_GET['error'] == 'invalid') {
                        echo 'Credenciales inválidas. Por favor, intente nuevamente.';
                    } elseif ($_GET['error'] == 'inactive') {
                        echo 'Su cuenta está inactiva. Contacte al administrador.';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['timeout'])): ?>
                <div class="alert alert-warning">
                    Su sesión ha expirado por inactividad. Por favor, inicie sesión nuevamente.
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-success">
                    Ha cerrado sesión exitosamente.
                </div>
            <?php endif; ?>
            
            <form action="login_process.php" method="POST" id="loginForm">
                <div class="input-group">
                    <label class="form-label">Correo Electrónico</label>
                    <span class="input-icon">📧</span>
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control form-control-icon" 
                        placeholder="usuario@ejemplo.com"
                        required
                        autocomplete="email"
                    >
                </div>
                
                <div class="input-group">
                    <label class="form-label">Contraseña</label>
                    <span class="input-icon">🔒</span>
                    <input 
                        type="password" 
                        name="password" 
                        id="password"
                        class="form-control form-control-icon" 
                        placeholder="Ingrese su contraseña"
                        required
                    >
                </div>
                
                <div class="forgot-password">
                    <a href="#">¿Olvidó su contraseña?</a>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1rem; padding: 1rem;">
                    Iniciar Sesión
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                ¿No tienes una cuenta? <a href="../public/registro.php" style="color: var(--primary); font-weight: 600;">Regístrate como Participante</a>
            </div>
            
            <div class="login-footer">
                <p>Sistema de Gestión de Eventos Académicos v1.0</p>
                <p style="font-size: 0.75rem; margin-top: 0.5rem;">
                    Usuario demo: <strong>admin@evento.com</strong> / Contraseña: <strong>admin123</strong>
                </p>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
