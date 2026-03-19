<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || !isAluno()) {
    header('Location: ../login.php');
    exit;
}

$login = $_SESSION['login'];

// Buscar informação da matrícula
$aluno = getAlunoInfo($conn, $login);

// Buscar a ficha do aluno para obter a foto
$ficha_query = mysqli_query($conn, "SELECT * FROM fichas_aluno WHERE aluno_id = '$login'");
$ficha = mysqli_fetch_assoc($ficha_query);

// Verificar se o aluno tem ficha aprovada
$ficha_aprovada = ($ficha && $ficha['estado'] == 'aprovada');

// Verificar estado da matrícula
$estado_matricula = 'sem_matricula'; // Valores possíveis: 'sem_matricula', 'pendente', 'aprovada'
$numero_matricula = '---';
$data_matricula = '--/--/----';
$curso = null; // Inicializar curso como null

// Buscar pedidos de matrícula do aluno
$pedidos_query = mysqli_query($conn, "SELECT * FROM pedidos_matricula WHERE aluno_id = '$login' ORDER BY data_pedido DESC LIMIT 1");
if ($pedidos_query && mysqli_num_rows($pedidos_query) > 0) {
    $pedido = mysqli_fetch_assoc($pedidos_query);
    
    if ($pedido['estado'] == 'aprovado') {
        $estado_matricula = 'aprovada';
        $data_matricula = date('d/m/Y', strtotime($pedido['data_decisao']));
        $numero_matricula = 'IPCA' . date('Y', strtotime($pedido['data_pedido'])) . str_pad($pedido['id'], 4, '0', STR_PAD_LEFT);
        
        // Buscar o curso associado à matrícula aprovada
        $curso_query = mysqli_query($conn, "SELECT * FROM cursos WHERE ID = " . $pedido['curso_id']);
        if ($curso_query && mysqli_num_rows($curso_query) > 0) {
            $curso = mysqli_fetch_assoc($curso_query);
        }
    } elseif ($pedido['estado'] == 'pendente') {
        $estado_matricula = 'pendente';
        $data_pedido = date('d/m/Y', strtotime($pedido['data_pedido']));
        // Buscar o curso do pedido pendente (para mostrar qual curso foi pedido)
        $curso_query = mysqli_query($conn, "SELECT * FROM cursos WHERE ID = " . $pedido['curso_id']);
        if ($curso_query && mysqli_num_rows($curso_query) > 0) {
            $curso = mysqli_fetch_assoc($curso_query);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Matrícula - IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
    <style>
        /* Aumentar a largura máxima do container */
        .container {
            max-width: 1000px !important;
            margin: 0 auto;
        }
        
        /* Ajustar o menu */
        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            background: #f8f9fa;
            padding: 15px 30px;
            border-bottom: 1px solid #dee2e6;
            justify-content: center;
        }
        
        .nav a {
            color: #667eea;
            text-decoration: none;
            padding: 10px 20px;
            margin: 0 5px;
            border-radius: 25px;
            transition: all 0.3s;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .nav a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Destacar o botão ativo */
        .nav a[style*="background: rgba(255,255,255,0.2)"] {
            background: rgba(102, 126, 234, 0.2);
            font-weight: 600;
        }
        
        /* Card especial para Pedir Matrícula */
        .card-destaque {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 20px;
            padding: 40px;
            margin: 30px 0;
            text-align: center;
            box-shadow: 0 20px 40px rgba(40, 167, 69, 0.3);
        }
        
        .card-destaque h2 {
            color: white;
            font-size: 2.2em;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .card-destaque p {
            color: white;
            font-size: 1.2em;
            margin-bottom: 25px;
            opacity: 0.95;
        }
        
        .btn-destaque {
            display: inline-block;
            background: white;
            color: #28a745;
            text-decoration: none;
            padding: 18px 45px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.4em;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            transition: all 0.3s;
            border: 2px solid white;
        }
        
        .btn-destaque:hover {
            background: transparent;
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
        }
        
        .btn-destaque.secondary {
            background: #ffc107;
            color: #333;
            font-size: 1.2em;
            padding: 15px 35px;
        }
        
        .btn-destaque.secondary:hover {
            background: transparent;
            color: #ffc107;
            border-color: #ffc107;
        }
        
        /* Estilo para a foto do aluno */
        .foto-aluno {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            margin: 0 auto;
        }
        
        .foto-placeholder {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        
        .foto-placeholder span {
            font-size: 4em;
            color: white;
        }
        
        /* Status da matrícula */
        .status-matricula {
            display: inline-block;
            padding: 10px 30px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 1.2em;
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
        
        /* Melhorar o espaçamento do conteúdo */
        .content {
            padding: 40px 30px;
        }
        
        /* Grid para os cards */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        /* Estilo para indisponível */
        .indisponivel {
            background: #ccc !important;
            cursor: not-allowed;
            opacity: 0.7;
            pointer-events: none;
        }
        
        .indisponivel:hover {
            transform: none !important;
            box-shadow: none !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h1>🎓 Minha Matrícula</h1>
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
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="plano_estudos.php">📚 Plano de Estudos</a>
            <a href="ficha.php">📝 Ficha Pessoal</a>
            <a href="ver_pedidos.php">📋 Pedidos</a>
        </div>
        
        <div class="content">
            <!-- CARD ESPECIAL PARA PEDIR MATRÍCULA (só aparece se não tiver matrícula aprovada) -->
            <?php if ($estado_matricula != 'aprovada'): ?>
            <div class="card-destaque">
                <h2>🎓 Pedido de Matrícula <?php echo date('Y') . '/' . (date('Y')+1); ?></h2>
                
                <?php if ($ficha_aprovada): ?>
                    <?php if ($estado_matricula == 'pendente'): ?>
                        <p>⏳ Já submeteu um pedido de matrícula. Aguarde a aprovação do funcionário.</p>
                        <p style="margin-top: 15px; font-size: 1em;">
                            <small>Data do pedido: <?php echo $data_pedido; ?></small>
                        </p>
                    <?php else: ?>
                        <p>✅ A sua ficha está aprovada! Pode solicitar a matrícula para o próximo ano letivo.</p>
                        <a href="pedir_matricula.php" class="btn-destaque">
                            🚀 PEDIR MATRÍCULA AGORA
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <p>⚠️ Para pedir matrícula, precisa primeiro ter a ficha de aluno aprovada.</p>
                    <a href="ficha.php" class="btn-destaque secondary">
                        📝 PREENCHER FICHA DE ALUNO
                    </a>
                    <p style="margin-top: 20px; font-size: 1em;">
                        <small>Após preencher, a ficha será validada pelo gestor pedagógico.</small>
                    </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Cartão de matrícula -->
            <div class="card" style="border: 2px solid #667eea;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <?php if ($estado_matricula == 'aprovada'): ?>
                        <span class="status-matricula status-ativa">
                            ✓ MATRÍCULA ATIVA
                        </span>
                    <?php elseif ($estado_matricula == 'pendente'): ?>
                        <span class="status-matricula status-pendente">
                            ⏳ AGUARDANDO APROVAÇÃO
                        </span>
                    <?php else: ?>
                        <span class="status-matricula status-sem-matricula">
                            ⚠️ SEM MATRÍCULA
                        </span>
                    <?php endif; ?>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
                    <div style="text-align: center;">
                        <?php if ($ficha && $ficha['foto_path']): ?>
                            <img src="../<?php echo $ficha['foto_path']; ?>" class="foto-aluno" alt="Foto do aluno">
                        <?php else: ?>
                            <div class="foto-placeholder">
                                <span>👤</span>
                            </div>
                        <?php endif; ?>
                        <p style="margin-top: 15px; color: #666; font-weight: bold;"><?php echo $_SESSION['login']; ?></p>
                    </div>
                    
                    <div>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 12px; font-weight: bold; width: 40%;">Nº Matrícula:</td>
                                <td style="padding: 12px;"><?php echo $numero_matricula; ?></td>
                            </tr>
                            <tr style="background-color: #f8f9fa;">
                                <td style="padding: 12px; font-weight: bold;">Nome:</td>
                                <td style="padding: 12px;"><?php echo $_SESSION['login']; ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 12px; font-weight: bold;">Curso:</td>
                                <td style="padding: 12px;">
                                    <?php if ($curso): ?>
                                        <?php echo $curso['Nome']; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Não definido</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr style="background-color: #f8f9fa;">
                                <td style="padding: 12px; font-weight: bold;">Data Matrícula:</td>
                                <td style="padding: 12px;"><?php echo $data_matricula; ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 12px; font-weight: bold;">Ano Letivo:</td>
                                <td style="padding: 12px;">2025/2026</td>
                            </tr>
                            <tr style="background-color: #f8f9fa;">
                                <td style="padding: 12px; font-weight: bold;">Estado:</td>
                                <td style="padding: 12px;">
                                    <?php if ($estado_matricula == 'aprovada'): ?>
                                        <span class="badge badge-aluno" style="padding: 8px 15px;">Ativo</span>
                                    <?php elseif ($estado_matricula == 'pendente'): ?>
                                        <span class="badge badge-admin" style="padding: 8px 15px;">Pendente</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #6c757d; color: white; padding: 8px 15px;">Sem matrícula</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Grid de cards para documentos e informações -->
            <div class="cards-grid">
                <!-- Documentos -->
                <div class="card">
                    <h2>📄 Documentos</h2>
                    <table class="table">
                        <tr>
                            <td>📋 Comprovativo de Matrícula</td>
                            <td>
                                <span class="btn indisponivel" style="padding: 5px 15px; font-size: 0.9em;">Indisponível</span>
                            </td>
                        </tr>
                        <tr>
                            <td>📊 Horário 2025/2026</td>
                            <td>
                                <span class="btn indisponivel" style="padding: 5px 15px; font-size: 0.9em;">Indisponível</span>
                            </td>
                        </tr>
                        <tr>
                            <td>📚 Plano de Estudos</td>
                            <td><a href="plano_estudos.php" class="btn" style="padding: 5px 15px; font-size: 0.9em;">Ver</a></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Informações importantes -->
                <div class="card">
                    <h2>ℹ️ Informações Importantes</h2>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 10px; border-bottom: 1px solid #eee;">
                            <strong>📅 Início das aulas:</strong> 15/09/2025
                        </li>
                        <li style="padding: 10px; border-bottom: 1px solid #eee;">
                            <strong>📝 Época de exames:</strong> 10/01/2026 a 30/01/2026
                        </li>
                        <li style="padding: 10px; border-bottom: 1px solid #eee;">
                            <strong>💰 Propina:</strong> 697€ (Paga a 10/09/2025)
                        </li>
                        <li style="padding: 10px;">
                            <strong>👨‍🏫 Coordenador:</strong> Prof. João Silva
                        </li>
                    </ul>
                </div>
                
                <!-- Últimos pedidos -->
                <div class="card">
                    <h2>📋 Últimos Pedidos</h2>
                    <?php
                    // Buscar últimos pedidos do aluno
                    $pedidos = mysqli_query($conn, "SELECT * FROM pedidos_matricula WHERE aluno_id = '$login' ORDER BY data_pedido DESC LIMIT 3");
                    
                    if ($pedidos && mysqli_num_rows($pedidos) > 0):
                    ?>
                        <table class="table">
                            <?php while ($pedido = mysqli_fetch_assoc($pedidos)): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?></td>
                                    <td>
                                        <?php if ($pedido['estado'] == 'pendente'): ?>
                                            <span class="badge badge-admin">Pendente</span>
                                        <?php elseif ($pedido['estado'] == 'aprovado'): ?>
                                            <span class="badge badge-aluno">Aprovado</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #dc3545;">Rejeitado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="ver_pedidos.php" class="btn" style="padding: 5px 15px;">Ver todos</a>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; padding: 20px;">
                            Nenhum pedido encontrado.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Documento gerado automaticamente</p>
        </div>
    </div>
</body>
</html>