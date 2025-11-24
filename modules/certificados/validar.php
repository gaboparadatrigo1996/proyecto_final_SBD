<?php
require_once '../../config/config.php';

// This page can be public for certificate validation
$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    $mensaje = "Por favor, ingrese un código de validación.";
} else {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT c.*, 
                   p.nombres, p.apellidos, p.dni,
                   e.nombre_evento, e.fecha_inicio, e.fecha_fin
            FROM certificados c
            INNER JOIN inscripciones i ON c.id_inscripcion = i.id_inscripcion
            INNER JOIN participantes p ON i.id_participante = p.id_participante
            INNER JOIN eventos e ON i.id_evento = e.id_evento
            WHERE c.codigo_validacion = ?
        ");
        $stmt->execute([$codigo]);
        $certificado = $stmt->fetch();
        
        if ($certificado) {
            $valido = true;
        } else {
            $valido = false;
            $mensaje = "El código ingresado no corresponde a ningún certificado válido.";
        }
        
    } catch (Exception $e) {
        error_log("Certificate validation error: " . $e->getMessage());
        $mensaje = "Error al validar el certificado.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar Certificado</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            background: #e5e7eb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .validation-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        
        .validation-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .validation-icon {
            font-size: 64px;
            margin-bottom: 1rem;
        }
        
        .validation-title {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .validation-subtitle {
            color: var(--gray);
        }
        
        .search-form {
            margin-bottom: 2rem;
        }
        
        .result-success {
            background: var(--light-success);
            border: 2px solid var(--success);
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .result-error {
            background: #fff3f3;
            border: 2px solid var(--danger);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--gray);
        }
        
        .info-value {
            font-weight: 500;
            color: var(--dark);
        }
    </style>
</head>
<body>
    <div class="validation-card">
        <div class="validation-header">
            <div class="validation-icon">🔍</div>
            <h1 class="validation-title">Validar Certificado</h1>
            <p class="validation-subtitle">Ingrese el código de validación del certificado</p>
        </div>
        
        <form method="GET" class="search-form">
            <div class="form-group">
                <label class="form-label">Código de Validación</label>
                <input type="text" name="codigo" class="form-control" 
                       placeholder="CERT-XXXXX-XXXXX..." 
                       value="<?php echo htmlspecialchars($codigo); ?>" 
                       required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                ✓ Validar Certificado
            </button>
        </form>
        
        <?php if (isset($valido) && $valido): ?>
            <div class="result-success">
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <div style="font-size: 48px;">✅</div>
                    <h3 style="color: var(--success); margin: 0.5rem 0;">Certificado Válido</h3>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Participante:</span>
                    <span class="info-value"><?php echo htmlspecialchars($certificado['nombres'] . ' ' . $certificado['apellidos']); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">DNI:</span>
                    <span class="info-value"><?php echo htmlspecialchars($certificado['dni']); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Evento:</span>
                    <span class="info-value"><?php echo htmlspecialchars($certificado['nombre_evento']); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Fecha del Evento:</span>
                    <span class="info-value">
                        <?php echo date('d/m/Y', strtotime($certificado['fecha_inicio'])); ?> - 
                        <?php echo date('d/m/Y', strtotime($certificado['fecha_fin'])); ?>
                    </span>
                </div>
                
                <div class="info-row" style="border-bottom: none;">
                    <span class="info-label">Fecha de Emisión:</span>
                    <span class="info-value"><?php echo date('d/m/Y', strtotime($certificado['fecha_emision'])); ?></span>
                </div>
                
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="descargar.php?id=<?php echo $certificado['id_certificado']; ?>" 
                       class="btn btn-success" target="_blank">
                        📄 Ver Certificado
                    </a>
                </div>
            </div>
        <?php elseif (isset($mensaje)): ?>
            <div class="result-error">
                <div style="font-size: 48px; margin-bottom: 1rem;">❌</div>
                <h3 style="color: var(--danger); margin-bottom: 1rem;">Certificado No Válido</h3>
                <p style="color: var(--gray);"><?php echo htmlspecialchars($mensaje); ?></p>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="../../auth/login.php" style="color: var(--primary); text-decoration: none;">
                ← Volver al Sistema
            </a>
        </div>
    </div>
</body>
</html>
