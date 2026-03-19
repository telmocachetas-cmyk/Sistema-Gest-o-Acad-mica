<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['login'])) {
    header('Location: login.php');
    exit;
}

// Buscar todas as disciplinas
$sql = "SELECT * FROM disciplinas ORDER BY ID";
$result = mysqli_query($conn, $sql);

// Contar total de cursos válidos
$total_cursos_validos = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM cursos"))['total'];
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unidades Curriculares - IPCA</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📖 Unidades Curriculares</h1>
            <p><?php echo $_SESSION['login']; ?> (<?php echo $_SESSION['grupo']; ?>)</p>
        </div>
        
        <div class="nav">
            <a href="index.php">🏠 Início</a>
            <a href="listar_cursos.php">📚 Cursos</a>
            <?php if ($_SESSION['grupo'] == 'ADMIN'): ?>
            <?php endif; ?>
        </div>
        
        <div class="content">
            <div class="card">
                <h2>Lista de Unidades Curriculares</h2>
                
                <?php if ($_SESSION['grupo'] == 'ADMIN'): ?>
                <div style="margin-bottom: 20px;">
                    <a href="admin/gerir_disciplinas.php" class="btn">➕ Nova Unidade Curricular</a>
                </div>
                <?php endif; ?>
                
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome da Unidade Curricular</th>
                                <th>Cursos que a usam</th>
                                <?php if ($_SESSION['grupo'] == 'ADMIN'): ?>
                                <th>Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($disciplina = mysqli_fetch_assoc($result)): 
                                // Contar em quantos cursos VÁLIDOS esta disciplina é usada
                                $count_sql = "SELECT COUNT(DISTINCT p.CURSOS) as total 
                                              FROM plano_estudos p
                                              WHERE p.DISCIPLINA = " . $disciplina['ID'] . "
                                              AND p.CURSOS IN (SELECT ID FROM cursos)";
                                $count_result = mysqli_query($conn, $count_sql);
                                $total_cursos_uso = mysqli_fetch_assoc($count_result)['total'];
                            ?>
                            <tr>
                                <td>#<?php echo str_pad($disciplina['ID'], 2, '0', STR_PAD_LEFT); ?></td>
                                <td><strong><?php echo htmlspecialchars($disciplina['Nome_disc']); ?></strong></td>
                                <td>
                                    <span class="badge badge-admin"><?php echo $total_cursos_uso; ?> cursos</span>
                                </td>
                                <?php if ($_SESSION['grupo'] == 'ADMIN'): ?>
                                <td>
                                    <a href="admin/gerir_disciplinas.php?editar=<?php echo $disciplina['ID']; ?>" 
                                       class="btn" style="background: #28a745; padding: 5px 10px;">✏️ Editar</a>
                                    <a href="admin/gerir_disciplinas.php?eliminar=<?php echo $disciplina['ID']; ?>" 
                                       class="btn" style="background: #dc3545; padding: 5px 10px;"
                                       onclick="return confirm('Tem a certeza?')">🗑️ Eliminar</a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        Nenhuma unidade curricular encontrada.
                        <?php if ($_SESSION['grupo'] == 'ADMIN'): ?>
                            <br><br>
                            <a href="admin/gerir_disciplinas.php" class="btn">Criar primeira unidade curricular</a>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Estatísticas rápidas -->
            <div class="card">
                <h2>📊 Estatísticas</h2>
                <div style="display: flex; gap: 20px; justify-content: space-around;">
                    <div style="text-align: center;">
                        <div style="font-size: 2em; color: #667eea;"><?php echo mysqli_num_rows($result); ?></div>
                        <div>Total de Unidades Curriculares</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 2em; color: #667eea;"><?php echo $total_cursos_validos; ?></div>
                        <div>Total de Cursos</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Lista de Unidades Curriculares</p>
        </div>
    </div>
</body>
</html>