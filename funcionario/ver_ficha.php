<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['grupo'] != 'FUNCIONARIO') {
    header('Location: ../login.php');
    exit;
}

$aluno_login = isset($_GET['aluno']) ? mysqli_real_escape_string($conn, $_GET['aluno']) : '';

if (empty($aluno_login)) {
    header('Location: ver_alunos.php');
    exit;
}

// Buscar dados da ficha do aluno
$query = "
    SELECT 
        f.*,
        u.email,
        u.login,
        c.Nome as curso_nome
    FROM fichas_aluno f
    JOIN users u ON f.aluno_id = u.login
    JOIN cursos c ON f.curso_id = c.ID
    WHERE f.aluno_id = '$aluno_login'
";

$result = mysqli_query($conn, $query);
$ficha = mysqli_fetch_assoc($result);

if (!$ficha) {
    header('Location: ver_alunos.php?erro=1');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Ficha de Aluno - IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
    <style>
        .ficha-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .foto-aluno {
            width: 200px;
            height: 250px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .foto-placeholder {
            width: 200px;
            height: 250px;
            background: linear-gradient(135deg, #e0e0e0 0%, #f0f0f0 100%);
            border: 3px dashed #667eea;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 1.2em;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
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
        
        .estado-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1em;
        }
        
        .estado-rascunho { background: #ffc107; color: #000; }
        .estado-submetida { background: #17a2b8; color: #fff; }
        .estado-aprovada { background: #28a745; color: #fff; }
        .estado-rejeitada { background: #dc3545; color: #fff; }
        
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h1>📝 Ficha de Aluno</h1>
                    <p style="margin-left: 20px;"><strong><?php echo $ficha['nome_completo']; ?></strong></p>
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
            <a href="ver_pedidos.php">📋 Pedidos</a>
            <a href="ver_alunos.php">👥 Alunos</a>
        </div>
        
        <div class="content">
            <a href="ver_alunos.php" class="back-btn">← Voltar para lista de alunos</a>
            
            <div class="card ficha-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap;">
                    <h2>Dados da Ficha</h2>
                    <span class="estado-badge estado-<?php echo $ficha['estado']; ?>">
                        <?php echo strtoupper($ficha['estado']); ?>
                    </span>
                </div>
                
                <div style="display: flex; gap: 40px; flex-wrap: wrap;">
                    <!-- Coluna da foto -->
                    <div style="text-align: center;">
                        <?php if ($ficha['foto_path']): ?>
                            <img src="../<?php echo $ficha['foto_path']; ?>" class="foto-aluno">
                        <?php else: ?>
                            <div class="foto-placeholder">
                                <div>
                                    <div style="font-size: 3em; margin-bottom: 10px;">📷</div>
                                    <div>Sem foto</div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <p style="margin-top: 15px; color: #666;">
                            <strong>Login:</strong> <?php echo $ficha['aluno_id']; ?>
                        </p>
                    </div>
                    
                    <!-- Coluna dos dados -->
                    <div style="flex: 1;">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Nome Completo</div>
                                <div class="info-value"><?php echo $ficha['nome_completo']; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo $ficha['email'] ?? 'Não definido'; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Data Nascimento</div>
                                <div class="info-value"><?php echo $ficha['data_nascimento'] ? date('d/m/Y', strtotime($ficha['data_nascimento'])) : 'Não definido'; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">NIF</div>
                                <div class="info-value"><?php echo $ficha['nif'] ?? 'Não definido'; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Telefone</div>
                                <div class="info-value"><?php echo $ficha['telefone'] ?? 'Não definido'; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Curso Pretendido</div>
                                <div class="info-value"><?php echo $ficha['curso_nome']; ?></div>
                            </div>
                            
                            <div class="info-item" style="grid-column: span 2;">
                                <div class="info-label">Morada</div>
                                <div class="info-value"><?php echo $ficha['morada'] ?? 'Não definida'; ?></div>
                            </div>
                            
                            <?php if ($ficha['data_submissao']): ?>
                            <div class="info-item">
                                <div class="info-label">Data Submissão</div>
                                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($ficha['data_submissao'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($ficha['data_decisao']): ?>
                            <div class="info-item">
                                <div class="info-label">Data Decisão</div>
                                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($ficha['data_decisao'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($ficha['observacoes']): ?>
                            <div class="info-item" style="grid-column: span 2;">
                                <div class="info-label">Observações</div>
                                <div class="info-value"><?php echo $ficha['observacoes']; ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-top: 30px; text-align: center;">
                            <a href="ver_alunos.php" class="btn">👥 Voltar para Lista</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Área do Funcionário</p>
        </div>
    </div>
</body>
</html>