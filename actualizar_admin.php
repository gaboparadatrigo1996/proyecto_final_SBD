<?php
/**
 * Script para actualizar la contraseña del administrador
 * Ejecutar este archivo UNA VEZ: http://localhost:8080/proyectoBDv2/actualizar_admin.php
 * Luego ELIMINAR este archivo por seguridad
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Generar hash correcto para "admin123"
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Actualizar en la base de datos
    $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE email = 'admin@evento.com'");
    $stmt->execute([$hash]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ <strong>Contraseña actualizada correctamente!</strong><br><br>";
        echo "📧 Email: <strong>admin@evento.com</strong><br>";
        echo "🔑 Password: <strong>admin123</strong><br><br>";
        echo "🔒 Nuevo hash: <code>" . htmlspecialchars($hash) . "</code><br><br>";
        echo "⚠️ <strong>IMPORTANTE:</strong> Por seguridad, elimina este archivo (actualizar_admin.php) ahora.<br><br>";
        echo "<a href='auth/login.php' style='display: inline-block; padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 8px;'>➡️ Ir al Login</a>";
    } else {
        echo "❌ Error: No se encontró el usuario admin@evento.com<br>";
        echo "Por favor, verifica que la base de datos esté importada correctamente.";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Error de conexión:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>";
    echo "Verifica que:<br>";
    echo "1. MySQL esté corriendo<br>";
    echo "2. La base de datos 'evento_academico' exista<br>";
    echo "3. El archivo instalacion_completa.sql se haya importado correctamente<br>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Actualizar Admin</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            word-break: break-all;
        }
    </style>
</head>
<body>
</body>
</html>
