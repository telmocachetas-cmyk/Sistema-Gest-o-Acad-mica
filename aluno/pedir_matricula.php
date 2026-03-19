<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['grupo'] != 'ALUNO') {
    header('Location: ../login.php');
    exit;
}

$login = $_SESSION['login'];
$mensagem = '';

// Verificar se tem ficha aprovada
$ficha = mysqli_query($conn, "SELECT * FROM fichas_aluno WHERE aluno_id = '$login' AND estado = 'aprovada'");
$ficha = mysqli_fetch_assoc($ficha);

if (!$ficha) {
    $erro = "Precisa de ter uma ficha aprovada para pedir matrícula.";
}

// Buscar pedidos existentes
$pedidos = mysqli_query($conn, "
    SELECT pm.*, c.Nome as curso_nome 
    FROM pedidos_matricula pm
    JOIN cursos c ON pm.curso_id = c.ID
    WHERE pm.aluno_id = '$login'
    ORDER BY pm.data_pedido DESC
");

// Processar novo pedido
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['curso_id']) && !isset($erro)) {
    $curso_id = mysqli_real_escape_string($conn, $_POST['curso_id']);
    $ano_letivo = date('Y') . '/' . (date('Y') + 1);
    
    // Verificar se já existe pedido pendente
    $check = mysqli_query($conn, "
        SELECT id FROM pedidos_matricula 
        WHERE aluno_id = '$login' AND curso_id = $curso_id AND estado = 'pendente'
    ");
    
    if (mysqli_num_rows($check) > 0) {
        $mensagem = '<div class="alert alert-error">Já existe um pedido pendente para este curso!</div>';
    } else {
        $sql = "INSERT INTO pedidos_matricula (aluno_id, curso_id, ano_letivo, estado, data_pedido)
                VALUES ('$login', $curso_id, '$ano_letivo', 'pendente', NOW())";
        
        if (mysqli_query($conn, $sql)) {
            $mensagem = '<div class="alert alert-success">Pedido de matrícula submetido com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-error">Erro: ' . mysqli_error($conn) . '</div>';
        }
    }
}

// Buscar cursos para o select (excluindo os que já têm pedido pendente)
$cursos = mysqli_query($conn, "
    SELECT c.ID, c.Nome 
    FROM cursos c
    WHERE c.ID NOT IN (
        SELECT curso_id FROM pedidos_matricula 
        WHERE aluno_id = '$login' AND estado = 'pendente'
    )
");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedir Matrícula</title>
    <link rel="stylesheet" href="../estilo.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Pedido de Matrícula</h1>
            <p><?php echo $_SESSION['login']; ?></p>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="minha_matricula.php">🎓 Matrícula</a>
            <a href="plano_estudos.php">📚 Plano de Estudos</a>
            <a href="ficha.php">📝 Ficha Pessoal</a>
            <a href="ver_pedidos.php">📋 Pedidos</a>
        </div>
        
        <div class="content">
            <?php echo $mensagem; ?>
            
            <?php if (isset($erro)): ?>
                <div class="alert alert-error"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <?php if (!isset($erro)): ?>
                <div class="card">
                    <h2>Novo Pedido de Matrícula</h2>
                    
                    <?php if (mysqli_num_rows($cursos) > 0): ?>
                        <form method="POST">
                            <div class="form-group">
                                <label>Curso:</label>
                                <select name="curso_id" required>
                                    <option value="">Selecione um curso</option>
                                    <?php while ($curso = mysqli_fetch_assoc($cursos)): ?>
                                        <option value="<?php echo $curso['ID']; ?>">
                                            <?php echo $curso['Nome']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn">Submeter Pedido</button>
                        </form>
                    <?php else: ?>
                        <p style="text-align: center; padding: 30px; color: #666;">
                            Já submeteu pedidos para todos os cursos disponíveis.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Meus Pedidos</h2>
                
                <?php if (mysqli_num_rows($pedidos) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Curso</th>
                                <th>Estado</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($pedido = mysqli_fetch_assoc($pedidos)): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?></td>
                                    <td><?php echo $pedido['curso_nome']; ?></td>
                                    <td>
                                        <?php if ($pedido['estado'] == 'pendente'): ?>
                                            <span class="badge badge-admin">Pendente</span>
                                        <?php elseif ($pedido['estado'] == 'aprovado'): ?>
                                            <span class="badge badge-aluno">Aprovado</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #dc3545;">Rejeitado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $pedido['observacoes'] ?? '-'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; padding: 30px; color: #666;">
                        Nenhum pedido de matrícula encontrado.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>