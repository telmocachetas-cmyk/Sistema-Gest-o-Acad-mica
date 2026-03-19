<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['grupo'] != 'FUNCIONARIO') {
    header('Location: ../login.php');
    exit;
}

// Processar filtros
$filtro_uc = isset($_GET['uc']) ? (int)$_GET['uc'] : 0;
$filtro_epoca = isset($_GET['epoca']) ? mysqli_real_escape_string($conn, $_GET['epoca']) : '';
$filtro_ano = isset($_GET['ano_letivo']) ? mysqli_real_escape_string($conn, $_GET['ano_letivo']) : '';

// Buscar UCs para o filtro
$ucs = mysqli_query($conn, "SELECT ID, Nome_disc FROM disciplinas ORDER BY Nome_disc");

// Construir query com filtros
$where = [];
if ($filtro_uc > 0) {
    $where[] = "p.uc_id = $filtro_uc";
}
if (!empty($filtro_epoca)) {
    $where[] = "p.epoca = '$filtro_epoca'";
}
if (!empty($filtro_ano)) {
    $where[] = "p.ano_letivo = '$filtro_ano'";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Buscar pautas com filtros
$pautas = mysqli_query($conn, "
    SELECT p.*, d.Nome_disc as uc_nome, 
           (SELECT COUNT(*) FROM notas WHERE pauta_id = p.id) as total_alunos,
           (SELECT COUNT(*) FROM notas WHERE pauta_id = p.id AND nota IS NOT NULL) as notas_lancadas,
           (SELECT COUNT(*) FROM notas WHERE pauta_id = p.id AND nota >= 9.5) as aprovados
    FROM pautas p
    JOIN disciplinas d ON p.uc_id = d.ID
    $where_clause
    ORDER BY p.data_criacao DESC
");

// Buscar estatísticas gerais
$stats = mysqli_query($conn, "
    SELECT 
        COUNT(DISTINCT p.id) as total_pautas,
        COUNT(DISTINCT p.uc_id) as total_ucs_com_pauta,
        SUM(CASE WHEN n.nota IS NOT NULL THEN 1 ELSE 0 END) as total_notas,
        AVG(n.nota) as media_notas
    FROM pautas p
    LEFT JOIN notas n ON p.id = n.pauta_id
") or die(mysqli_error($conn));
$stats_data = mysqli_fetch_assoc($stats);

// Buscar épocas únicas para o filtro
$epocas = mysqli_query($conn, "SELECT DISTINCT epoca FROM pautas ORDER BY epoca");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Pautas - IPCA</title>
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
        
        /* Estilo para os filtros */
        .filtros {
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
        
        .filtro-grupo select, .filtro-grupo input {
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
            height: 38px;
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
            height: 38px;
        }
        
        .btn-limpar:hover {
            background: #5a6268;
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
        
        /* Badge para aprovados/reprovados */
        .badge-aprovado {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
        
        .badge-reprovado {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header com título à esquerda e menu de perfil -->
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h1>📊 Pautas de Avaliação</h1>
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
            <a href="ver_pedidos.php">📋 Pedidos</a>
            <a href="ver_alunos.php">👥 Alunos</a>
        </div>
        
        <div class="content">
            <!-- Estatísticas rápidas -->
            <div class="stats-grid">
                <div class="stat-card-small">
                    <div class="stat-value"><?php echo $stats_data['total_pautas'] ?? 0; ?></div>
                    <div class="stat-label">Total de Pautas</div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-value"><?php echo $stats_data['total_ucs_com_pauta'] ?? 0; ?></div>
                    <div class="stat-label">UCs com Pauta</div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-value"><?php echo $stats_data['total_notas'] ?? 0; ?></div>
                    <div class="stat-label">Notas Lançadas</div>
                </div>
                <div class="stat-card-small">
                    <div class="stat-value"><?php echo $stats_data['media_notas'] ? number_format($stats_data['media_notas'], 1) : '0'; ?></div>
                    <div class="stat-label">Média Geral</div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filtros">
                <div class="filtro-grupo">
                    <label>Unidade Curricular:</label>
                    <select name="uc" id="filtro_uc">
                        <option value="">Todas as UCs</option>
                        <?php while ($uc = mysqli_fetch_assoc($ucs)): ?>
                            <option value="<?php echo $uc['ID']; ?>" <?php echo $filtro_uc == $uc['ID'] ? 'selected' : ''; ?>>
                                <?php echo $uc['Nome_disc']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filtro-grupo">
                    <label>Época:</label>
                    <select name="epoca" id="filtro_epoca">
                        <option value="">Todas as épocas</option>
                        <option value="Normal" <?php echo $filtro_epoca == 'Normal' ? 'selected' : ''; ?>>Normal</option>
                        <option value="Recurso" <?php echo $filtro_epoca == 'Recurso' ? 'selected' : ''; ?>>Recurso</option>
                        <option value="Especial" <?php echo $filtro_epoca == 'Especial' ? 'selected' : ''; ?>>Especial</option>
                    </select>
                </div>
                
                <div class="filtro-grupo">
                    <label>Ano Letivo:</label>
                    <input type="text" name="ano_letivo" id="filtro_ano" placeholder="Ex: 2025/2026" value="<?php echo htmlspecialchars($filtro_ano); ?>">
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button onclick="aplicarFiltros()" class="btn-filtro">🔍 Filtrar</button>
                    <a href="ver_pautas.php" class="btn-limpar">🗑️ Limpar</a>
                </div>
            </div>
            
            <!-- Lista de pautas -->
            <div class="card">
                <h2>Lista de Pautas</h2>
                
                <?php if ($pautas && mysqli_num_rows($pautas) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>UC</th>
                                <th>Ano Letivo</th>
                                <th>Época</th>
                                <th>Data Criação</th>
                                <th>Progresso</th>
                                <th>Aprovados</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($pauta = mysqli_fetch_assoc($pautas)): 
                                $progresso = $pauta['total_alunos'] > 0 ? round(($pauta['notas_lancadas'] / $pauta['total_alunos']) * 100) : 0;
                                $taxa_aprovacao = $pauta['notas_lancadas'] > 0 ? round(($pauta['aprovados'] / $pauta['notas_lancadas']) * 100) : 0;
                            ?>
                            <tr>
                                <td><strong><?php echo $pauta['uc_nome']; ?></strong></td>
                                <td><?php echo $pauta['ano_letivo']; ?></td>
                                <td>
                                    <span class="badge" style="background: #17a2b8;"><?php echo $pauta['epoca']; ?></span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($pauta['data_criacao'])); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="flex: 1; background: #e0e0e0; border-radius: 10px; height: 10px;">
                                            <div style="width: <?php echo $progresso; ?>%; background: #28a745; height: 10px; border-radius: 10px;"></div>
                                        </div>
                                        <span style="font-size: 0.9em;"><?php echo $pauta['notas_lancadas']; ?>/<?php echo $pauta['total_alunos']; ?></span>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($pauta['notas_lancadas'] > 0): ?>
                                        <span class="badge badge-aluno"><?php echo $taxa_aprovacao; ?>%</span>
                                        <br>
                                        <small><?php echo $pauta['aprovados']; ?> aprovados</small>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="lancar_notas.php?pauta_id=<?php echo $pauta['id']; ?>" class="btn" style="background: #28a745; padding: 5px 10px;">
                                        ✏️ Lançar Notas
                                    </a>
                                    <a href="ver_pauta_detalhes.php?id=<?php echo $pauta['id']; ?>" class="btn" style="background: #17a2b8; padding: 5px 10px;">
                                        👁️ Ver
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px; color: #666;">
                        <div style="font-size: 4em; margin-bottom: 20px;">📊</div>
                        <h3 style="margin-bottom: 15px;">Nenhuma pauta encontrada</h3>
                        <p style="margin-bottom: 20px;">
                            <?php if ($filtro_uc || $filtro_epoca || $filtro_ano): ?>
                                Não existem pautas com os filtros selecionados.
                                <br><a href="ver_pautas.php">Limpar filtros</a>
                            <?php else: ?>
                                Ainda não foram criadas pautas de avaliação.
                            <?php endif; ?>
                        </p>
                        <a href="criar_pauta.php" class="btn" style="padding: 12px 30px;">➕ Criar Primeira Pauta</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Área do Funcionário</p>
        </div>
    </div>
    
    <script>
        function aplicarFiltros() {
            let uc = document.getElementById('filtro_uc').value;
            let epoca = document.getElementById('filtro_epoca').value;
            let ano = document.getElementById('filtro_ano').value;
            
            let url = 'ver_pautas.php?';
            if (uc) url += 'uc=' + uc + '&';
            if (epoca) url += 'epoca=' + encodeURIComponent(epoca) + '&';
            if (ano) url += 'ano_letivo=' + encodeURIComponent(ano);
            
            window.location.href = url;
        }
    </script>
</body>
</html>