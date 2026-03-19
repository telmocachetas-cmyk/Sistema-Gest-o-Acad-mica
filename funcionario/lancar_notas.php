<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['grupo'] != 'FUNCIONARIO') {
    header('Location: ../login.php');
    exit;
}

$pauta_id = $_GET['pauta_id'] ?? 0;
$mensagem = '';

// Buscar informações da pauta
$pauta = mysqli_query($conn, "
    SELECT p.*, d.Nome_disc as uc_nome 
    FROM pautas p
    JOIN disciplinas d ON p.uc_id = d.ID
    WHERE p.id = $pauta_id
");
$pauta = mysqli_fetch_assoc($pauta);

if (!$pauta) {
    header('Location: ver_pautas.php');
    exit;
}

// Processar lançamento de notas
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_notas'])) {
    $funcionario = $_SESSION['login'];
    
    foreach ($_POST['notas'] as $nota_id => $valor_nota) {
        if (!empty($valor_nota)) {
            $valor_nota = floatval($valor_nota);
            $aprovado = ($valor_nota >= 9.5) ? 1 : 0;
            
            mysqli_query($conn, "
                UPDATE notas 
                SET nota = $valor_nota, aprovado = $aprovado, data_registo = NOW(), funcionario_id = '$funcionario'
                WHERE id = $nota_id
            ");
        }
    }
    
    $mensagem = '<div class="alert alert-success">Notas guardadas com sucesso!</div>';
}

// Buscar alunos da pauta
$alunos = mysqli_query($conn, "
    SELECT n.*, u.login as aluno_login
    FROM notas n
    JOIN users u ON n.aluno_id = u.login
    WHERE n.pauta_id = $pauta_id
    ORDER BY u.login
");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lançar Notas</title>
    <link rel="stylesheet" href="../estilo.css">
    <style>
        .nota-input {
            width: 80px;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            text-align: center;
        }
        
        .aprovado {
            background-color: #d4edda;
        }
        
        .reprovado {
            background-color: #f8d7da;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✏️ Lançar Notas</h1>
            <p><?php echo $pauta['uc_nome']; ?> - <?php echo $pauta['epoca']; ?> <?php echo $pauta['ano_letivo']; ?></p>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="criar_pauta.php">📝 Criar Pauta</a>
            <a href="ver_pautas.php">📊 Ver Pautas</a>
            <a href="ver_pedidos.php">📋 Pedidos</a>
            <a href="ver_alunos.php">👥 Alunos</a>
        </div>
        
        <div class="content">
            <?php echo $mensagem; ?>
            
            <div class="card">
                <form method="POST">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Aluno</th>
                                <th>Nota</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $index = 1;
                            while ($aluno = mysqli_fetch_assoc($alunos)): 
                                $classe = '';
                                if ($aluno['nota'] !== null) {
                                    $classe = $aluno['nota'] >= 9.5 ? 'aprovado' : 'reprovado';
                                }
                            ?>
                            <tr>
                                <td><?php echo $index++; ?></td>
                                <td><?php echo $aluno['aluno_login']; ?></td>
                                <td>
                                    <input type="number" 
                                           name="notas[<?php echo $aluno['id']; ?>]" 
                                           value="<?php echo $aluno['nota']; ?>"
                                           class="nota-input <?php echo $classe; ?>"
                                           step="0.1" min="0" max="20">
                                </td>
                                <td>
                                    <?php if ($aluno['nota'] !== null): ?>
                                        <?php if ($aluno['nota'] >= 9.5): ?>
                                            <span class="badge badge-aluno">Aprovado</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #dc3545;">Reprovado</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-admin">Pendente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 20px; text-align: center;">
                        <button type="submit" name="guardar_notas" class="btn">Guardar Notas</button>
                        <a href="ver_pautas.php" class="btn" style="background: #6c757d;">Voltar</a>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <h2>📊 Resumo</h2>
                <?php
                // Recalcular para o resumo
                mysqli_data_seek($alunos, 0);
                $total = 0;
                $lancadas = 0;
                $aprovados = 0;
                
                while ($aluno = mysqli_fetch_assoc($alunos)) {
                    $total++;
                    if ($aluno['nota'] !== null) {
                        $lancadas++;
                        if ($aluno['nota'] >= 9.5) {
                            $aprovados++;
                        }
                    }
                }
                ?>
                <div style="display: flex; gap: 20px; justify-content: center;">
                    <div><strong>Total alunos:</strong> <?php echo $total; ?></div>
                    <div><strong>Notas lançadas:</strong> <?php echo $lancadas; ?></div>
                    <div><strong>Aprovados:</strong> <?php echo $aprovados; ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Aplicar cor automaticamente quando a nota muda
        document.querySelectorAll('.nota-input').forEach(input => {
            input.addEventListener('change', function() {
                let nota = parseFloat(this.value);
                if (nota >= 9.5) {
                    this.classList.add('aprovado');
                    this.classList.remove('reprovado');
                } else if (nota !== '') {
                    this.classList.add('reprovado');
                    this.classList.remove('aprovado');
                }
            });
        });
    </script>
</body>
</html>