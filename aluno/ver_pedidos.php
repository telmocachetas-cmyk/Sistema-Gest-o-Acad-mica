<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['grupo'] != 'ALUNO') {
    header('Location: ../login.php');
    exit;
}

$login = $_SESSION['login'];

// Buscar pedidos de matrícula
$pedidos = mysqli_query($conn, "
    SELECT pm.*, c.Nome as curso_nome 
    FROM pedidos_matricula pm
    JOIN cursos c ON pm.curso_id = c.ID
    WHERE pm.aluno_id = '$login'
    ORDER BY pm.data_pedido DESC
");

// Buscar fichas
$fichas = mysqli_query($conn, "
    SELECT * FROM fichas_aluno 
    WHERE aluno_id = '$login'
    ORDER BY data_submissao DESC
");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos</title>
    <link rel="stylesheet" href="../estilo.css">
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 30px;
        }
        
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -20px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
        }
        
        .timeline-item:after {
            content: '';
            position: absolute;
            left: -15px;
            top: 20px;
            width: 2px;
            height: calc(100% - 15px);
            background: #e0e0e0;
        }
        
        .timeline-item:last-child:after {
            display: none;
        }
        
        .estado-concluido:before {
            background: #28a745;
        }
        
        .estado-pendente:before {
            background: #ffc107;
        }
        
        .estado-rejeitado:before {
            background: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header com título à esquerda e menu de perfil -->
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <div>
                    <h1>📋 Meus Pedidos</h1>
                    <p><strong>Bem-vindo, <?php echo $_SESSION['login']; ?></strong></p>
                </div>
                <div class="menu-perfil">
                    <span class="profile-badge profile-aluno">ALUNO</span>
                    <div class="menu-perfil-content">
                        <a href="../index.php">🏠 Site Principal</a>
                        <a href="perfil_aluno.php">👤 Meu Perfil</a>
                        <a href="../logout.php">🚪 Logout</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="minha_matricula.php">🎓 Matrícula</a>
            <a href="plano_estudos.php">📚 Plano de Estudos</a>
            <a href="ficha.php">📝 Ficha Pessoal</a>
        </div>
        
        <div class="content">
            <div class="card">
                <h2>📋 Estado da Ficha de Aluno</h2>
                
                <?php if (mysqli_num_rows($fichas) > 0): ?>
                    <?php while ($ficha = mysqli_fetch_assoc($fichas)): ?>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <p><strong>Data submissão:</strong> <?php echo $ficha['data_submissao'] ? date('d/m/Y H:i', strtotime($ficha['data_submissao'])) : 'Não submetida'; ?></p>
                                    <p><strong>Observações:</strong> <?php echo $ficha['observacoes'] ?? '-'; ?></p>
                                </div>
                                <div>
                                    <?php if ($ficha['estado'] == 'rascunho'): ?>
                                        <span class="badge badge-admin">Rascunho</span>
                                    <?php elseif ($ficha['estado'] == 'submetida'): ?>
                                        <span class="badge" style="background: #17a2b8;">Submetida</span>
                                    <?php elseif ($ficha['estado'] == 'aprovada'): ?>
                                        <span class="badge badge-aluno">Aprovada</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #dc3545;">Rejeitada</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($ficha['data_decisao']): ?>
                                <p style="margin-top: 10px; color: #666;">
                                    <small>Decisão em: <?php echo date('d/m/Y H:i', strtotime($ficha['data_decisao'])); ?></small>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; padding: 30px; color: #666;">
                        Ainda não preencheu a ficha de aluno.
                        <a href="ficha.php">Preencher agora</a>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>📋 Pedidos de Matrícula</h2>
                
                <?php if (mysqli_num_rows($pedidos) > 0): ?>
                    <div class="timeline">
                        <?php while ($pedido = mysqli_fetch_assoc($pedidos)): 
                            $estado_class = '';
                            if ($pedido['estado'] == 'aprovado') $estado_class = 'estado-concluido';
                            elseif ($pedido['estado'] == 'pendente') $estado_class = 'estado-pendente';
                            elseif ($pedido['estado'] == 'rejeitado') $estado_class = 'estado-rejeitado';
                        ?>
                            <div class="timeline-item <?php echo $estado_class; ?>">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h3><?php echo $pedido['curso_nome']; ?></h3>
                                        <p><strong>Data pedido:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></p>
                                        <?php if ($pedido['observacoes']): ?>
                                            <p><strong>Observações:</strong> <?php echo $pedido['observacoes']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($pedido['estado'] == 'pendente'): ?>
                                            <span class="badge badge-admin">Pendente</span>
                                        <?php elseif ($pedido['estado'] == 'aprovado'): ?>
                                            <span class="badge badge-aluno">Aprovado</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #dc3545;">Rejeitado</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($pedido['data_decisao']): ?>
                                    <p style="margin-top: 10px; color: #666;">
                                        <small>Decisão em: <?php echo date('d/m/Y H:i', strtotime($pedido['data_decisao'])); ?></small>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; padding: 30px; color: #666;">
                        Nenhum pedido de matrícula encontrado.
                        <a href="pedir_matricula.php">Pedir matrícula</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Meus Pedidos</p>
        </div>
    </div>
</body>
</html>