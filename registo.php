<?php
require_once 'init.php';
require_once 'config.php';

// Se já estiver logado, redireciona para o dashboard apropriado
if (isset($_SESSION['login'])) {
    if ($_SESSION['grupo'] == 'ADMIN') {
        header('Location: admin/dashboard.php');
    } elseif ($_SESSION['grupo'] == 'FUNCIONARIO') {
        header('Location: funcionario/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = mysqli_real_escape_string($conn, $_POST['login']);
    $pwd = $_POST['pwd'];
    $confirm_pwd = $_POST['confirm_pwd'];
    $grupo = 2; // Por defeito é ALUNO (ID=2)
    
    // Validações
    if (empty($login) || empty($pwd)) {
        $erro = "Todos os campos são obrigatórios!";
    } elseif ($pwd != $confirm_pwd) {
        $erro = "As passwords não coincidem!";
    } elseif (strlen($pwd) < 4) {
        $erro = "A password deve ter pelo menos 4 caracteres!";
    } else {
        // Verificar se login já existe
        $check_sql = "SELECT login FROM users WHERE login = '$login'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $erro = "Este login já está em uso!";
        } else {
            // Inserir novo utilizador com password_hash
            $pwd_hash = password_hash($pwd, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO users (login, pwd, grupo) VALUES ('$login', '$pwd_hash', $grupo)";
            
            if (mysqli_query($conn, $insert_sql)) {
                $sucesso = "Conta criada com sucesso! <a href='login.php'>Faça login</a>";
            } else {
                $erro = "Erro ao criar conta: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo - IPCA</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container" style="max-width: 500px; margin: 50px auto;">
        <div class="header">
            <h1>📝 Criar Conta</h1>
            <p>Registe-se no sistema IPCA</p>
        </div>
        
        <div class="content">
            <div class="card">
                <?php if ($erro): ?>
                    <div class="alert alert-error"><?php echo $erro; ?></div>
                <?php endif; ?>
                
                <?php if ($sucesso): ?>
                    <div class="alert alert-success"><?php echo $sucesso; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="login">👤 Nome de Utilizador</label>
                        <input type="text" id="login" name="login" required 
                               placeholder="Escolha um login" 
                               value="<?php echo isset($_POST['login']) ? $_POST['login'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="pwd">🔒 Password</label>
                        <input type="password" id="pwd" name="pwd" required 
                               placeholder="Mínimo 4 caracteres">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_pwd">🔒 Confirmar Password</label>
                        <input type="password" id="confirm_pwd" name="confirm_pwd" required 
                               placeholder="Digite novamente a password">
                    </div>
                    
                    <button type="submit" class="btn btn-block">Criar Conta</button>
                </form>
                
                <p style="text-align: center; margin-top: 20px;">
                    Já tem conta? <a href="login.php" style="color: #667eea;">Faça login</a>
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Sistema Académico</p>
        </div>
    </div>
</body>
</html>