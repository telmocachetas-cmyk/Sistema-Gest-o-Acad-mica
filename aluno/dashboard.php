<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || !isAluno()) {
    header('Location: ../login.php');
    exit;
}

$login = $_SESSION['login'];

// Buscar informações do aluno e da ficha
$aluno = getAlunoInfo($conn, $login);

// Buscar a ficha do aluno para obter informações
$ficha_query = mysqli_query($conn, "SELECT * FROM fichas_aluno WHERE aluno_id = '$login'");
$ficha = mysqli_fetch_assoc($ficha_query);

// Buscar o curso da matrícula APROVADA do aluno
$curso = null;
$matricula_query = mysqli_query($conn, "
    SELECT c.*, pm.id as matricula_id FROM cursos c
    JOIN pedidos_matricula pm ON c.ID = pm.curso_id
    WHERE pm.aluno_id = '$login' AND pm.estado = 'aprovado'
    LIMIT 1
");

if ($matricula_query && mysqli_num_rows($matricula_query) > 0) {
    $curso = mysqli_fetch_assoc($matricula_query);
}

// Buscar disciplinas do curso (se existir curso) COM INFORMAÇÃO DO SEMESTRE
$disciplinas = [];
$total_disciplinas = 0;

if ($curso) {
    $disciplinas = mysqli_query($conn, 
        "SELECT d.*, p.semestre FROM disciplinas d 
         INNER JOIN plano_estudos p ON d.ID = p.DISCIPLINA 
         WHERE p.CURSOS = " . $curso['ID'] . "
         ORDER BY p.semestre"
    );
    $total_disciplinas = mysqli_num_rows($disciplinas);
}

// Verificar estado da matrícula
$matricula_aprovada = false;
$pedido_pendente = false;
$estado_matricula = 'sem_matricula'; // Valores: 'sem_matricula', 'pendente', 'aprovada'

$pedidos_query = mysqli_query($conn, "SELECT * FROM pedidos_matricula WHERE aluno_id = '$login' ORDER BY data_pedido DESC LIMIT 1");
if ($pedidos_query && mysqli_num_rows($pedidos_query) > 0) {
    $pedido = mysqli_fetch_assoc($pedidos_query);
    
    if ($pedido['estado'] == 'aprovado') {
        $matricula_aprovada = true;
        $estado_matricula = 'aprovada';
    } elseif ($pedido['estado'] == 'pendente') {
        $pedido_pendente = true;
        $estado_matricula = 'pendente';
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Aluno - IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
    <style>
        /* Ajustes para o dashboard */
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
        
        /* Status da matrícula */
        .status-matricula {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .status-ativa {
            background: #28a745;
            color: white;
        }
        
        .status-pendente {
            background: #ffc107;
            color: #333;
        }
        
        .status-sem-matricula {
            background: #6c757d;
            color: white;
        }
        
        /* Disciplinas grid */
        .disciplinas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .disciplina-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }
        
        .disciplina-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .disciplina-card h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .disciplina-card p {
            margin: 5px 0;
            color: #666;
            font-size: 0.9em;
        }
        
        .estado-disciplina {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .estado-em-curso {
            background: #28a745;
            color: white;
        }
        
        .estado-aguarda {
            background: #ffc107;
            color: #333;
        }
        
        .estado-bloqueado {
            background: #6c757d;
            color: white;
        }
        
        .semestre-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            background: #e9ecef;
            color: #666;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>🎓 Área do Aluno</h1>
                    <p>Bem-vindo, <strong><?php echo $_SESSION['login']; ?></strong></p>
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
            <a href="minha_matricula.php">🎓 Matrícula</a>
            <a href="plano_estudos.php">📚 Plano de Estudos</a>
            <a href="ficha.php">📝 Ficha Pessoal</a>
            <a href="ver_pedidos.php">📋 Pedidos</a>
        </div>
        
        <div class="content">
            <!-- Informações do aluno -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="info-card">
                    <div class="info-card-icon">📚</div>
                    <div class="info-card-title">Curso</div>
                    <div class="info-card-value"><?php echo $curso ? $curso['Nome'] : 'Não atribuído'; ?></div>
                    <div style="margin-top: 15px;">
                        <?php if ($estado_matricula == 'aprovada'): ?>
                            <span class="status-matricula status-ativa">
                                ✓ Matrícula Ativa
                            </span>
                        <?php elseif ($estado_matricula == 'pendente'): ?>
                            <span class="status-matricula status-pendente">
                                ⏳ Pedido Pendente
                            </span>
                        <?php else: ?>
                            <span class="status-matricula status-sem-matricula">
                                ⚠️ Sem Matrícula
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon">📖</div>
                    <div class="info-card-title">Disciplinas Inscritas</div>
                    <div class="info-card-value"><?php echo $total_disciplinas; ?></div>
                    <div style="margin-top: 15px;">
                        <?php if ($curso): ?>
                            <a href="plano_estudos.php" style="color: #667eea;">Ver todas →</a>
                        <?php else: ?>
                            <span style="color: #999;">Indisponível</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon">📅</div>
                    <div class="info-card-title">Ano Letivo</div>
                    <div class="info-card-value">2025/2026</div>
                    <div style="margin-top: 15px;">
                        <span>1º Semestre</span>
                    </div>
                </div>
            </div>
            
            <!-- Estado da Ficha - SEM BOTÃO -->
            <div class="card">
                <h2>📝 Estado da Ficha de Aluno</h2>
                <div style="display: flex; align-items: flex-start; gap: 20px; flex-wrap: wrap;">
                    <?php if ($ficha): ?>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <?php if ($ficha['foto_path']): ?>
                                <img src="../<?php echo $ficha['foto_path']; ?>" style="width: 80px; height: 100px; border-radius: 10px; object-fit: cover; border: 2px solid #667eea;">
                            <?php else: ?>
                                <div style="width: 80px; height: 100px; background: #f0f0f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 2px dashed #667eea;">
                                    <span style="color: #999;">Sem foto</span>
                                </div>
                            <?php endif; ?>
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <p style="margin: 0;"><strong>Estado:</strong> 
                                    <?php if ($ficha['estado'] == 'rascunho'): ?>
                                        <span class="badge badge-admin">Rascunho</span>
                                    <?php elseif ($ficha['estado'] == 'submetida'): ?>
                                        <span class="badge" style="background: #17a2b8;">Submetida</span>
                                    <?php elseif ($ficha['estado'] == 'aprovada'): ?>
                                        <span class="badge badge-aluno">Aprovada</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #dc3545;">Rejeitada</span>
                                    <?php endif; ?>
                                </p>
                                
                                <?php if ($ficha['observacoes']): ?>
                                    <p style="margin: 0;"><strong>Observações:</strong> <?php echo $ficha['observacoes']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p style="color: #666;">Ainda não preencheu a ficha de aluno. <a href="ficha.php">Preencher agora</a></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Próximas disciplinas -->
            <div class="card">
                <h2>📖 Minhas Disciplinas</h2>
                
                <?php if ($curso && $total_disciplinas > 0): ?>
                    <div class="disciplinas-grid">
                        <?php 
                        mysqli_data_seek($disciplinas, 0);
                        while($disc = mysqli_fetch_assoc($disciplinas)): 
                            // Determinar o estado com base no semestre
                            $estado_class = '';
                            $estado_texto = '';
                            
                            if (!$matricula_aprovada) {
                                $estado_class = 'estado-bloqueado';
                                $estado_texto = 'Sem matrícula';
                            } elseif ($pedido_pendente) {
                                $estado_class = 'estado-aguarda';
                                $estado_texto = 'A aguardar';
                            } elseif ($disc['semestre'] == 1) {
                                $estado_class = 'estado-em-curso';
                                $estado_texto = 'Em curso';
                            } else {
                                $estado_class = 'estado-em-curso'; // Mesma cor verde
                                $estado_texto = 'Por iniciar';
                            }
                        ?>
                        <div class="disciplina-card">
                            <h4>
                                <?php echo $disc['Nome_disc']; ?>
                                <span class="semestre-badge"><?php echo $disc['semestre']; ?>º Sem</span>
                            </h4>
                            <p><strong>Código:</strong> DISC<?php echo str_pad($disc['ID'], 3, '0', STR_PAD_LEFT); ?></p>
                            <p><strong>Estado:</strong> 
                                <span class="estado-disciplina <?php echo $estado_class; ?>">
                                    <?php echo $estado_texto; ?>
                                </span>
                            </p>
                            <div style="margin-top: 15px;">
                                <span style="background: #e9ecef; padding: 5px 10px; border-radius: 15px; font-size: 0.8em;">
                                    48h
                                </span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        <?php if ($matricula_aprovada): ?>
                            Nenhuma disciplina encontrada para o seu curso.
                        <?php else: ?>
                            Nenhuma disciplina atribuída. Complete a sua ficha e peça matrícula.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Calendário resumido - SÓ APARECE SE O ALUNO TIVER MATRÍCULA APROVADA -->
            <?php if ($matricula_aprovada): ?>
            <div class="card">
                <h2>📅 Próximos Eventos</h2>
                <table class="table">
                    <tr>
                        <td>📝 Exame de Matemática</td>
                        <td>15/06/2026</td>
                        <td><span class="badge badge-admin">09:00</span></td>
                    </tr>
                    <tr>
                        <td>💻 Entrega Projeto WEB I</td>
                        <td>20/06/2026</td>
                        <td><span class="badge badge-admin">23:59</span></td>
                    </tr>
                    <tr>
                        <td>📊 Apresentação LP</td>
                        <td>25/06/2026</td>
                        <td><span class="badge badge-admin">14:00</span></td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Ações rápidas baseadas no estado -->
            <div class="card">
                <h2>⚡ Ações Rápidas</h2>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <?php if (!$ficha || $ficha['estado'] == 'rascunho' || $ficha['estado'] == 'rejeitada'): ?>
                        <a href="ficha.php" class="btn">📝 Preencher Ficha</a>
                    <?php endif; ?>
                    
                    <?php if ($ficha && $ficha['estado'] == 'aprovada' && $estado_matricula != 'aprovada' && $estado_matricula != 'pendente'): ?>
                        <a href="pedir_matricula.php" class="btn" style="background: #28a745;">🎓 Pedir Matrícula</a>
                    <?php endif; ?>
                    
                    <?php if ($estado_matricula == 'pendente'): ?>
                        <a href="ver_pedidos.php" class="btn">📋 Ver Pedidos</a>
                    <?php endif; ?>
                    
                    <?php if ($matricula_aprovada): ?>
                        <a href="plano_estudos.php" class="btn">📚 Aceder às Disciplinas</a>
                    <?php else: ?>
                        <a href="plano_estudos.php" class="btn">📚 Ver Plano de Estudos</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Área do Aluno</p>
        </div>
    </div>
</body>
</html>