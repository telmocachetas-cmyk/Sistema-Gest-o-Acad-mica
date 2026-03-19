<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['grupo'] != 'FUNCIONARIO') {
    header('Location: ../login.php');
    exit;
}

// Processar filtros de pesquisa
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filtro_curso = isset($_GET['curso']) ? (int)$_GET['curso'] : 0;
$filtro_estado_ficha = isset($_GET['estado_ficha']) ? mysqli_real_escape_string($conn, $_GET['estado_ficha']) : '';
$filtro_estado_matricula = isset($_GET['estado_matricula']) ? mysqli_real_escape_string($conn, $_GET['estado_matricula']) : '';

// Buscar cursos para o filtro
$cursos = mysqli_query($conn, "SELECT ID, Nome FROM cursos ORDER BY Nome");

// Construir query base
$query = "
    SELECT 
        u.login,
        u.email,
        u.nome_completo,
        f.id as ficha_id,
        f.estado as estado_ficha,
        f.curso_id as ficha_curso_id,
        f.data_submissao,
        f.foto_path,
        c.Nome as curso_ficha,
        pm.id as pedido_id,
        pm.estado as estado_matricula,
        pm.curso_id as matricula_curso_id,
        pm.data_pedido,
        pm.data_decisao,
        c2.Nome as curso_matricula
    FROM users u
    LEFT JOIN fichas_aluno f ON u.login = f.aluno_id
    LEFT JOIN cursos c ON f.curso_id = c.ID
    LEFT JOIN pedidos_matricula pm ON u.login = pm.aluno_id AND pm.id = (
        SELECT id FROM pedidos_matricula 
        WHERE aluno_id = u.login 
        ORDER BY data_pedido DESC 
        LIMIT 1
    )
    LEFT JOIN cursos c2 ON pm.curso_id = c2.ID
    WHERE u.grupo = 2
";

// Aplicar filtros
$where = [];
if (!empty($search)) {
    $where[] = "(u.login LIKE '%$search%' OR u.nome_completo LIKE '%$search%' OR u.email LIKE '%$search%')";
}
if ($filtro_curso > 0) {
    $where[] = "(f.curso_id = $filtro_curso OR pm.curso_id = $filtro_curso)";
}
if (!empty($filtro_estado_ficha)) {
    $where[] = "f.estado = '$filtro_estado_ficha'";
}
if (!empty($filtro_estado_matricula)) {
    $where[] = "pm.estado = '$filtro_estado_matricula'";
}

if (!empty($where)) {
    $query .= " AND " . implode(" AND ", $where);
}

$query .= " ORDER BY u.login";

$alunos = mysqli_query($conn, $query);

// Estatísticas rápidas
$total_alunos = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE grupo=2"))['total'];
$com_ficha = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT aluno_id) as total FROM fichas_aluno"))['total'];
$com_matricula_aprovada = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT aluno_id) as total FROM pedidos_matricula WHERE estado='aprovado'"))['total'];
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Alunos - IPCA</title>
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
        
        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card-small {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.8em;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        
        /* Barra de pesquisa e filtros */
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 1em;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .filtros-avancados {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filtro-grupo {
            display: flex;
            flex-direction: column;
        }
        
        .filtro-grupo label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        
        .filtro-grupo select {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            width: 100%;
        }
        
        .btn-filtro {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-filtro:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-limpar {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-limpar:hover {
            background: #5a6268;
        }
        
        /* Avatar do aluno */
        .aluno-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #667eea;
        }
        
        .avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2em;
        }
        
        /* Botão Ficha melhorado */
        .btn-ficha {
            background: #17a2b8;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-ficha:hover {
            background: #138496;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(23, 162, 184, 0.4);
        }
        
        .btn-pedido {
            background: #ffc107;
            color: #333;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-pedido:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header com título à esquerda e menu de perfil -->
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h1>👥 Alunos Registados</h1>
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
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="criar_pauta.php">📝 Criar Pauta</a>
            <a href="ver_pautas.php">📊 Ver Pautas</a>
            <a href="ver_pedidos.php">📋 Pedidos</a>
        </div>
        
        <div class="content">
            <!-- Estatísticas rápidas -->
            <div class="stats-grid">
                <div class="stat-card-small">
                    <div class="stat-value"><?php echo $total_alunos; ?></div>
                    <div class="stat-label">Total Alunos</div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-value"><?php echo $com_ficha; ?></div>
                    <div class="stat-label">Com Ficha</div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-value"><?php echo $com_matricula_aprovada; ?></div>
                    <div class="stat-label">Matriculados</div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-value"><?php echo $total_alunos - $com_ficha; ?></div>
                    <div class="stat-label">Sem Ficha</div>
                </div>
            </div>
            
            <!-- Barra de pesquisa simples -->
            <form method="GET" class="search-bar">
                <input type="text" name="search" class="search-input" placeholder="Pesquisar por login, nome ou email..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-filtro">🔍 Pesquisar</button>
                <a href="ver_alunos.php" class="btn-limpar">🗑️ Limpar</a>
            </form>
            
            <!-- Filtros avançados (opcionais) -->
            <details>
                <summary style="margin-bottom: 10px; color: #667eea; cursor: pointer;">🔍 Filtros Avançados</summary>
                <div class="filtros-avancados">
                    <div class="filtro-grupo">
                        <label>Curso (Ficha ou Matrícula):</label>
                        <select name="curso" id="filtro_curso">
                            <option value="">Todos os cursos</option>
                            <?php while ($curso = mysqli_fetch_assoc($cursos)): ?>
                                <option value="<?php echo $curso['ID']; ?>" <?php echo $filtro_curso == $curso['ID'] ? 'selected' : ''; ?>>
                                    <?php echo $curso['Nome']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filtro-grupo">
                        <label>Estado da Ficha:</label>
                        <select name="estado_ficha" id="filtro_estado_ficha">
                            <option value="">Todos</option>
                            <option value="rascunho" <?php echo $filtro_estado_ficha == 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                            <option value="submetida" <?php echo $filtro_estado_ficha == 'submetida' ? 'selected' : ''; ?>>Submetida</option>
                            <option value="aprovada" <?php echo $filtro_estado_ficha == 'aprovada' ? 'selected' : ''; ?>>Aprovada</option>
                            <option value="rejeitada" <?php echo $filtro_estado_ficha == 'rejeitada' ? 'selected' : ''; ?>>Rejeitada</option>
                        </select>
                    </div>
                    
                    <div class="filtro-grupo">
                        <label>Estado da Matrícula:</label>
                        <select name="estado_matricula" id="filtro_estado_matricula">
                            <option value="">Todos</option>
                            <option value="pendente" <?php echo $filtro_estado_matricula == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="aprovado" <?php echo $filtro_estado_matricula == 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                            <option value="rejeitado" <?php echo $filtro_estado_matricula == 'rejeitado' ? 'selected' : ''; ?>>Rejeitado</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button type="submit" class="btn-filtro">Aplicar Filtros</button>
                        <a href="ver_alunos.php" class="btn-limpar">Limpar</a>
                    </div>
                </div>
            </details>
            
            <!-- Lista de alunos -->
            <div class="card">
                <h2>Lista de Alunos</h2>
                
                <?php if ($alunos && mysqli_num_rows($alunos) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th>Contacto</th>
                                <th>Ficha</th>
                                <th>Matrícula</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($aluno = mysqli_fetch_assoc($alunos)): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php if ($aluno['foto_path']): ?>
                                            <img src="../<?php echo $aluno['foto_path']; ?>" class="aluno-avatar">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <?php echo strtoupper(substr($aluno['login'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo $aluno['login']; ?></strong><br>
                                            <small><?php echo $aluno['nome_completo'] ?? 'Nome não preenchido'; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($aluno['email']): ?>
                                        <a href="mailto:<?php echo $aluno['email']; ?>"><?php echo $aluno['email']; ?></a>
                                    <?php else: ?>
                                        <span style="color: #999;">Não definido</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($aluno['ficha_id']): ?>
                                        <span class="badge <?php 
                                            echo $aluno['estado_ficha'] == 'aprovada' ? 'badge-aluno' : 
                                                ($aluno['estado_ficha'] == 'submetida' ? 'badge-admin' : 
                                                ($aluno['estado_ficha'] == 'rejeitada' ? 'badge' : 'badge-admin')); 
                                        ?>" style="<?php echo $aluno['estado_ficha'] == 'rejeitada' ? 'background: #dc3545;' : ''; ?>">
                                            <?php echo ucfirst($aluno['estado_ficha']); ?>
                                        </span>
                                        <?php if ($aluno['curso_ficha']): ?>
                                            <br><small><?php echo $aluno['curso_ficha']; ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Sem ficha</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($aluno['pedido_id']): ?>
                                        <span class="badge <?php 
                                            echo $aluno['estado_matricula'] == 'aprovado' ? 'badge-aluno' : 
                                                ($aluno['estado_matricula'] == 'pendente' ? 'badge-admin' : 'badge'); 
                                        ?>" style="<?php echo $aluno['estado_matricula'] == 'rejeitado' ? 'background: #dc3545;' : ''; ?>">
                                            <?php echo ucfirst($aluno['estado_matricula']); ?>
                                        </span>
                                        <?php if ($aluno['curso_matricula']): ?>
                                            <br><small><?php echo $aluno['curso_matricula']; ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Sem pedido</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <?php if ($aluno['ficha_id']): ?>
                                            <a href="../funcionario/ver_ficha.php?aluno=<?php echo $aluno['login']; ?>" class="btn-ficha">
                                                📝 Ver Ficha
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($aluno['pedido_id']): ?>
                                            <a href="ver_pedidos.php?aluno=<?php echo $aluno['login']; ?>" class="btn-pedido">
                                                📋 Ver Pedido
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px; color: #666;">
                        <div style="font-size: 4em; margin-bottom: 20px;">👥</div>
                        <h3 style="margin-bottom: 15px;">Nenhum aluno encontrado</h3>
                        <p>
                            <?php if ($search || $filtro_curso || $filtro_estado_ficha || $filtro_estado_matricula): ?>
                                Não existem alunos com os critérios de pesquisa selecionados.
                                <br><a href="ver_alunos.php">Limpar filtros</a>
                            <?php else: ?>
                                Ainda não existem alunos registados no sistema.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Área do Funcionário</p>
        </div>
    </div>
    
    <script>
        // Submeter formulário quando os filtros avançados mudarem
        document.querySelectorAll('#filtro_curso, #filtro_estado_ficha, #filtro_estado_matricula').forEach(select => {
            select.addEventListener('change', function() {
                document.querySelector('form').submit();
            });
        });
    </script>
</body>
</html>