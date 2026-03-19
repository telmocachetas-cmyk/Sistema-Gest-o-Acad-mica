<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || !isAluno()) {
    header('Location: ../login.php');
    exit;
}

$login = $_SESSION['login'];

// Buscar a ficha do aluno
$ficha_query = mysqli_query($conn, "SELECT * FROM fichas_aluno WHERE aluno_id = '$login'");
$ficha = mysqli_fetch_assoc($ficha_query);

// Buscar o curso da matrícula APROVADA do aluno
$curso = null;
$matricula_query = mysqli_query($conn, "
    SELECT c.* FROM cursos c
    JOIN pedidos_matricula pm ON c.ID = pm.curso_id
    WHERE pm.aluno_id = '$login' AND pm.estado = 'aprovado'
    LIMIT 1
");

if ($matricula_query && mysqli_num_rows($matricula_query) > 0) {
    $curso = mysqli_fetch_assoc($matricula_query);
}

// Buscar disciplinas do curso (se existir curso)
$disciplinas = [];
$total_disciplinas = 0;

if ($curso) {
    $disciplinas = mysqli_query($conn, 
        "SELECT d.* FROM disciplinas d 
         INNER JOIN plano_estudos p ON d.ID = p.DISCIPLINA 
         WHERE p.CURSOS = " . $curso['ID']
    );
    $total_disciplinas = mysqli_num_rows($disciplinas);
}

// Verificar estado da matrícula
$matricula_aprovada = false;
$pedido_pendente = false;

$pedidos_query = mysqli_query($conn, "SELECT * FROM pedidos_matricula WHERE aluno_id = '$login' ORDER BY data_pedido DESC LIMIT 1");
if ($pedidos_query && mysqli_num_rows($pedidos_query) > 0) {
    $pedido = mysqli_fetch_assoc($pedidos_query);
    
    if ($pedido['estado'] == 'aprovado') {
        $matricula_aprovada = true;
    } elseif ($pedido['estado'] == 'pendente') {
        $pedido_pendente = true;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plano de Estudos - IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
    <style>
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
        
        .aviso-matricula {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .aviso-matricula a {
            color: #856404;
            font-weight: bold;
            text-decoration: underline;
        }
        
        .aviso-matricula a:hover {
            color: #533f03;
        }
        
        .sem-curso {
            text-align: center;
            padding: 60px;
            color: #666;
            background: #f8f9fa;
            border-radius: 15px;
            margin: 20px 0;
        }
        
        .sem-curso h3 {
            color: #667eea;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <div>
                    <h1>📚 Plano de Estudos</h1>
                    <p><strong>Bem-vindo, <?php echo $_SESSION['login']; ?></strong> 
                    <?php if ($curso): ?>
                        - <?php echo $curso['Nome']; ?>
                    <?php endif; ?>
                    </p>
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
            <a href="ficha.php">📝 Ficha Pessoal</a>
            <a href="ver_pedidos.php">📋 Pedidos</a>
        </div>
        
        <div class="content">
            <?php if (!$curso): ?>
                <!-- Aluno sem matrícula aprovada -->
                <div class="sem-curso">
                    <h3>🎓 Sem curso atribuído</h3>
                    <p>Para ver o plano de estudos, precisa primeiro:</p>
                    <ol style="text-align: left; max-width: 400px; margin: 20px auto;">
                        <li>📝 Preencher a <a href="ficha.php">ficha de aluno</a></li>
                        <li>✅ Aguardar aprovação da ficha pelo gestor</li>
                        <li>🎓 <a href="pedir_matricula.php">Pedir matrícula</a> num curso</li>
                        <li>📋 Aguardar aprovação da matrícula pelo funcionário</li>
                    </ol>
                    <div style="margin-top: 30px;">
                        <a href="ficha.php" class="btn">📝 Preencher Ficha</a>
                        <a href="pedir_matricula.php" class="btn" style="background: #28a745;">🎓 Pedir Matrícula</a>
                    </div>
                </div>
            <?php else: ?>
                
                <!-- Aviso sobre estado da matrícula -->
                <?php if (!$matricula_aprovada): ?>
                    <div class="aviso-matricula">
                        <?php if ($pedido_pendente): ?>
                            ⏳ O seu pedido de matrícula está pendente de aprovação. Assim que for aprovado, poderá frequentar as disciplinas.
                        <?php else: ?>
                            ⚠️ Para frequentar as disciplinas, precisa ter uma matrícula ativa. 
                            <?php if ($ficha && $ficha['estado'] == 'aprovada'): ?>
                                <a href="pedir_matricula.php">Pedir matrícula agora</a>.
                            <?php else: ?>
                                Precisa primeiro <a href="ficha.php">preencher a ficha de aluno</a>.
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Resumo do plano -->
                <div class="card">
                    <h2>📊 Resumo do Ano Letivo 2025/2026</h2>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; text-align: center;">
                        <div>
                            <div style="font-size: 2.5em; color: #667eea;"><?php echo $total_disciplinas; ?></div>
                            <div style="color: #666;">Disciplinas</div>
                        </div>
                        <div>
                            <div style="font-size: 2.5em; color: #667eea;"><?php echo $total_disciplinas * 48; ?>h</div>
                            <div style="color: #666;">Carga Horária</div>
                        </div>
                        <div>
                            <div style="font-size: 2.5em; color: #667eea;"><?php echo $total_disciplinas * 6; ?></div>
                            <div style="color: #666;">Créditos ECTS</div>
                        </div>
                    </div>
                </div>
                
                <!-- Disciplinas por semestre -->
                <?php
                // Buscar disciplinas organizadas por semestre
                $disciplinas_por_semestre = mysqli_query($conn, "
                    SELECT d.*, p.semestre 
                    FROM disciplinas d 
                    INNER JOIN plano_estudos p ON d.ID = p.DISCIPLINA 
                    WHERE p.CURSOS = " . $curso['ID'] . "
                    ORDER BY p.semestre
                ");
                ?>

                <!-- 1º Semestre -->
                <div class="card">
                    <h2>📚 1º Semestre</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Disciplina</th>
                                <th>Horas</th>
                                <th>ECTS</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($disciplinas_por_semestre, 0);
                            $encontrou = false;
                            while($disc = mysqli_fetch_assoc($disciplinas_por_semestre)): 
                                if ($disc['semestre'] == 1):
                                    $encontrou = true;
                            ?>
                            <tr>
                                <td>DISC<?php echo str_pad($disc['ID'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo $disc['Nome_disc']; ?></td>
                                <td>48h</td>
                                <td>6</td>
                                <td>
                                    <?php if ($matricula_aprovada): ?>
                                        <span class="estado-disciplina estado-em-curso">Em curso</span>
                                    <?php elseif ($pedido_pendente): ?>
                                        <span class="estado-disciplina estado-aguarda">A aguardar</span>
                                    <?php else: ?>
                                        <span class="estado-disciplina estado-bloqueado">Bloqueado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                endif;
                            endwhile; 
                            if (!$encontrou):
                            ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #666;">Nenhuma disciplina neste semestre</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 2º Semestre -->
                <div class="card">
                    <h2>📚 2º Semestre</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Disciplina</th>
                                <th>Horas</th>
                                <th>ECTS</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($disciplinas_por_semestre, 0);
                            $encontrou = false;
                            while($disc = mysqli_fetch_assoc($disciplinas_por_semestre)): 
                                if ($disc['semestre'] == 2):
                                    $encontrou = true;
                            ?>
                            <tr>
                                <td>DISC<?php echo str_pad($disc['ID'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo $disc['Nome_disc']; ?></td>
                                <td>48h</td>
                                <td>6</td>
                                <td>
                                    <?php if ($matricula_aprovada): ?>
                                        <span class="estado-disciplina estado-em-curso">Por iniciar</span>
                                    <?php elseif ($pedido_pendente): ?>
                                        <span class="estado-disciplina estado-aguarda">A aguardar</span>
                                    <?php else: ?>
                                        <span class="estado-disciplina estado-bloqueado">Bloqueado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                endif;
                            endwhile; 
                            if (!$encontrou):
                            ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #666;">Nenhuma disciplina neste semestre</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Horário semanal -->
                <div class="card">
                    <h2>⏰ Horário Semanal</h2>
                    <p style="text-align: center; color: #666; padding: 30px;">
                        ⏳ Indisponível
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Plano de Estudos</p>
        </div>
    </div>
</body>
</html>