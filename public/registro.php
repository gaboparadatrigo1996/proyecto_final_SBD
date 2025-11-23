<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

require_once '../config/database.php';
define('BASE_URL', 'http://localhost:8080');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Participante - Sistema de Eventos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
        }
        
        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        .registro-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 600px;
            padding: 20px;
            margin: 40px auto;
        }
        
        .registro-card {
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
        
        .registro-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .registro-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .registro-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .registro-subtitle {
            color: var(--gray);
            font-size: 0.95rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-lightest);
        }
        
        .login-link a {
            color: var(--primary);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="registro-container">
        <div class="registro-card">
            <div class="registro-header">
                <div class="registro-logo">🎓</div>
                <h1 class="registro-title">Registro de Participante</h1>
                <p class="registro-subtitle">Crea tu cuenta para inscribirte en eventos académicos</p>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    if ($_GET['error'] == 'email_exists') {
                        echo 'El correo electrónico ya está registrado.';
                    } elseif ($_GET['error'] == 'dni_exists') {
                        echo 'El DNI ya está registrado.';
                    } elseif ($_GET['error'] == 'password_mismatch') {
                        echo 'Las contraseñas no coinciden.';
                    } else {
                        echo 'Error al procesar el registro. Intente nuevamente.';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    ✅ ¡Registro exitoso! Ya puedes <a href="../auth/login.php">iniciar sesión</a> y pre-inscribirte en eventos.
                </div>
            <?php endif; ?>
            
            <form action="registro_process.php" method="POST" id="registroForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nombres *</label>
                        <input 
                            type="text" 
                            name="nombres" 
                            class="form-control" 
                            required
                            placeholder="Juan Carlos"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Apellidos *</label>
                        <input 
                            type="text" 
                            name="apellidos" 
                            class="form-control" 
                            required
                            placeholder="Pérez López"
                        >
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">DNI/CI *</label>
                        <input 
                            type="text" 
                            name="dni" 
                            class="form-control" 
                            required
                            placeholder="12345678"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input 
                            type="tel" 
                            name="telefono" 
                            class="form-control" 
                            placeholder="71234567"
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Correo Electrónico *</label>
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control" 
                        required
                        placeholder="correo@ejemplo.com"
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label">Institución</label>
                    <input 
                        type="text" 
                        name="institucion" 
                        class="form-control" 
                        placeholder="Universidad, empresa, etc."
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipo de Participante *</label>
                    <select name="tipo_participante" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <option value="estudiante">Estudiante</option>
                        <option value="profesional">Profesional</option>
                        <option value="ponente">Ponente</option>
                        <option value="invitado">Invitado</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Contraseña *</label>
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            class="form-control" 
                            required
                            minlength="6"
                            placeholder="Mínimo 6 caracteres"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirmar Contraseña *</label>
                        <input 
                            type="password" 
                            name="password_confirm" 
                            class="form-control" 
                            required
                            minlength="6"
                            placeholder="Repita la contraseña"
                        >
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1rem; padding: 1rem; margin-top: 1rem;">
                    Crear Cuenta
                </button>
            </form>
            
            <div class="login-link">
                ¿Ya tienes una cuenta? <a href="../auth/login.php">Iniciar Sesión</a>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('registroForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const passwordConfirm = document.querySelector('input[name="password_confirm"]').value;
            
            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
            }
        });
    </script>
</body>
</html>
