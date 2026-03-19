<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';

// Processar criação de novo utilizador (admin pode criar)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])) {
    $login = mysqli_real_escape_string($conn, $_POST['login']);
    $pwd = md5($_POST['pwd']); // MD5 como na base original
    $grupo = mysqli_real_escape_string($conn, $_POST['grupo']);
    
    // Verificar se login já existe
    $check = mysqli_query($conn, "SELECT login FROM users WHERE login='$login'");
    if (mysqli_num_rows($check) > 0) {
        $mensagem = '<div class="alert alert-error">Este login já existe!</div>';
    } else {
        $sql = "INSERT INTO users (login, pwd, grupo) VALUES ('$login', '$pwd', $grupo)";
        if (mysqli_query($conn, $sql)) {
            $mensagem = '<div class="alert alert-success">Utilizador criado com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-error">Erro ao criar utilizador: ' . mysqli_error($conn) . '</div>';
        }
    }
}

// Processar edição de utilizador
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar'])) {
    $login_original = mysqli_real_escape_string($conn, $_POST['login_original']);
    $login_novo = mysqli_real_escape_string($conn, $_POST['login']);
    $grupo = mysqli_real_escape_string($conn, $_POST['grupo']);
    
    // Se password foi alterada
    if (!empty($_POST['pwd'])) {
        $pwd = md5($_POST['pwd']);
        $sql = "UPDATE users SET login='$login_novo', pwd='$pwd', grupo=$grupo WHERE login='$login_original'";
    } else {
        $sql = "UPDATE users SET login='$login_novo', grupo=$grupo WHERE login='$login_original'";
    }
    
    if (mysqli_query($conn, $sql)) {
        $mensagem = '<div class="alert alert-success">Utilizador atualizado com sucesso!</div>';
    } else {
        $mensagem = '<div class="alert alert-error">Erro ao atualizar utilizador: ' . mysqli_error($conn) . '</div>';
    }
}

// Processar eliminação de utilizador
if (isset($_GET['eliminar'])) {
    $login = $_GET['eliminar'];
    
    // Não permitir eliminar o próprio admin
    if ($login == $_SESSION['login']) {
        $mensagem = '<div class="alert alert-error">Não pode eliminar o seu próprio utilizador!</div>';
    } else {
        $sql = "DELETE FROM users WHERE login='$login'";
        if (mysqli_query($conn, $sql)) {
            $mensagem = '<div class="alert alert-success">Utilizador eliminado com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-error">Erro ao eliminar utilizador: ' . mysqli_error($conn) . '</div>';
        }
    }
}

// Buscar todos os utilizadores com informação do grupo
$utilizadores = mysqli_query($conn, 
    "SELECT u.*, g.GRUPO FROM users u 
     INNER JOIN grupos g ON u.grupo = g.ID 
     ORDER BY u.login");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Utilizadores - IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
    <style>
        /* Ajustes para o menu */
        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            background: #f8f9fa;
            padding: 15px 30px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .nav a {
            color: #667eea;
            text-decoration: none;
            padding: 8px 15px;
            margin: 0 2px;
            border-radius: 25px;
            transition: all 0.3s;
            font-weight: 500;
            white-space: nowrap;
            font-size: 0.95em;
        }
        
        .nav a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-small {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 5px 15px;
            border-radius: 20px;
            margin-top: 10px;
            font-size: 0.9em;
            transition: all 0.3s;
        }
        
        .btn-small:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header com título à esquerda e menu de perfil -->
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h1>👥 Gestão de Utilizadores</h1>
                    <p style="margin-left: 20px;"><strong>Bem-vindo, <?php echo $_SESSION['login']; ?></strong></p>
                </div>
                <div class="menu-perfil">
                    <span class="profile-badge profile-admin">ADMIN</span>
                    <div class="menu-perfil-content">
                        <a href="../index.php">🏠 Site Principal</a>
                        <a href="perfil_admin.php">👤 Meu Perfil</a>
                        <a href="../logout.php">🚪 Logout</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="gerir_cursos.php">📚 Cursos</a>
            <a href="gerir_disciplinas.php">📖 Unidades Curriculares</a>
            <a href="gerir_planos.php">📋 Planos de Estudo</a>
            <a href="validar_fichas.php">📝 Fichas</a>
        </div>
        
        <div class="content">
            <?php echo $mensagem; ?>
            
            <!-- Formulário para novo utilizador -->
            <div class="card">
                <h2>➕ Adicionar Novo Utilizador</h2>
                <form method="POST" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                    <div>
                        <input type="text" name="login" placeholder="Login" required style="width: 100%; padding: 10px;">
                    </div>
                    <div>
                        <input type="password" name="pwd" placeholder="Password" required style="width: 100%; padding: 10px;">
                    </div>
                    <div>
                        <select name="grupo" required style="width: 100%; padding: 10px;">
                            <option value="">Tipo</option>
                            <option value="1">ADMIN</option>
                            <option value="2">ALUNO</option>
                            <option value="3">FUNCIONARIO</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" name="criar" class="btn" style="width: 100%;">Criar</button>
                    </div>
                </form>
            </div>
            
            <!-- Lista de utilizadores -->
            <div class="card">
                <h2>📋 Lista de Utilizadores</h2>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Login</th>
                            <th>Tipo</th>
                            <th>Password (hash)</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($utilizadores)): ?>
                        <tr>
                            <td><strong><?php echo $user['login']; ?></strong></td>
                            <td>
                                <span class="badge <?php echo $user['GRUPO'] == 'ADMIN' ? 'badge-admin' : 'badge-aluno'; ?>">
                                    <?php echo $user['GRUPO']; ?>
                                </span>
                            </td>
                            <td><code style="font-size: 0.8em;"><?php echo substr($user['pwd'], 0, 20); ?>...</code></td>
                            <td>
                                <button onclick="editarUtilizador('<?php echo $user['login']; ?>', <?php echo $user['grupo']; ?>)" 
                                        class="btn" style="background: #28a745; padding: 5px 10px;">
                                    ✏️ Editar
                                </button>
                                
                                <?php if ($user['login'] != $_SESSION['login']): ?>
                                <a href="?eliminar=<?php echo $user['login']; ?>" 
                                   class="btn" style="background: #dc3545; padding: 5px 10px;"
                                   onclick="return confirm('Tem a certeza que deseja eliminar este utilizador?')">
                                    🗑️ Eliminar
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal de Edição -->
    <div id="modalEditar" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; max-width: 500px; margin: 100px auto; padding: 30px; border-radius: 15px;">
            <h3 style="margin-bottom: 20px;">✏️ Editar Utilizador</h3>
            <form method="POST" id="formEditar">
                <input type="hidden" name="login_original" id="edit_login_original">
                <div class="form-group">
                    <label>Login:</label>
                    <input type="text" name="login" id="edit_login" required style="width: 100%; padding: 10px;">
                </div>
                <div class="form-group">
                    <label>Nova Password (deixar vazio para manter):</label>
                    <input type="password" name="pwd" id="edit_pwd" style="width: 100%; padding: 10px;">
                </div>
                <div class="form-group">
                    <label>Tipo:</label>
                    <select name="grupo" id="edit_grupo" required style="width: 100%; padding: 10px;">
                        <option value="1">ADMIN</option>
                        <option value="2">ALUNO</option>
                        <option value="3">FUNCIONARIO</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="fecharModal()" class="btn" style="background: #6c757d;">Cancelar</button>
                    <button type="submit" name="editar" class="btn">Guardar Alterações</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; 2026 IPCA - Gestão de Utilizadores</p>
    </div>
    
    <script>
        function editarUtilizador(login, grupo) {
            document.getElementById('edit_login_original').value = login;
            document.getElementById('edit_login').value = login;
            document.getElementById('edit_grupo').value = grupo;
            document.getElementById('edit_pwd').value = '';
            document.getElementById('modalEditar').style.display = 'block';
        }
        
        function fecharModal() {
            document.getElementById('modalEditar').style.display = 'none';
        }
        
        window.onclick = function(event) {
            var modal = document.getElementById('modalEditar');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>