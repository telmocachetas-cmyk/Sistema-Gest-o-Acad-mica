<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['grupo'] != 'ALUNO') {
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
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Aluno - IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
            color: #28a745;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👤 Meu Perfil (Aluno)</h1>
            <p><?php echo $_SESSION['login']; ?></p>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="minha_matricula.php">🎓 Matrícula</a>
            <a href="plano_estudos.php">📚 Plano de Estudos</a>
            <a href="ficha.php">📝 Ficha Pessoal</a>
            <a href="../logout.php" style="float: right;">🚪 Logout</a>
        </div>
        
        <div class="content">
            <?php echo $mensagem; ?>
            
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['login'], 0, 1)); ?>
                </div>
                <h2><?php echo $user['login']; ?></h2>
                <p><span class="badge badge-aluno"><?php echo $user['GRUPO']; ?></span></p>
            </div>
            
            <div class="profile-info">
                <div class="info-item">
                    <div class="info-label">Login</div>
                    <div class="info-value"><?php echo $user['login']; ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Tipo</div>
                    <div class="info-value">
                        <span class="badge badge-aluno"><?php echo $user['GRUPO']; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2>🔒 Alterar Password</h2>
                <form method="POST" style="max-width: 500px; margin: 0 auto;">
                    <div class="form-group">
                        <label>Password Atual</label>
                        <input type="password" name="pwd_atual" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Nova Password</label>
                        <input type="password" name="pwd_nova" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirmar Password</label>
                        <input type="password" name="pwd_confirmar" required>
                    </div>
                    
                    <button type="submit" name="alterar_password" class="btn btn-block">Alterar Password</button>
                </form>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Perfil de Aluno</p>
        </div>
    </div>
</body>
</html>