<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';

// Processar criação de novo curso
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])) {
    $nome_curso = mysqli_real_escape_string($conn, $_POST['nome']);
    
    // Verificar se já existe um curso com o mesmo nome
    $check = mysqli_query($conn, "SELECT ID FROM cursos WHERE Nome = '$nome_curso'");
    if (mysqli_num_rows($check) > 0) {
        $mensagem = '<div class="alert alert-error">Já existe um curso com este nome!</div>';
    } else {
        $sql = "INSERT INTO cursos (Nome) VALUES ('$nome_curso')";
        if (mysqli_query($conn, $sql)) {
            $mensagem = '<div class="alert alert-success">Curso criado com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-error">Erro ao criar curso: ' . mysqli_error($conn) . '</div>';
        }
    }
}

// Processar edição de curso
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $nome_curso = mysqli_real_escape_string($conn, $_POST['nome']);
    
    // Verificar se já existe outro curso com o mesmo nome (excluindo o próprio)
    $check = mysqli_query($conn, "SELECT ID FROM cursos WHERE Nome = '$nome_curso' AND ID != $id");
    if (mysqli_num_rows($check) > 0) {
        $mensagem = '<div class="alert alert-error">Já existe outro curso com este nome!</div>';
    } else {
        $sql = "UPDATE cursos SET Nome='$nome_curso' WHERE ID=$id";
        if (mysqli_query($conn, $sql)) {
            $mensagem = '<div class="alert alert-success">Curso atualizado com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-error">Erro ao atualizar curso: ' . mysqli_error($conn) . '</div>';
        }
    }
}

// Processar eliminação de curso
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    // Verificar se curso tem disciplinas associadas
    $check = mysqli_query($conn, "SELECT * FROM plano_estudos WHERE CURSOS=$id");
    if (mysqli_num_rows($check) > 0) {
        $mensagem = '<div class="alert alert-error">Não é possível eliminar: curso tem disciplinas associadas!</div>';
    } else {
        $sql = "DELETE FROM cursos WHERE ID=$id";
        if (mysqli_query($conn, $sql)) {
            $mensagem = '<div class="alert alert-success">Curso eliminado com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-error">Erro ao eliminar curso: ' . mysqli_error($conn) . '</div>';
        }
    }
}

// Buscar todos os cursos
$cursos = mysqli_query($conn, "SELECT * FROM cursos ORDER BY ID");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Cursos - IPCA</title>
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
                    <h1>📚 Gestão de Cursos</h1>
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
            <a href="gerir_disciplinas.php">📖 Unidades Curriculares</a>
            <a href="gerir_planos.php">📋 Planos de Estudo</a>
            <a href="gerir_utilizadores.php">👥 Utilizadores</a>
            <a href="validar_fichas.php">📝 Fichas</a>
        </div>
        
        <div class="content">
            <?php echo $mensagem; ?>
            
            <!-- Formulário para novo curso -->
            <div class="card">
                <h2>➕ Adicionar Novo Curso</h2>
                <form method="POST" style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <input type="text" name="nome" class="form-group" placeholder="Nome do curso" required style="width: 100%; padding: 10px;">
                    </div>
                    <button type="submit" name="criar" class="btn">Criar Curso</button>
                </form>
            </div>
            
            <!-- Lista de cursos -->
            <div class="card">
                <h2>📋 Lista de Cursos</h2>
                
                <?php if (mysqli_num_rows($cursos) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome do Curso</th>
                                <th>Disciplinas</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($curso = mysqli_fetch_assoc($cursos)): 
                                // Contar disciplinas do curso
                                $count_disc = mysqli_query($conn, "SELECT COUNT(*) as total FROM plano_estudos WHERE CURSOS=" . $curso['ID']);
                                $total_disc = mysqli_fetch_assoc($count_disc)['total'];
                            ?>
                            <tr>
                                <td>#<?php echo $curso['ID']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($curso['Nome']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-admin"><?php echo $total_disc; ?> disciplinas</span>
                                </td>
                                <td>
                                    <!-- Botão Editar -->
                                    <button onclick="editarCurso(<?php echo $curso['ID']; ?>, '<?php echo htmlspecialchars($curso['Nome']); ?>')" 
                                            class="btn" style="background: #28a745; padding: 5px 10px;">
                                        ✏️ Editar
                                    </button>
                                    
                                    <!-- Botão Eliminar -->
                                    <a href="?eliminar=<?php echo $curso['ID']; ?>" 
                                       class="btn" style="background: #dc3545; padding: 5px 10px;"
                                       onclick="return confirm('Tem a certeza que deseja eliminar este curso?')">
                                        🗑️ Eliminar
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        Nenhum curso encontrado. Crie o primeiro curso!
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal de Edição -->
    <div id="modalEditar" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; max-width: 500px; margin: 100px auto; padding: 30px; border-radius: 15px;">
            <h3 style="margin-bottom: 20px;">✏️ Editar Curso</h3>
            <form method="POST" id="formEditar">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Nome do Curso:</label>
                    <input type="text" name="nome" id="edit_nome" required style="width: 100%; padding: 10px;">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="fecharModal()" class="btn" style="background: #6c757d;">Cancelar</button>
                    <button type="submit" name="editar" class="btn">Guardar Alterações</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; 2026 IPCA - Gestão de Cursos</p>
    </div>
    
    <script>
        function editarCurso(id, nome) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nome').value = nome;
            document.getElementById('modalEditar').style.display = 'block';
        }
        
        function fecharModal() {
            document.getElementById('modalEditar').style.display = 'none';
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            var modal = document.getElementById('modalEditar');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>