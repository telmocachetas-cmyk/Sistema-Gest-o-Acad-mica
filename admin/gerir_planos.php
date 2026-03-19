<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';

// Processar formulário de adicionar disciplina ao plano
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adicionar'])) {
    $curso_id = mysqli_real_escape_string($conn, $_POST['curso_id']);
    $disciplina_id = mysqli_real_escape_string($conn, $_POST['disciplina_id']);
    $ano = (int)$_POST['ano'];
    $semestre = (int)$_POST['semestre'];
    
    // Verificar se já existe (impedir duplicações no mesmo curso/ano/semestre)
    $check = mysqli_query($conn, "SELECT * FROM plano_estudos 
                                   WHERE CURSOS=$curso_id 
                                   AND DISCIPLINA=$disciplina_id 
                                   AND ano=$ano 
                                   AND semestre=$semestre");
    
    if (mysqli_num_rows($check) == 0) {
        $sql = "INSERT INTO plano_estudos (CURSOS, DISCIPLINA, ano, semestre) 
                VALUES ($curso_id, $disciplina_id, $ano, $semestre)";
        if (mysqli_query($conn, $sql)) {
            $mensagem = '<div class="alert alert-success">Disciplina adicionada ao plano com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-error">Erro: ' . mysqli_error($conn) . '</div>';
        }
    } else {
        $mensagem = '<div class="alert alert-error">Esta disciplina já está no plano do curso para este ano/semestre!</div>';
    }
}

// Processar remoção
if (isset($_GET['remover'])) {
    $id = $_GET['remover'];
    mysqli_query($conn, "DELETE FROM plano_estudos WHERE id=$id");
    $mensagem = '<div class="alert alert-success">Disciplina removida do plano!</div>';
}

// Buscar todos os cursos
$cursos = mysqli_query($conn, "SELECT * FROM cursos");
// Buscar todas as disciplinas
$disciplinas = mysqli_query($conn, "SELECT * FROM disciplinas ORDER BY Nome_disc");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Planos de Estudo - IPCA</title>
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
        
        .form-plano {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr auto;
            gap: 10px;
            align-items: end;
        }
        
        @media (max-width: 768px) {
            .form-plano {
                grid-template-columns: 1fr;
            }
        }
        
        .curso-plano {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
        }
        
        .semestre-titulo {
            background: #f8f9fa;
            padding: 10px;
            margin: 15px 0 10px 0;
            border-radius: 5px;
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header com título à esquerda e menu de perfil -->
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h1>📋 Gestão de Planos de Estudo</h1>
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
            <a href="gerir_utilizadores.php">👥 Utilizadores</a>
            <a href="validar_fichas.php">📝 Fichas</a>
        </div>
        
        <div class="content">
            <?php echo $mensagem; ?>
            
            <div class="card">
                <h2>➕ Adicionar UC ao Plano</h2>
                <form method="POST" class="form-plano">
                    <div class="form-group">
                        <label>Curso:</label>
                        <select name="curso_id" required>
                            <option value="">Selecione um curso</option>
                            <?php 
                            mysqli_data_seek($cursos, 0);
                            while($curso = mysqli_fetch_assoc($cursos)): ?>
                            <option value="<?php echo $curso['ID']; ?>"><?php echo $curso['Nome']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Unidade Curricular:</label>
                        <select name="disciplina_id" required>
                            <option value="">Selecione uma UC</option>
                            <?php 
                            mysqli_data_seek($disciplinas, 0);
                            while($disciplina = mysqli_fetch_assoc($disciplinas)): ?>
                            <option value="<?php echo $disciplina['ID']; ?>"><?php echo $disciplina['Nome_disc']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Ano:</label>
                        <select name="ano" required>
                            <option value="1">1º Ano</option>
                            <option value="2">2º Ano</option>
                            <option value="3">3º Ano</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Semestre:</label>
                        <select name="semestre" required>
                            <option value="1">1º Semestre</option>
                            <option value="2">2º Semestre</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="adicionar" class="btn">Adicionar</button>
                </form>
            </div>
            
            <div class="card">
                <h2>📋 Planos de Estudo Atuais</h2>
                
                <?php
                // Buscar todos os cursos com suas disciplinas organizadas por ano/semestre
                $cursos_list = mysqli_query($conn, "SELECT * FROM cursos ORDER BY ID");
                
                while($curso = mysqli_fetch_assoc($cursos_list)):
                ?>
                
                <div class="curso-plano">
                    <h3 style="color: #667eea; margin-bottom: 15px;"><?php echo $curso['Nome']; ?></h3>
                    
                    <?php for($ano = 1; $ano <= 2; $ano++): ?>
                        <?php for($semestre = 1; $semestre <= 2; $semestre++): 
                            // Buscar disciplinas deste curso para este ano/semestre
                            $sql = "SELECT pe.id as plano_id, d.* FROM disciplinas d 
                                    INNER JOIN plano_estudos pe ON d.ID = pe.DISCIPLINA 
                                    WHERE pe.CURSOS = " . $curso['ID'] . " 
                                    AND pe.ano = $ano 
                                    AND pe.semestre = $semestre
                                    ORDER BY d.Nome_disc";
                            $disciplinas_curso = mysqli_query($conn, $sql);
                            
                            if (mysqli_num_rows($disciplinas_curso) > 0):
                        ?>
                            <div class="semestre-titulo">
                                <?php echo $ano; ?>º Ano - <?php echo $semestre; ?>º Semestre
                            </div>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Unidade Curricular</th>
                                        <th>Código</th>
                                        <th>ECTS</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($disc = mysqli_fetch_assoc($disciplinas_curso)): ?>
                                    <tr>
                                        <td>#<?php echo $disc['ID']; ?></td>
                                        <td><?php echo $disc['Nome_disc']; ?></td>
                                        <td><?php echo $disc['codigo'] ?? '-'; ?></td>
                                        <td><?php echo $disc['creditos'] ?? '6'; ?></td>
                                        <td>
                                            <a href="?remover=<?php echo $disc['plano_id']; ?>" 
                                               class="btn" style="background: #dc3545; padding: 5px 10px; font-size: 0.9em;"
                                               onclick="return confirm('Remover esta UC do plano?')">
                                                Remover
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php 
                            endif;
                        endfor; 
                    endfor; 
                    
                    // Verificar se o curso não tem nenhuma disciplina
                    $sql_total = "SELECT COUNT(*) as total FROM plano_estudos WHERE CURSOS = " . $curso['ID'];
                    $total = mysqli_fetch_assoc(mysqli_query($conn, $sql_total))['total'];
                    
                    if ($total == 0):
                    ?>
                        <p style="color: #666; text-align: center; padding: 20px;">
                            Nenhuma unidade curricular atribuída a este curso.
                        </p>
                    <?php endif; ?>
                </div>
                
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; 2026 IPCA - Gestão de Planos de Estudo</p>
    </div>
</body>
</html>