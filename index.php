<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['login'])) {
    header('Location: login.php');
    exit;
}

// Buscar estatísticas
$total_cursos = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM cursos"))['total'];
$total_disciplinas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM disciplinas"))['total'];
$total_utilizadores = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];

// Determinar o link correto para o dashboard conforme o perfil
$dashboard_link = '#';
if ($_SESSION['grupo'] == 'ADMIN') {
    $dashboard_link = 'admin/dashboard.php';
} elseif ($_SESSION['grupo'] == 'FUNCIONARIO') {
    $dashboard_link = 'funcionario/dashboard.php';
} else {
    $dashboard_link = 'aluno/dashboard.php';
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo - IPCA</title>
    <link rel="stylesheet" href="estilo.css">
    <style>
        .dashboard-cards {
            display: flex !important;
            flex-direction: row !important;
            gap: 20px !important;
            margin-bottom: 30px !important;
            flex-wrap: wrap !important;
        }
        
        .dashboard-card {
            flex: 1 !important;
            min-width: 250px !important;
            background: white !important;
            border-radius: 15px !important;
            padding: 25px !important;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
            text-align: center !important;
            border: 1px solid #f0f0f0 !important;
        }
        
        .card-number {
            font-size: 3.5em !important;
            font-weight: bold !important;
            color: #667eea !important;
            margin: 10px 0 !important;
        }
        
        .card-label {
            font-size: 1.2em !important;
            color: #666 !important;
            margin-bottom: 15px !important;
        }
        
        .card-button {
            display: inline-block !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            text-decoration: none !important;
            padding: 10px 25px !important;
            border-radius: 25px !important;
            margin-top: 10px !important;
            transition: all 0.3s !important;
        }
        
        .card-button:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4) !important;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .welcome-banner h2 {
            color: white;
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .welcome-banner p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .btn-dashboard {
            display: inline-block;
            background: white;
            color: #667eea;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.1em;
            margin-top: 15px;
            transition: all 0.3s;
            border: 2px solid white;
        }
        
        .btn-dashboard:hover {
            background: transparent;
            color: white;
            transform: translateY(-2px);
        }
        
        /* Esconder completamente a barra de navegação */
        .nav {
            display: none;
        }
        
        /* Estilo para indisponível */
        .indisponivel {
            background: #6c757d !important;
            cursor: not-allowed;
            opacity: 0.7;
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
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>🎓 IPCA - Sistema de Gestão Académica</h1>
                    <p>Bem-vindo, <strong><?php echo $_SESSION['login']; ?></strong>! 
                       (<?php echo $_SESSION['grupo']; ?>)</p>
                </div>
                <div class="menu-perfil">
                    <?php if ($_SESSION['grupo'] == 'FUNCIONARIO'): ?>
                        <span class="profile-badge" style="background: #17a2b8;">
                            <?php echo $_SESSION['grupo']; ?>
                        </span>
                    <?php else: ?>
                        <span class="profile-badge profile-<?php echo strtolower($_SESSION['grupo']); ?>">
                            <?php echo $_SESSION['grupo']; ?>
                        </span>
                    <?php endif; ?>
                    
                    <div class="menu-perfil-content">
                        <a href="index.php">🏠 Página Inicial</a>
                        <?php if ($_SESSION['grupo'] == 'ADMIN'): ?>
                            <a href="admin/perfil_admin.php">👤 Meu Perfil</a>
                        <?php elseif ($_SESSION['grupo'] == 'FUNCIONARIO'): ?>
                            <a href="funcionario/perfil_funcionario.php">👤 Meu Perfil</a>
                        <?php else: ?>
                            <a href="aluno/perfil_aluno.php">👤 Meu Perfil</a>
                        <?php endif; ?>
                        <a href="logout.php">🚪 Logout</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Barra de navegação removida -->
        
        <div class="content">
            <!-- Banner de boas-vindas com botão de acesso ao dashboard -->
            <div class="welcome-banner">
                <h2>👋 Olá, <?php echo $_SESSION['login']; ?>!</h2>
                <p>Bem-vindo ao Sistema de Gestão Académica do IPCA.</p>
                <a href="<?php echo $dashboard_link; ?>" class="btn-dashboard">
                    Aceder ao Dashboard →
                </a>
            </div>
            
            <!-- Estatísticas Gerais do Sistema -->
            <h2 style="margin-bottom: 20px;">📊 Estatísticas Gerais</h2>
            <div class="dashboard-cards">
                <div class="dashboard-card">
                    <div class="card-number"><?php echo $total_cursos; ?></div>
                    <div class="card-label">Cursos</div>
                    <a href="listar_cursos.php" class="card-button">Ver Cursos</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-number"><?php echo $total_disciplinas; ?></div>
                    <div class="card-label">Unidades Curriculares</div>
                    <a href="listar_disciplinas.php" class="card-button">Ver Unidades Curriculares</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-number">⏰</div>
                    <div class="card-label">Horários</div>
                    <span class="card-button indisponivel">Indisponível</span>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 IPCA - Sistema de Gestão Académica</p>
        </div>
    </div>
</body>
</html>
<script>
