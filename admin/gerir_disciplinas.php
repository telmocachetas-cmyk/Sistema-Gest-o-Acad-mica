<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';

// Processar criação de nova disciplina
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])) {
    $nome_disc = mysqli_real_escape_string($conn, $_POST['nome']);
    
    // Verificar se já existe uma UC com o mesmo nome
    $check = mysqli_query($conn, "SELECT ID FROM disciplinas WHERE Nome_disc = '$nome_disc'");
    if (mysqli_num_rows($check) > 0) {
        $mensagem = '<div class="alert alert-error">Já existe uma Unidade Curricular com este nome!</div>';
    } else {
        $sql = "INSERT INTO disciplinas (Nome_disc) VALUES ('$nome_disc')";
        if (mysqli_query($conn, $sql)) {
            $mensagem = '<div class="alert alert-success">UC criada com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-error">Erro ao criar UC: ' . mysqli_error($conn) . '</div>';
        }
    }
}

// Processar edição de disciplina
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $nome_disc = mysqli_real_escape_string($conn, $_POST['nome']);
    
    // Verificar se já existe outra UC com o mesmo nome (excluindo a própria)
    $check = mysqli_query($conn, "SELECT ID FROM disciplinas WHERE Nome_disc = '$nome_disc' AND ID != $id");
    if (mysqli_num_rows($check) > 0) {
        $mensagem = '<div class="alert alert-error">Já existe outra Unidade Curricular com este nome!</div>';
    } else {
        $sql = "UPDATE disciplinas SET Nome_disc='$nome_disc' WHERE ID=$id";
        if (mysqli_query($conn, $sql)) {
            $mensagem = '<div class="alert alert-success">UC atualizada com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-error">Erro ao atualizar UC: ' . mysqli_error($conn) . '</div>';
        }
    }
}

// Processar eliminação de disciplina
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    // Verificar se disciplina está associada a algum curso
    $check = mysqli_query($conn, "SELECT * FROM plano_estudos WHERE DISCIPLINA=$id");
    if (mysqli_num_rows($check) > 0) {
        $mensagem = '<div class="alert alert-error">Não é possível eliminar: UC está em uso num plano de estudos!</div>';
    } else {
        $sql = "DELETE FROM disciplinas WHERE ID=$id";
        if (mysqli_query($conn, $sql)) {
            $mensagem = '<div class="alert alert-success">UC eliminada com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-error">Erro ao eliminar UC: ' . mysqli_error($conn) . '</div>';
        }
    }
}

// Buscar todas as disciplinas
$disciplinas = mysqli_query($conn, "SELECT * FROM disciplinas ORDER BY ID");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Unidades Curriculares - IPCA</title>
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
                    <h1>📖 Gestão de Unidades Curriculares</h1>
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
            <a href="gerir_planos.php">📋 Planos de Estudo</a>
            <a href="gerir_utilizadores.php">👥 Utilizadores</a>
            <a href="validar_fichas.php">📝 Fichas</a>
        </div>
        
        <div class="content">
            <?php echo $mensagem; ?>
            
            <!-- Formulário para nova disciplina -->
            <div class="card">
                <h2>➕ Adicionar Nova Unidade Curricular</h2>
                <form method="POST" style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <input type="text" name="nome" class="form-group" placeholder="Nome da Unidade Curricular" required style="width: 100%; padding: 10px;">
                    </div>
                    <button type="submit" name="criar" class="btn">Criar Unidade Curricular</button>
                </form>
            </div>
            
            <!-- Lista de disciplinas -->
            <div class="card">
                <h2>📋 Lista de Unidades Curriculares</h2>
                
                <?php if (mysqli_num_rows($disciplinas) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome da Unidade Curricular</th>
                                <th>Cursos que a usam</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($disc = mysqli_fetch_assoc($disciplinas)): 
                                // Contar cursos que usam esta disciplina
                                $count_cursos = mysqli_query($conn, "SELECT COUNT(*) as total FROM plano_estudos WHERE DISCIPLINA=" . $disc['ID']);
                                $total_cursos = mysqli_fetch_assoc($count_cursos)['total'];
                            ?>
                            <tr>
                                <td>#<?php echo $disc['ID']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($disc['Nome_disc']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-admin"><?php echo $total_cursos; ?> cursos</span>
                                </td>
                                <td>
                                    <!-- Botão Editar -->
                                    <button onclick="editarDisciplina(<?php echo $disc['ID']; ?>, '<?php echo htmlspecialchars($disc['Nome_disc']); ?>')" 
                                            class="btn" style="background: #28a745; padding: 5px 10px;">
                                        ✏️ Editar
                                    </button>
                                    
                                    <!-- Botão Eliminar -->
                                    <a href="?eliminar=<?php echo $disc['ID']; ?>" 
                                       class="btn" style="background: #dc3545; padding: 5px 10px;"
                                       onclick="return confirm('Tem a certeza que deseja eliminar esta UC?')">
                                        🗑️ Eliminar
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        Nenhuma Unidade Curricular encontrada. Crie a primeira UC!
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal de Edição -->
    <div id="modalEditar" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; max-width: 500px; margin: 100px auto; padding: 30px; border-radius: 15px;">
            <h3 style="margin-bottom: 20px;">✏️ Editar Unidade Curricular</h3>
            <form method="POST" id="formEditar">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Nome da Unidade Curricular:</label>
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
        <p>&copy; 2026 IPCA - Gestão de Unidades Curriculares</p>
    </div>
    
    <script>
        function editarDisciplina(id, nome) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nome').value = nome;
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