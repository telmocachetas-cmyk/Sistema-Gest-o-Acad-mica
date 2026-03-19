<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['login'])) {
    header('Location: login.php');
    exit;
}

$sql = "SELECT * FROM cursos ORDER BY ID";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursos - IPCA</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Cursos</h1>
            <p><?php echo $_SESSION['login']; ?> (<?php echo $_SESSION['grupo']; ?>)</p>
        </div>
        
        <div class="nav">
            <a href="index.php">🏠 Início</a>
            <a href="listar_disciplinas.php">📖 Unidades Curriculares</a>
            <?php if ($_SESSION['grupo'] == 'ADMIN'): ?>
            <?php endif; ?>
        </div>
        
        <div class="content">
            <div class="card">
                <h2>Lista de Cursos</h2>
                
                <?php if ($_SESSION['grupo'] == 'ADMIN'): ?>
                <div style="margin-bottom: 20px;">
                    <a href="admin/gerir_cursos.php" class="btn">➕ Novo Curso</a>
                </div>
                <?php endif; ?>
                
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome do Curso</th>
                                <th>Unidades Curriculares</th>
                                <?php if ($_SESSION['grupo'] == 'ADMIN'): ?>
                                <th>Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($curso = mysqli_fetch_assoc($result)): 
                                // Contar disciplinas ÚNICAS associadas ao curso
                                $count_disc = mysqli_query($conn, "SELECT COUNT(DISTINCT DISCIPLINA) as total FROM plano_estudos WHERE CURSOS=" . $curso['ID']);
                                $total_disc = mysqli_fetch_assoc($count_disc)['total'];
                            ?>
                            <tr>
                                <td>#<?php echo $curso['ID']; ?></td>
                                <td><strong><?php echo htmlspecialchars($curso['Nome']); ?></strong></td>
                                <td><span class="badge badge-admin"><?php echo $total_disc; ?> unidades</span></td>
                                <?php if ($_SESSION['grupo'] == 'ADMIN'): ?>
                                <td>
                                    <a href="admin/gerir_cursos.php?editar=<?php echo $curso['ID']; ?>" 
                                       class="btn" style="background: #28a745; padding: 5px 10px;">✏️ Editar</a>
                                    <a href="admin/gerir_cursos.php?eliminar=<?php echo $curso['ID']; ?>" 
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
                        Nenhum curso encontrado.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Lista de Cursos</p>
        </div>
    </div>
</body>
</html>