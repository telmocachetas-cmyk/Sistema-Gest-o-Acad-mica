<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['grupo'] != 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';
$login = $_SESSION['login'];

// Buscar dados do utilizador
$sql = "SELECT u.*, g.GRUPO FROM users u 
        INNER JOIN grupos g ON u.grupo = g.ID 
        WHERE u.login = '$login'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

// Processar alteração de password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['alterar_password'])) {
    $pwd_atual = $_POST['pwd_atual'];
    $pwd_nova = $_POST['pwd_nova'];
    $pwd_confirmar = $_POST['pwd_confirmar'];
    
    // Verificar se password atual está correta
    if (md5($pwd_atual) != $user['pwd']) {
        $mensagem = '<div class="alert alert-error">Password atual incorreta!</div>';
    } elseif ($pwd_nova != $pwd_confirmar) {
        $mensagem = '<div class="alert alert-error">As passwords não coincidem!</div>';
    } elseif (strlen($pwd_nova) < 4) {
        $mensagem = '<div class="alert alert-error">A password deve ter pelo menos 4 caracteres!</div>';
    } else {
        $pwd_hash = md5($pwd_nova);
        $update = "UPDATE users SET pwd = '$pwd_hash' WHERE login = '$login'";
        if (mysqli_query($conn, $update)) {
            $mensagem = '<div class="alert alert-success">Password alterada com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-error">Erro ao alterar password!</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Admin - IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3em;
            color: #667eea;
            border: 4px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .profile-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }
        
        .info-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }
        
        .password-form {
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👤 Meu Perfil (Administrador)</h1>
            <p><?php echo $_SESSION['login']; ?></p>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="gerir_cursos.php">📚 Cursos</a>
            <a href="gerir_disciplinas.php">📖 Unidades Curriculares</a>
            <a href="gerir_planos.php">📋 Planos de Estudo</a>
            <a href="gerir_utilizadores.php">👥 Utilizadores</a>
            <a href="validar_fichas.php">📝 Fichas</a>
            <a href="../logout.php" style="float: right;">🚪 Logout</a>
        </div>
        
        <div class="content">
            <?php echo $mensagem; ?>
            
            <!-- Cabeçalho do perfil -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['login'], 0, 1)); ?>
                </div>
                <h2><?php echo $user['login']; ?></h2>
                <p><span class="badge badge-admin"><?php echo $user['GRUPO']; ?></span></p>
            </div>
            
            <!-- Informações do perfil -->
            <div class="profile-info">
                <div class="info-item">
                    <div class="info-label">Login</div>
                    <div class="info-value"><?php echo $user['login']; ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Tipo de Utilizador</div>
                    <div class="info-value">
                        <span class="badge badge-admin"><?php echo $user['GRUPO']; ?></span>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Último Acesso</div>
                    <div class="info-value"><?php echo date('d/m/Y H:i'); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Hash da Password</div>
                    <div class="info-value"><code><?php echo substr($user['pwd'], 0, 20); ?>...</code></div>
                </div>
            </div>
            
            <!-- Formulário de alteração de password -->
            <div class="card">
                <h2>🔒 Alterar Password</h2>
                
                <form method="POST" class="password-form">
                    <div class="form-group">
                        <label>Password Atual</label>
                        <input type="password" name="pwd_atual" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Nova Password</label>
                        <input type="password" name="pwd_nova" required minlength="4">
                    </div>
                    
                    <div class="form-group">
                        <label>Confirmar Nova Password</label>
                        <input type="password" name="pwd_confirmar" required minlength="4">
                    </div>
                    
                    <button type="submit" name="alterar_password" class="btn btn-block">Alterar Password</button>
                </form>
            </div>
            
            <!-- Ações do perfil -->
            <div class="card">
                <h2>⚙️ Ações</h2>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="dashboard.php" class="btn">📊 Voltar ao Dashboard</a>
                    <a href="../logout.php" class="btn" style="background: #dc3545;">🚪 Terminar Sessão</a>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Perfil de Administrador</p>
        </div>
    </div>
</body>
</html>