<?php
/**
 * FrigoTIC - Error 404
 */
$pageTitle = 'Página no encontrada';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - <?= $pageTitle ?> | FrigoTIC</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .error-container {
            text-align: center;
            max-width: 500px;
        }
        
        .error-icon {
            font-size: 8rem;
            color: #dc2626;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #1e293b;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .error-title {
            font-size: 1.5rem;
            color: #475569;
            margin-bottom: 1rem;
        }
        
        .error-description {
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
        }
        
        .fridge-animation {
            font-size: 4rem;
            margin-top: 2rem;
            display: inline-block;
            animation: shake 3s ease-in-out infinite;
        }
        
        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="error-code">404</div>
        <h1 class="error-title">Página no encontrada</h1>
        <p class="error-description">
            Parece que te has perdido buscando en el frigorífico. 
            La página que buscas no existe o ha sido movida.
        </p>
        <a href="/" class="btn">
            <i class="fas fa-home"></i> Volver al inicio
        </a>
        <div class="fridge-animation">
            <i class="fas fa-snowflake"></i>
        </div>
    </div>
</body>
</html>
