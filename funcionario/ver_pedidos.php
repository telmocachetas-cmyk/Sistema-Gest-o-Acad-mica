<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['grupo'] != 'FUNCIONARIO') {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';

// Processar aprovação/rejeição de pedidos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pedido_id'])) {
    $pedido_id = mysqli_real_escape_string($conn, $_POST['pedido_id']);
    $estado = mysqli_real_escape_string($conn, $_POST['estado']);
    $observacoes = mysqli_real_escape_string($conn, $_POST['observacoes']);
    $funcionario = $_SESSION['login'];
    
    $sql = "UPDATE pedidos_matricula SET 
            estado = '$estado', 
            observacoes = '$observacoes', 
            data_decisao = NOW(), 
            funcionario_id = '$funcionario' 
            WHERE id = $pedido_id";
    
    if (mysqli_query($conn, $sql)) {
        $mensagem = '<div class="alert alert-success">Pedido ' . ($estado == 'aprovado' ? 'aprovado' : 'rejeitado') . ' com sucesso!</div>';
    } else {
        $mensagem = '<div class="alert alert-error">Erro: ' . mysqli_error($conn) . '</div>';
    }
}

// Buscar estatísticas
$pendentes = mysqli_query($conn, "SELECT COUNT(*) as total FROM pedidos_matricula WHERE estado = 'pendente'");
$total_pendentes = mysqli_fetch_assoc($pendentes)['total'];

$aprovados = mysqli_query($conn, "SELECT COUNT(*) as total FROM pedidos_matricula WHERE estado = 'aprovado'");
$total_aprovados = mysqli_fetch_assoc($aprovados)['total'];

$rejeitados = mysqli_query($conn, "SELECT COUNT(*) as total FROM pedidos_matricula WHERE estado = 'rejeitado'");
$total_rejeitados = mysqli_fetch_assoc($rejeitados)['total'];

// Buscar pedidos pendentes
$pedidos_pendentes = mysqli_query($conn, "
    SELECT pm.*, u.login as aluno_login, c.Nome as curso_nome 
    FROM pedidos_matricula pm
    JOIN users u ON pm.aluno_id = u.login
    JOIN cursos c ON pm.curso_id = c.ID
    WHERE pm.estado = 'pendente'
    ORDER BY pm.data_pedido ASC
");

// Buscar histórico de pedidos processados
$historico = mysqli_query($conn, "
    SELECT pm.*, u.login as aluno_login, c.Nome as curso_nome, 
           f.login as funcionario_login
    FROM pedidos_matricula pm
    JOIN users u ON pm.aluno_id = u.login
    JOIN cursos c ON pm.curso_id = c.ID
    LEFT JOIN users f ON pm.funcionario_id = f.login
    WHERE pm.estado IN ('aprovado', 'rejeitado')
    ORDER BY pm.data_decisao DESC
    LIMIT 20
");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Pedidos - Funcionário</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
        }
        
        .pedido-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #ffc107;
            transition: transform 0.3s;
        }
        
        .pedido-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .pedido-info {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .pedido-info {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header com título à esquerda e menu de perfil -->
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h1>📋 Gestão de Pedidos de Matrícula</h1>
                    <p><strong>Bem-vindo, <?php echo $_SESSION['login']; ?></strong></p>
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
            <a href="criar_pauta.php">📝 Criar Pauta</a>
            <a href="ver_pautas.php">📊 Ver Pautas</a>
            <a href="ver_alunos.php">👥 Alunos</a>
        </div>
        
        <div class="content">
            <?php echo $mensagem; ?>
            
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_pendentes; ?></div>
                    <p>Pendentes</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_aprovados; ?></div>
                    <p>Aprovados</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_rejeitados; ?></div>
                    <p>Rejeitados</p>
                </div>
            </div>
            
            <!-- Pedidos Pendentes -->
            <div class="card">
                <h2>⏳ Pedidos Pendentes (<?php echo $total_pendentes; ?>)</h2>
                
                <?php if (mysqli_num_rows($pedidos_pendentes) > 0): ?>
                    <?php while ($pedido = mysqli_fetch_assoc($pedidos_pendentes)): ?>
                        <div class="pedido-card">
                            <div class="pedido-info">
                                <div>
                                    <h3 style="margin-bottom: 10px;"><?php echo $pedido['aluno_login']; ?></h3>
                                    <p><strong>Curso:</strong> <?php echo $pedido['curso_nome']; ?></p>
                                    <p><strong>Data do pedido:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></p>
                                    <p><strong>Ano Letivo:</strong> <?php echo $pedido['ano_letivo']; ?></p>
                                </div>
                                
                                <div>
                                    <form method="POST">
                                        <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                        
                                        <div class="form-group">
                                            <label for="obs_<?php echo $pedido['id']; ?>">Observações:</label>
                                            <textarea name="observacoes" id="obs_<?php echo $pedido['id']; ?>" rows="2" style="width: 100%;"></textarea>
                                        </div>
                                        
                                        <div style="display: flex; gap: 10px;">
                                            <button type="submit" name="estado" value="aprovado" class="btn" style="background: #28a745; flex: 1;">
                                                ✅ Aprovar
                                            </button>
                                            <button type="submit" name="estado" value="rejeitado" class="btn" style="background: #dc3545; flex: 1;">
                                                ❌ Rejeitar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        Nenhum pedido pendente no momento.
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Histórico de Pedidos -->
            <div class="card">
                <h2>📜 Histórico de Pedidos</h2>
                
                <?php if (mysqli_num_rows($historico) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data Pedido</th>
                                <th>Aluno</th>
                                <th>Curso</th>
                                <th>Estado</th>
                                <th>Processado por</th>
                                <th>Data Decisão</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($pedido = mysqli_fetch_assoc($historico)): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?></td>
                                    <td><?php echo $pedido['aluno_login']; ?></td>
                                    <td><?php echo $pedido['curso_nome']; ?></td>
                                    <td>
                                        <?php if ($pedido['estado'] == 'aprovado'): ?>
                                            <span class="badge badge-aluno">Aprovado</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #dc3545;">Rejeitado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $pedido['funcionario_login'] ?? '-'; ?></td>
                                    <td><?php echo $pedido['data_decisao'] ? date('d/m/Y', strtotime($pedido['data_decisao'])) : '-'; ?></td>
                                    <td><?php echo $pedido['observacoes'] ?? '-'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 20px;">
                        Nenhum pedido processado ainda.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Área do Funcionário</p>
        </div>
    </div>
</body>
</html>