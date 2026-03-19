<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Estatísticas para admin
$total_cursos = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM cursos"))['total'];
$total_disciplinas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM disciplinas"))['total'];
$total_utilizadores = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];
$total_alunos = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE grupo=2"))['total'];
$total_planos = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM plano_estudos"))['total'];

// Verificar se a tabela fichas_aluno existe antes de consultar
$fichas_pendentes = 0;
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'fichas_aluno'");
if (mysqli_num_rows($table_check) > 0) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM fichas_aluno WHERE estado = 'submetida'");
    if ($result) {
        $fichas_pendentes = mysqli_fetch_assoc($result)['total'];
    }
}

// Últimos utilizadores registados
$ultimos_users = mysqli_query($conn, "SELECT * FROM users ORDER BY login DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IPCA</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h1>⚙️ Painel de Administração</h1>
                    <p>Bem-vindo, <strong><?php echo $_SESSION['login']; ?></strong></p>
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
            <a href="gerir_cursos.php">📚 Cursos</a>
            <a href="gerir_disciplinas.php">📖 Unidades Curriculares</a>
            <a href="gerir_planos.php">📋 Planos de Estudo</a>
            <a href="gerir_utilizadores.php">👥 Utilizadores</a>
            <a href="validar_fichas.php">📝 Fichas</a>
        </div>
        
        <div class="content">
            <!-- Cards de estatísticas -->
            <div class="stats-grid">
                <div class="info-card">
                    <div class="info-card-icon">📚</div>
                    <div class="info-card-title">Cursos</div>
                    <div class="info-card-value"><?php echo $total_cursos; ?></div>
                    <a href="gerir_cursos.php" class="btn-small">Gerir</a>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon">📖</div>
                    <div class="info-card-title">UCs</div>
                    <div class="info-card-value"><?php echo $total_disciplinas; ?></div>
                    <a href="gerir_disciplinas.php" class="btn-small">Gerir</a>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon">👥</div>
                    <div class="info-card-title">Utilizadores</div>
                    <div class="info-card-value"><?php echo $total_utilizadores; ?></div>
                    <a href="gerir_utilizadores.php" class="btn-small">Gerir</a>
                </div>
                
                <div class="info-card">
                    <div class="info-card-icon">📋</div>
                    <div class="info-card-title">Planos Ativos</div>
                    <div class="info-card-value"><?php echo $total_planos; ?></div>
                    <a href="gerir_planos.php" class="btn-small">Gerir</a>
                </div>

                <!-- Card de Fichas Pendentes -->
                <div class="info-card">
                    <div class="info-card-icon">📝</div>
                    <div class="info-card-title">Fichas Pendentes</div>
                    <div class="info-card-value"><?php echo $fichas_pendentes; ?></div>
                    <a href="validar_fichas.php" class="btn-small">Gerir</a>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <!-- Ações rápidas -->
                <div class="card">
                    <h2>⚡ Ações Rápidas</h2>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <a href="gerir_cursos.php?acao=criar" style="text-decoration: none;">
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; transition: all 0.3s;">
                                <div style="font-size: 2em; margin-bottom: 10px;">➕</div>
                                <div style="color: #333;">Novo Curso</div>
                            </div>
                        </a>
                        
                        <a href="gerir_disciplinas.php?acao=criar" style="text-decoration: none;">
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; transition: all 0.3s;">
                                <div style="font-size: 2em; margin-bottom: 10px;">📖</div>
                                <div style="color: #333;">Nova UC</div>
                            </div>
                        </a>
                        
                        <a href="gerir_planos.php" style="text-decoration: none;">
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; transition: all 0.3s;">
                                <div style="font-size: 2em; margin-bottom: 10px;">📋</div>
                                <div style="color: #333;">Criar Plano</div>
                            </div>
                        </a>

                        <!-- Ação rápida Validar Fichas -->
                        <a href="validar_fichas.php" style="text-decoration: none;">
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; transition: all 0.3s;">
                                <div style="font-size: 2em; margin-bottom: 10px;">✅</div>
                                <div style="color: #333;">Validar Fichas</div>
                                <?php if ($fichas_pendentes > 0): ?>
                                    <span style="background: #ffc107; color: #000; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; margin-left: 5px;">
                                        <?php echo $fichas_pendentes; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                        
                        <a href="gerir_utilizadores.php" style="text-decoration: none;">
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; transition: all 0.3s;">
                                <div style="font-size: 2em; margin-bottom: 10px;">👥</div>
                                <div style="color: #333;">Novo Utilizador</div>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Últimos registos -->
                <div class="card">
                    <h2>👥 Últimos Utilizadores</h2>
                    <table style="width: 100%;">
                        <?php 
                        if ($ultimos_users && mysqli_num_rows($ultimos_users) > 0):
                            while($user = mysqli_fetch_assoc($ultimos_users)): 
                                $grupo_sql = mysqli_query($conn, "SELECT GRUPO FROM grupos WHERE ID=" . $user['grupo']);
                                $grupo = $grupo_sql ? mysqli_fetch_assoc($grupo_sql)['GRUPO'] : 'Desconhecido';
                        ?>
                        <tr>
                            <td style="padding: 8px 0;"><?php echo $user['login']; ?></td>
                            <td style="padding: 8px 0; text-align: right;">
                                <span class="badge <?php echo $grupo == 'ADMIN' ? 'badge-admin' : 'badge-aluno'; ?>">
                                    <?php echo $grupo; ?>
                                </span>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="2" style="text-align: center; padding: 20px; color: #666;">
                                Nenhum utilizador encontrado.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Últimas fichas submetidas (só se a tabela existir) -->
            <?php
            if (mysqli_num_rows($table_check) > 0):
                $ultimas_fichas = mysqli_query($conn, "
                    SELECT f.*, u.login as aluno_login, c.Nome as curso_nome 
                    FROM fichas_aluno f
                    LEFT JOIN users u ON f.aluno_id = u.login
                    LEFT JOIN cursos c ON f.curso_id = c.ID
                    WHERE f.estado = 'submetida'
                    ORDER BY f.data_submissao DESC
                    LIMIT 5
                ");
                
                if ($ultimas_fichas && mysqli_num_rows($ultimas_fichas) > 0):
            ?>
            <div class="card" style="margin-top: 20px;">
                <h2>📝 Últimas Fichas Submetidas</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Aluno</th>
                            <th>Curso</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($ficha = mysqli_fetch_assoc($ultimas_fichas)): ?>
                        <tr>
                            <td><?php echo $ficha['data_submissao'] ? date('d/m/Y H:i', strtotime($ficha['data_submissao'])) : '-'; ?></td>
                            <td><?php echo $ficha['aluno_login'] ?? '-'; ?></td>
                            <td><?php echo $ficha['curso_nome'] ?? '-'; ?></td>
                            <td>
                                <a href="validar_fichas.php" class="btn" style="padding: 5px 10px;">
                                    Validar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php 
                endif;
            endif;
            ?>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Área Administrativa</p>
        </div>
    </div>
</body>
</html>
