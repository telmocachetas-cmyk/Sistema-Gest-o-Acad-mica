<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['grupo'] != 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';

// Processar aprovação/rejeição
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ficha_id'])) {
    $ficha_id = $_POST['ficha_id'];
    $estado = $_POST['estado'];
    $observacoes = mysqli_real_escape_string($conn, $_POST['observacoes']);
    $gestor = $_SESSION['login'];
    
    $sql = "UPDATE fichas_aluno SET 
            estado = '$estado', 
            observacoes = '$observacoes', 
            data_decisao = NOW(), 
            gestor_id = '$gestor' 
            WHERE id = $ficha_id";
    
    if (mysqli_query($conn, $sql)) {
        $mensagem = '<div class="alert alert-success">Ficha ' . ($estado == 'aprovada' ? 'aprovada' : 'rejeitada') . ' com sucesso!</div>';
    } else {
        $mensagem = '<div class="alert alert-error">Erro: ' . mysqli_error($conn) . '</div>';
    }
}

// Buscar estatísticas
$pendentes = mysqli_query($conn, "SELECT COUNT(*) as total FROM fichas_aluno WHERE estado = 'submetida'");
$pendentes = mysqli_fetch_assoc($pendentes)['total'];

$aprovadas = mysqli_query($conn, "SELECT COUNT(*) as total FROM fichas_aluno WHERE estado = 'aprovada'");
$aprovadas = mysqli_fetch_assoc($aprovadas)['total'];

$rejeitadas = mysqli_query($conn, "SELECT COUNT(*) as total FROM fichas_aluno WHERE estado = 'rejeitada'");
$rejeitadas = mysqli_fetch_assoc($rejeitadas)['total'];

// Buscar fichas pendentes
$fichas_pendentes = mysqli_query($conn, "
    SELECT f.*, u.login as aluno_login, c.Nome as curso_nome 
    FROM fichas_aluno f
    JOIN users u ON f.aluno_id = u.login
    JOIN cursos c ON f.curso_id = c.ID
    WHERE f.estado = 'submetida'
    ORDER BY f.data_submissao ASC
");

// Buscar histórico
$historico = mysqli_query($conn, "
    SELECT f.*, u.login as aluno_login, c.Nome as curso_nome, g.login as gestor_login
    FROM fichas_aluno f
    JOIN users u ON f.aluno_id = u.login
    JOIN cursos c ON f.curso_id = c.ID
    LEFT JOIN users g ON f.gestor_id = g.login
    WHERE f.estado IN ('aprovada', 'rejeitada')
    ORDER BY f.data_decisao DESC
    LIMIT 20
");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar Fichas - Gestor</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
        }
        
        .ficha-pendente {
            border-left: 4px solid #ffc107;
            margin-bottom: 20px;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .foto-mini {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Header com título à esquerda e menu de perfil -->
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h1>✅Validação de Fichas de Aluno</h1>
                    <p style="margin-left: 20px;"><strong>Bem-vindo, <?php echo $_SESSION['login']; ?></strong></p>
                </div>
                <div class="menu-perfil">
                    <span class="profile-badge profile-admin">ADMIN</span>
                    <div class="menu-perfil-content">
                        <a href="../index.php">🏠 Site Principal</a>
                        <a href="perfil_admin.php">👤 Meu Perfil</a>
                        <a href="../logout.php">🚪 Logout</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="gerir_cursos.php">📚 Cursos</a>
            <a href="gerir_disciplinas.php">📖 Unidades Curriculares</a>
            <a href="gerir_planos.php">📋 Planos de Estudo</a>
            <a href="gerir_utilizadores.php">👥 Utilizadores</a>
        </div>
        
        <div class="content">
            <?php echo $mensagem; ?>
            
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pendentes; ?></div>
                    <p>Pendentes</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $aprovadas; ?></div>
                    <p>Aprovadas</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $rejeitadas; ?></div>
                    <p>Rejeitadas</p>
                </div>
            </div>
            
            <!-- Fichas Pendentes -->
            <div class="card">
                <h2>⏳ Fichas Pendentes (<?php echo $pendentes; ?>)</h2>
                
                <?php if (mysqli_num_rows($fichas_pendentes) > 0): ?>
                    <?php while ($ficha = mysqli_fetch_assoc($fichas_pendentes)): ?>
                        <div class="ficha-pendente">
                            <div style="display: flex; gap: 20px;">
                                <!-- Foto -->
                                <div>
                                    <?php if ($ficha['foto_path']): ?>
                                        <img src="../<?php echo $ficha['foto_path']; ?>" class="foto-mini">
                                    <?php else: ?>
                                        <div class="foto-mini" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                            📷
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Dados -->
                                <div style="flex: 1;">
                                    <h3><?php echo $ficha['nome_completo']; ?> (<?php echo $ficha['aluno_login']; ?>)</h3>
                                    <p><strong>Curso pretendido:</strong> <?php echo $ficha['curso_nome']; ?></p>
                                    <p><strong>Data submissão:</strong> <?php echo date('d/m/Y H:i', strtotime($ficha['data_submissao'])); ?></p>
                                    
                                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
                                        <p><strong>NIF:</strong> <?php echo $ficha['nif'] ?? '-'; ?></p>
                                        <p><strong>Telefone:</strong> <?php echo $ficha['telefone'] ?? '-'; ?></p>
                                        <p><strong>Email:</strong> <?php echo $ficha['email'] ?? '-'; ?></p>
                                        <p><strong>Data nasc.:</strong> <?php echo $ficha['data_nascimento'] ? date('d/m/Y', strtotime($ficha['data_nascimento'])) : '-'; ?></p>
                                    </div>
                                    
                                    <p><strong>Morada:</strong> <?php echo $ficha['morada'] ?? '-'; ?></p>
                                </div>
                                
                                <!-- Ações -->
                                <div style="width: 300px;">
                                    <form method="POST">
                                        <input type="hidden" name="ficha_id" value="<?php echo $ficha['id']; ?>">
                                        
                                        <div class="form-group">
                                            <label>Observações:</label>
                                            <textarea name="observacoes" rows="2" placeholder="Motivo da aprovação/rejeição..." style="width: 100%;"></textarea>
                                        </div>
                                        
                                        <div style="display: flex; gap: 10px;">
                                            <button type="submit" name="estado" value="aprovada" class="btn" style="background: #28a745; flex: 1;">
                                                ✅ Aprovar
                                            </button>
                                            <button type="submit" name="estado" value="rejeitada" class="btn" style="background: #dc3545; flex: 1;">
                                                ❌ Rejeitar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; padding: 40px; color: #666;">
                        Nenhuma ficha pendente no momento.
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Histórico -->
            <div class="card">
                <h2>📜 Histórico de Validações</h2>
                
                <?php if (mysqli_num_rows($historico) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Aluno</th>
                                <th>Curso</th>
                                <th>Estado</th>
                                <th>Validado por</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ficha = mysqli_fetch_assoc($historico)): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($ficha['data_submissao'])); ?></td>
                                    <td><?php echo $ficha['nome_completo']; ?></td>
                                    <td><?php echo $ficha['curso_nome']; ?></td>
                                    <td>
                                        <?php if ($ficha['estado'] == 'aprovada'): ?>
                                            <span class="badge badge-aluno">Aprovada</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #dc3545;">Rejeitada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $ficha['gestor_login'] ?? '-'; ?></td>
                                    <td><?php echo $ficha['observacoes'] ?? '-'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nenhuma ficha processada ainda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; 2026 IPCA - Validação de Fichas</p>
    </div>
</body>
</html>