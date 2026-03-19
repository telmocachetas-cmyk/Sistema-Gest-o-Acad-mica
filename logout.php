<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A sair...</title>
    <link rel="stylesheet" href="estilo.css">
    <meta http-equiv="refresh" content="2;url=login.php">
</head>
<body>
    <div class="container" style="max-width: 400px; margin: 100px auto; text-align: center;">
        <div class="card">
            <h1>👋 Até breve!</h1>
            <p>A sair do sistema...</p>
            <div style="margin: 20px 0;">
                <div style="width: 50px; height: 50px; border: 3px solid #f3f3f3; border-top: 3px solid #667eea; border-radius: 50%; margin: 0 auto; animation: spin 1s linear infinite;"></div>
            </div>
            <p><a href="login.php">Clique aqui se não for redirecionado</a></p>
        </div>
    </div>
    
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</body>
</html>