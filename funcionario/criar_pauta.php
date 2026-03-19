<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['grupo'] != 'FUNCIONARIO') {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';

// Buscar UCs (unidades curriculares) da tabela disciplinas
$ucs = mysqli_query($conn, "SELECT ID, Nome_disc FROM disciplinas ORDER BY Nome_disc");

// Processar criação de pauta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar_pauta'])) {
    $uc_id = mysqli_real_escape_string($conn, $_POST['uc_id']);
    $ano_letivo = mysqli_real_escape_string($conn, $_POST['ano_letivo']);
    $epoca = mysqli_real_escape_string($conn, $_POST['epoca']);
    $funcionario = $_SESSION['login'];
    
    // Verificar se já existe pauta para esta UC/ano/época
    $check = mysqli_query($conn, "SELECT id FROM pautas WHERE uc_id = $uc_id AND ano_letivo = '$ano_letivo' AND epoca = '$epoca'");
    
    if (mysqli_num_rows($check) > 0) {
        $mensagem = '<div class="alert alert-error">Já existe uma pauta para esta UC/época!</div>';
    } else {
        // Iniciar transação
        mysqli_begin_transaction($conn);
        
        try {
            // Criar pauta
            $sql = "INSERT INTO pautas (uc_id, ano_letivo, epoca, data_criacao, funcionario_id) 
                    VALUES ($uc_id, '$ano_letivo', '$epoca', NOW(), '$funcionario')";
            
            if (!mysqli_query($conn, $sql)) {
                throw new Exception("Erro ao criar pauta: " . mysqli_error($conn));
            }
            
            $pauta_id = mysqli_insert_id($conn);
            
            // Buscar alunos elegíveis (com matrícula aprovada e que tenham esta UC no plano)
            $alunos_query = "
                SELECT DISTINCT u.login 
                FROM users u
                INNER JOIN pedidos_matricula pm ON u.login = pm.aluno_id
                INNER JOIN plano_estudos pe ON pm.curso_id = pe.CURSOS
                WHERE u.grupo = 2 
                AND pm.estado = 'aprovado'
                AND pe.DISCIPLINA = $uc_id
                ORDER BY u.login";
            
            $alunos = mysqli_query($conn, $alunos_query);
            
            if (!$alunos) {
                throw new Exception("Erro ao buscar alunos: " . mysqli_error($conn));
            }
            
            $total_alunos = mysqli_num_rows($alunos);
            
            if ($total_alunos == 0) {
                throw new Exception("Não existem alunos elegíveis para esta UC (sem matrícula aprovada ou UC não incluída no plano do curso).");
            }
            
            // Inserir registos de notas para cada aluno elegível
            $stmt = mysqli_prepare($conn, "INSERT INTO notas (pauta_id, aluno_id, funcionario_id) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iss", $pauta_id, $aluno_login, $funcionario);
            
            $inseridos = 0;
            while ($aluno = mysqli_fetch_assoc($alunos)) {
                $aluno_login = $aluno['login'];
                if (mysqli_stmt_execute($stmt)) {
                    $inseridos++;
                }
            }
            mysqli_stmt_close($stmt);
            
            // Commit da transação
            mysqli_commit($conn);
            
            $mensagem = '<div class="alert alert-success">';
            $mensagem .= "Pauta criada com sucesso! $inseridos alunos elegíveis inscritos. ";
            $mensagem .= '<a href="lancar_notas.php?pauta_id=' . $pauta_id . '" class="btn" style="background: #28a745; margin-left: 10px;">Lançar Notas</a>';
            $mensagem .= '</div>';
            
        } catch (Exception $e) {
            // Rollback em caso de erro
            mysqli_rollback($conn);
            $mensagem = '<div class="alert alert-error">' . $e->getMessage() . '</div>';
        }
    }
}

// Buscar estatísticas de UCs com pautas
$estatisticas = mysqli_query($conn, "
    SELECT d.Nome_disc, COUNT(p.id) as total_pautas
    FROM disciplinas d
    LEFT JOIN pautas p ON d.ID = p.uc_id
    GROUP BY d.ID
    ORDER BY total_pautas DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Pauta - IPCA</title>
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
        
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-mini-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-mini-number {
            font-size: 1.5em;
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
                    <h1>📝 Criar Pauta de Avaliação</h1>
                    <p style="margin-left: 20px;"><strong>Bem-vindo, <?php echo $_SESSION['login']; ?></strong></p>
                </div>
                <div class="menu-perfil">
                    <span class="profile-badge" style="background: #17a2b8;">FUNCIONÁRIO</span>
                    <div class="menu-perfil-content">
                        <a href="../index.php">🏠 Site Principal</a>
                        <a href="perfil_funcionario.php">👤 Meu Perfil</a>
                        <a href="../logout.php">🚪 Logout</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="ver_pautas.php">📊 Ver Pautas</a>
            <a href="ver_pedidos.php">📋 Pedidos</a>
            <a href="ver_alunos.php">👥 Alunos</a>
        </div>
        
        <div class="content">
            <?php echo $mensagem; ?>
            
            <div class="card">
                <h2>➕ Criar Nova Pauta</h2>
                
                <form method="POST">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <div class="form-group">
                            <label>Unidade Curricular:</label>
                            <select name="uc_id" required>
                                <option value="">Selecione...</option>
                                <?php while ($uc = mysqli_fetch_assoc($ucs)): ?>
                                    <option value="<?php echo $uc['ID']; ?>">
                                        <?php echo $uc['Nome_disc']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Ano Letivo:</label>
                            <select name="ano_letivo" required>
                                <option value="<?php echo date('Y') . '/' . (date('Y')+1); ?>">
                                    <?php echo date('Y') . '/' . (date('Y')+1); ?>
                                </option>
                                <option value="<?php echo (date('Y')-1) . '/' . date('Y'); ?>">
                                    <?php echo (date('Y')-1) . '/' . date('Y'); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Época:</label>
                            <select name="epoca" required>
                                <option value="Normal">Normal</option>
                                <option value="Recurso">Recurso</option>
                                <option value="Especial">Especial</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <button type="submit" name="criar_pauta" class="btn" style="padding: 12px 40px;">Criar Pauta</button>
                    </div>
                </form>
                
                <div class="stats-mini">
                    <div class="stat-mini-card">
                        <div class="stat-mini-number"><?php 
                            $total_ucs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM disciplinas"))['total'];
                            echo $total_ucs;
                        ?></div>
                        <div>UCs Disponíveis</div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-number"><?php 
                            $total_pautas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pautas"))['total'];
                            echo $total_pautas;
                        ?></div>
                        <div>Pautas Criadas</div>
                    </div>
                </div>
            </div>
            
            <!-- UCs com mais pautas -->
            <?php if ($estatisticas && mysqli_num_rows($estatisticas) > 0): ?>
            <div class="card">
                <h2>📊 UCs com Mais Pautas</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Unidade Curricular</th>
                            <th>Total de Pautas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($stat = mysqli_fetch_assoc($estatisticas)): ?>
                        <tr>
                            <td><?php echo $stat['Nome_disc']; ?></td>
                            <td><span class="badge badge-admin"><?php echo $stat['total_pautas']; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Área do Funcionário</p>
        </div>
    </div>
</body>
</html>