<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['grupo'] != 'FUNCIONARIO') {
    header('Location: ../login.php');
    exit;
}

$pauta_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pauta_id == 0) {
    header('Location: ver_pautas.php');
    exit;
}

// Buscar dados da pauta
$query_pauta = "
    SELECT p.*, d.Nome_disc as uc_nome, u.login as funcionario_login
    FROM pautas p
    JOIN disciplinas d ON p.uc_id = d.ID
    JOIN users u ON p.funcionario_id = u.login
    WHERE p.id = $pauta_id
";
$result_pauta = mysqli_query($conn, $query_pauta);
$pauta = mysqli_fetch_assoc($result_pauta);

if (!$pauta) {
    header('Location: ver_pautas.php');
    exit;
}

// Buscar notas dos alunos com informação da ficha
$query_notas = "
    SELECT n.*, 
           u.login as aluno_login,
           COALESCE(f.nome_completo, u.nome_completo, 'Nome não preenchido') as nome_completo
    FROM notas n
    JOIN users u ON n.aluno_id = u.login
    LEFT JOIN fichas_aluno f ON u.login = f.aluno_id
    WHERE n.pauta_id = $pauta_id
    ORDER BY u.login
";
$notas = mysqli_query($conn, $query_notas);

// Estatísticas da pauta
$total_alunos = mysqli_num_rows($notas);
$notas_lancadas = 0;
$aprovados = 0;
$reprovados = 0;
$soma_notas = 0;

// Guardar notas num array para poder calcular estatísticas e depois mostrar
$dados_notas = [];
while ($nota = mysqli_fetch_assoc($notas)) {
    $dados_notas[] = $nota;
    if ($nota['nota'] !== null) {
        $notas_lancadas++;
        $soma_notas += $nota['nota'];
        if ($nota['nota'] >= 9.5) {
            $aprovados++;
        } else {
            $reprovados++;
        }
    }
}
$media = $notas_lancadas > 0 ? round($soma_notas / $notas_lancadas, 1) : 0;
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Pauta - IPCA</title>
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
        
        /* Stats cards */
        .stats-grid-small {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card-mini {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-value-mini {
            font-size: 1.8em;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label-mini {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        
        .info-pauta {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1.1em;
            font-weight: bold;
            color: #333;
        }
        
        .nota-aprovado {
            background: #28a745 !important;
            color: white;
            font-weight: bold;
        }
        
        .nota-reprovado {
            background: #dc3545 !important;
            color: white;
            font-weight: bold;
        }
        
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-btn:hover {
            text-decoration: underline;
        }
        
        .btn-lancar {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-lancar:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header com título à esquerda e menu de perfil -->
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h1>📊 Detalhes da Pauta</h1>
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
            <a href="criar_pauta.php">📝 Criar Pauta</a>
            <a href="ver_pedidos.php">📋 Pedidos</a>
            <a href="ver_alunos.php">👥 Alunos</a>
        </div>
        
        <div class="content">
            <a href="ver_pautas.php" class="back-btn">← Voltar para lista de pautas</a>
            
            <div class="card">
                <h2><?php echo $pauta['uc_nome']; ?></h2>
                
                <!-- Informações da pauta -->
                <div class="info-pauta">
                    <div class="info-item">
                        <span class="info-label">Ano Letivo</span>
                        <span class="info-value"><?php echo $pauta['ano_letivo']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Época</span>
                        <span class="info-value"><?php echo $pauta['epoca']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Data de Criação</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($pauta['data_criacao'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Criado por</span>
                        <span class="info-value"><?php echo $pauta['funcionario_login']; ?></span>
                    </div>
                </div>
                
                <!-- Estatísticas rápidas -->
                <div class="stats-grid-small">
                    <div class="stat-card-mini">
                        <div class="stat-value-mini"><?php echo $total_alunos; ?></div>
                        <div class="stat-label-mini">Total Alunos</div>
                    </div>
                    <div class="stat-card-mini">
                        <div class="stat-value-mini"><?php echo $notas_lancadas; ?></div>
                        <div class="stat-label-mini">Notas Lançadas</div>
                    </div>
                    <div class="stat-card-mini">
                        <div class="stat-value-mini"><?php echo $aprovados; ?></div>
                        <div class="stat-label-mini">Aprovados</div>
                    </div>
                    <div class="stat-card-mini">
                        <div class="stat-value-mini"><?php echo $reprovados; ?></div>
                        <div class="stat-label-mini">Reprovados</div>
                    </div>
                    <div class="stat-card-mini">
                        <div class="stat-value-mini"><?php echo $media; ?></div>
                        <div class="stat-label-mini">Média</div>
                    </div>
                </div>
                
                <!-- Botão para lançar notas (se ainda não todas lançadas) -->
                <?php if ($notas_lancadas < $total_alunos): ?>
                <div style="text-align: center; margin: 20px 0;">
                    <a href="lancar_notas.php?pauta_id=<?php echo $pauta_id; ?>" class="btn-lancar">
                        ✏️ Lançar Notas Pendentes
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Tabela de notas -->
                <h3 style="margin: 30px 0 15px 0;">Notas dos Alunos</h3>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Nome</th>
                            <th>Nota</th>
                            <th>Estado</th>
                            <th>Data Registo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dados_notas as $nota): ?>
                        <tr>
                            <td><strong><?php echo $nota['aluno_login']; ?></strong></td>
                            <td><?php echo $nota['nome_completo']; ?></td>
                            <td>
                                <?php if ($nota['nota'] !== null): ?>
                                    <span class="badge <?php echo $nota['nota'] >= 9.5 ? 'badge-aluno' : 'badge'; ?>" 
                                          style="<?php echo $nota['nota'] < 9.5 ? 'background: #dc3545;' : ''; ?>">
                                        <?php echo number_format($nota['nota'], 1); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($nota['nota'] !== null): ?>
                                    <?php if ($nota['nota'] >= 9.5): ?>
                                        <span class="badge badge-aluno">Aprovado</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #dc3545;">Reprovado</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-admin">Pendente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($nota['data_registo']): ?>
                                    <?php echo date('d/m/Y H:i', strtotime($nota['data_registo'])); ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="ver_pautas.php" class="btn">← Voltar</a>
                    <a href="lancar_notas.php?pauta_id=<?php echo $pauta_id; ?>" class="btn" style="background: #28a745;">✏️ Lançar/Editar Notas</a>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Área do Funcionário</p>
        </div>
    </div>
</body>
</html>