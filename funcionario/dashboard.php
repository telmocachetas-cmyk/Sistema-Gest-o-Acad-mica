<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['grupo'] != 'FUNCIONARIO') {
    header('Location: ../login.php');
    exit;
}

// Estatísticas
$pendentes = mysqli_query($conn, "SELECT COUNT(*) as total FROM pedidos_matricula WHERE estado='pendente'");
$total_pendentes = mysqli_fetch_assoc($pendentes)['total'];

$pautas = mysqli_query($conn, "SELECT COUNT(*) as total FROM pautas");
$total_pautas = mysqli_fetch_assoc($pautas)['total'];

$alunos = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE grupo=2");
$total_alunos = mysqli_fetch_assoc($alunos)['total'];

// Últimos pedidos para mostrar na sidebar (opcional)
$ultimos_pedidos = mysqli_query($conn, "
    SELECT pm.*, u.login as aluno_login, c.Nome as curso_nome 
    FROM pedidos_matricula pm
    JOIN users u ON pm.aluno_id = u.login
    JOIN cursos c ON pm.curso_id = c.ID
    WHERE pm.estado = 'pendente'
    ORDER BY pm.data_pedido DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Funcionário</title>
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
        
        /* Grid de estatísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
            border: 1px solid #f0f0f0;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
        }
        
        .info-card-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: #667eea;
        }
        
        .info-card-title {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 10px;
        }
        
        .info-card-value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
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
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .action-btn {
            display: block;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            text-align: center;
            font-size: 1.1em;
            transition: transform 0.3s;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h1>👔 Painel do Funcionário</h1>
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
            <a href="criar_pauta.php">📝 Criar Pauta</a>
            <a href="ver_pautas.php">📊 Ver Pautas</a>
            <a href="ver_pedidos.php">📋 Pedidos</a>
            <a href="ver_alunos.php">👥 Alunos</a>
        </div>
        
        <div class="content">
            <!-- Cards de estatísticas -->
            <div class="stats-grid">
                <div class="info-card">
                    <div class="info-card-icon">📋</div>
                    <div class="info-card-title">Pedidos Pendentes</div>
                    <div class="info-card-value"><?php echo $total_pendentes; ?></div>
                    <a href="ver_pedidos.php" class="btn-small">Ver pedidos</a>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon">📊</div>
                    <div class="info-card-title">Pautas Criadas</div>
                    <div class="info-card-value"><?php echo $total_pautas; ?></div>
                    <a href="ver_pautas.php" class="btn-small">Ver pautas</a>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon">👥</div>
                    <div class="info-card-title">Alunos Registados</div>
                    <div class="info-card-value"><?php echo $total_alunos; ?></div>
                    <a href="ver_alunos.php" class="btn-small">Ver alunos</a>
                </div>
            </div> <!-- Fim do stats-grid -->
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <!-- Ações rápidas -->
                <div class="card">
                    <h2>⚡ Ações Rápidas</h2>
                    <div class="quick-actions">
                        <a href="ver_pedidos.php" class="action-btn">
                            📋 Gerir Pedidos Pendentes
                        </a>
                        <a href="criar_pauta.php" class="action-btn">
                            📝 Criar Nova Pauta
                        </a>
                        <a href="ver_pautas.php" class="action-btn">
                            📊 Ver Pautas Existentes
                        </a>
                    </div>
                </div>
                
                <!-- Últimos pedidos pendentes -->
                <div class="card">
                    <h2>⏳ Últimos Pedidos</h2>
                    <?php if ($ultimos_pedidos && mysqli_num_rows($ultimos_pedidos) > 0): ?>
                        <table style="width: 100%;">
                            <?php while ($pedido = mysqli_fetch_assoc($ultimos_pedidos)): ?>
                            <tr>
                                <td style="padding: 8px 0;"><?php echo $pedido['aluno_login']; ?></td>
                                <td style="padding: 8px 0; text-align: right;">
                                    <span class="badge badge-admin">Pendente</span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="ver_pedidos.php" class="btn-small">Ver todos</a>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; padding: 20px;">
                            Nenhum pedido pendente.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Últimas pautas criadas -->
            <?php
            $ultimas_pautas = mysqli_query($conn, "
                SELECT p.*, d.Nome_disc as uc_nome 
                FROM pautas p
                JOIN disciplinas d ON p.uc_id = d.ID
                ORDER BY p.data_criacao DESC
                LIMIT 5
            ");
            
            if ($ultimas_pautas && mysqli_num_rows($ultimas_pautas) > 0):
            ?>
            <div class="card" style="margin-top: 20px;">
                <h2>📊 Últimas Pautas Criadas</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>UC</th>
                            <th>Época</th>
                            <th>Ano Letivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($pauta = mysqli_fetch_assoc($ultimas_pautas)): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($pauta['data_criacao'])); ?></td>
                            <td><?php echo $pauta['uc_nome']; ?></td>
                            <td><?php echo $pauta['epoca']; ?></td>
                            <td><?php echo $pauta['ano_letivo']; ?></td>
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