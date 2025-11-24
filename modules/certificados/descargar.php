<?php
require_once '../../config/config.php';
checkSession();

if (!isset($_GET['id'])) {
    redirect('modules/certificados/index.php');
}

try {
    $db = Database::getInstance()->getConnection();
    
    $certificadoId = (int)$_GET['id'];
    
    // Get certificate data
    $stmt = $db->prepare("
        SELECT c.*, 
               i.id_evento,
               p.nombres, p.apellidos, p.dni, p.email,
               e.nombre_evento, e.fecha_inicio, e.fecha_fin,
               fn_porcentaje_asistencia_participante(p.id_participante, e.id_evento) as porcentaje_asistencia
        FROM certificados c
        INNER JOIN inscripciones i ON c.id_inscripcion = i.id_inscripcion
        INNER JOIN participantes p ON i.id_participante = p.id_participante
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        WHERE c.id_certificado = ?
    ");
    $stmt->execute([$certificadoId]);
    $certificado = $stmt->fetch();
    
    if (!$certificado) {
        redirect('modules/certificados/index.php?error=notfound');
    }
    
    // Calculate duration in days
    $fechaInicio = new DateTime($certificado['fecha_inicio']);
    $fechaFin = new DateTime($certificado['fecha_fin']);
    $duracion = $fechaInicio->diff($fechaFin)->days + 1;
    
} catch (Exception $e) {
    error_log("Certificate download error: " . $e->getMessage());
    redirect('modules/certificados/index.php?error=system');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado - <?php echo htmlspecialchars($certificado['nombres'] . ' ' . $certificado['apellidos']); ?></title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #e5e7eb;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 10px;
        }
        
        .certificate {
            background: white;
            width: 297mm;
            height: 210mm;
            padding: 15mm 25mm;
            position: relative;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .certificate::before {
            content: '';
            position: absolute;
            top: 10mm;
            left: 15mm;
            right: 15mm;
            bottom: 10mm;
            border: 3px solid #4b5563;
            border-radius: 8px;
        }
        
        .certificate::after {
            content: '';
            position: absolute;
            top: 12mm;
            left: 17mm;
            right: 17mm;
            bottom: 12mm;
            border: 1px solid #6b7280;
            border-radius: 6px;
        }
        
        .content {
            position: relative;
            z-index: 1;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 10px;
        }
        
        .logo {
            width: 80px;
            height: auto;
        }
        
        .university-name {
            font-size: 24px;
            font-weight: bold;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1.2;
        }
        
        .title {
            font-size: 42px;
            font-weight: bold;
            color: #333;
            margin: 8px 0;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        
        .subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .participant {
            font-size: 32px;
            font-weight: bold;
            color: #4b5563;
            margin: 15px 0;
            padding: 12px 20px;
            border-bottom: 2px solid #4b5563;
            display: inline-block;
            min-width: 400px;
        }
        
        .description {
            font-size: 15px;
            color: #555;
            line-height: 1.6;
            margin: 15px auto;
            max-width: 700px;
        }
        
        .event-name {
            font-weight: bold;
            color: #6b7280;
            font-size: 18px;
        }
        
        .footer {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .signature {
            text-align: center;
            flex: 1;
        }
        
        .signature-line {
            width: 180px;
            border-top: 2px solid #333;
            margin: 30px auto 8px;
        }
        
        .signature-name {
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }
        
        .signature-role {
            color: #666;
            font-size: 12px;
        }
        
        .certificate-info {
            text-align: right;
            font-size: 11px;
            color: #999;
        }
        
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .btn {
            background: white;
            color: #4b5563;
            border: 2px solid #4b5563;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #4b5563;
            color: white;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .certificate {
                box-shadow: none;
                page-break-after: always;
                margin: 0;
                width: 297mm;
                height: 210mm;
            }
            
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn">🖨️ Imprimir / Guardar PDF</button>
        <a href="index.php" class="btn">← Volver</a>
    </div>
    
    <div class="certificate">
        <div class="content">
            <div>
                <div class="header">
                    <img src="../../assets/images/logo_umsa.png" alt="UMSA Logo" class="logo">
                    <div class="university-name">
                        Universidad Mayor<br>de San Andrés
                    </div>
                </div>
                
                <div class="title">Certificado de Participación</div>
                
                <div class="subtitle">Se otorga el presente certificado a:</div>
                
                <div class="participant">
                    <?php echo strtoupper(htmlspecialchars($certificado['nombres'] . ' ' . $certificado['apellidos'])); ?>
                </div>
                
                <div class="description">
                    Por su participación en el evento académico<br>
                    <span class="event-name"><?php echo strtoupper(htmlspecialchars($certificado['nombre_evento'])); ?></span><br>
                    realizado del <?php echo date('d', strtotime($certificado['fecha_inicio'])); ?> 
                    <?php 
                    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 
                              'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
                    echo 'de ' . $meses[(int)date('n', strtotime($certificado['fecha_inicio']))]; 
                    ?>
                    al <?php echo date('d', strtotime($certificado['fecha_fin'])); ?> 
                    de <?php echo $meses[(int)date('n', strtotime($certificado['fecha_fin']))]; ?> 
                    de <?php echo date('Y', strtotime($certificado['fecha_fin'])); ?>,<br>
                    con una duración de <strong><?php echo $duracion; ?> día<?php echo $duracion > 1 ? 's' : ''; ?></strong>
                    y una asistencia del <strong><?php echo number_format($certificado['porcentaje_asistencia'], 1); ?>%</strong>.
                </div>
            </div>
            
            <div class="footer">
                <div class="signature">
                    <div class="signature-line"></div>
                    <div class="signature-name">Director Académico</div>
                    <div class="signature-role">Universidad Mayor de San Andrés</div>
                </div>
                
                <div class="certificate-info">
                    <div>Código de Verificación:</div>
                    <div style="font-weight: bold; color: #4b5563; margin: 5px 0; font-size: 12px;">
                        <?php echo htmlspecialchars($certificado['codigo_validacion']); ?>
                    </div>
                    <div>Fecha de Emisión: <?php echo date('d/m/Y', strtotime($certificado['fecha_emision'])); ?></div>
                    <div>DNI: <?php echo htmlspecialchars($certificado['dni']); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-print dialog can be enabled here if needed
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
