<?php
session_start();
require_once 'config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = mysqli_real_escape_string($conn, $_POST['login']);
    $pwd = $_POST['pwd'];
    
    // Validar se os campos não estão vazios
    if (empty($login) || empty($pwd)) {
        $erro = "Preencha todos os campos!";
    } else {
        // Buscar utilizador na base de dados
        $sql = "SELECT u.*, g.GRUPO FROM users u 
                INNER JOIN grupos g ON u.grupo = g.ID 
                WHERE u.login='$login'";
        $result = mysqli_query($conn, $sql);
        
        // Verificar se a query correu bem
        if (!$result) {
            $erro = "Erro na base de dados: " . mysqli_error($conn);
        } elseif (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verificar a password com password_verify
            if (password_verify($pwd, $user['pwd'])) {
                // Password correta
                $_SESSION['login'] = $login;
                $_SESSION['grupo'] = $user['GRUPO'];
                $_SESSION['user_id'] = $user['login'];
                
                // Redirecionar conforme o grupo
                if ($user['GRUPO'] == 'ADMIN') {
                    header('Location: index.php');
                } elseif ($user['GRUPO'] == 'FUNCIONARIO') {
                    header('Location: index.php');
                    } elseif ($user['GRUPO'] == 'ALUNO') {
                    header('Location: index.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $erro = "Login ou password incorretos!";
            }
        } else {
            $erro = "Login ou password incorretos!";
        }
    }
}

// Função para obter o IP local (para o QR Code)
function getLocalIP() {
    // Para Windows
    if (PHP_OS_FAMILY == 'Windows') {
        $output = shell_exec('ipconfig');
        preg_match('/IPv4 Address[. ]+: ([0-9.]+)/', $output, $matches);
        if (isset($matches[1])) {
            return $matches[1];
        }
    }
    
    // Fallback
    $host = gethostname();
    $localIP = gethostbyname($host);
    
    if ($localIP != '127.0.0.1' && filter_var($localIP, FILTER_VALIDATE_IP)) {
        return $localIP;
    }
    
    return $_SERVER['SERVER_ADDR'] ?? 'localhost';
}

$ip = getLocalIP();
$url = "http://$ip/ipca/login.php";
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IPCA</title>
    <link rel="stylesheet" href="estilo.css">
    <style>
        .login-container {
            max-width: 500px;
            margin: 50px auto;
        }
        
        .qr-code-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }
        
        .qr-code-box {
            background: white;
            padding: 15px;
            border-radius: 15px;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        
        .qr-code-box img {
            width: 180px;
            height: 180px;
            border-radius: 10px;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: left;
            font-size: 0.9em;
        }
        
        .url-display {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
            margin-top: 10px;
        }
        
        .demo-credentials {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="header">
            <h1>🎓 IPCA</h1>
            <p>Sistema de Gestão Académica</p>
        </div>
        
        <div class="content">
            <div class="card">
                <h2 style="text-align: center;">🔐 Login</h2>
                
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-error">
                        <strong>Erro:</strong> <?php echo $erro; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Formulário de Login -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="login">👤 Utilizador</label>
                        <input type="text" id="login" name="login" required 
                               placeholder="Digite seu login"
                               value="<?php echo isset($_POST['login']) ? $_POST['login'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="pwd">🔒 Password</label>
                        <input type="password" id="pwd" name="pwd" required 
                               placeholder="Digite sua password">
                    </div>
                    
                    <button type="submit" class="btn btn-block">Entrar</button>
                </form>
                
                <p style="text-align: center; margin-top: 20px;">
                    Não tem conta? <a href="registo.php">Registe-se como aluno</a>
                </p>
                <!-- QR Code -->
                <div class="qr-code-section">
                    <h3>📱 Acesso Mobile</h3>
                    <div class="qr-code-box">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?php echo urlencode($url); ?>" 
                             alt="QR Code">
                    </div>
                    <div class="info-box">
                        <p>Use o telemóvel na mesma rede Wi-Fi</p>
                        <div class="url-display"><?php echo $url; ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA</p>
        </div>
    </div>
</body>
</html>