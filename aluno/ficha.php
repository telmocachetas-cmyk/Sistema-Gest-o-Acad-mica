<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['grupo'] != 'ALUNO') {
    header('Location: ../login.php');
    exit;
}

$login = $_SESSION['login'];
$mensagem = '';

// Buscar cursos para o select
$cursos = mysqli_query($conn, "SELECT ID, Nome FROM cursos WHERE ativo = 1 OR ativo IS NULL");

// Verificar se já existe ficha
$ficha = mysqli_query($conn, "SELECT * FROM fichas_aluno WHERE aluno_id = '$login'");
$ficha = mysqli_fetch_assoc($ficha);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<!-- DEBUG: Formulário submetido -->";
    
    $curso_id = mysqli_real_escape_string($conn, $_POST['curso_id']);
    $nome_completo = mysqli_real_escape_string($conn, $_POST['nome_completo']);
    $data_nascimento = mysqli_real_escape_string($conn, $_POST['data_nascimento']);
    $nif = mysqli_real_escape_string($conn, $_POST['nif']);
    $morada = mysqli_real_escape_string($conn, $_POST['morada']);
    $telefone = mysqli_real_escape_string($conn, $_POST['telefone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $acao = $_POST['acao'] ?? 'rascunho';
    
    // Processar upload da foto
    $foto_path = $ficha['foto_path'] ?? null;
    $upload_realizado = false;
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        echo "<!-- DEBUG: Ficheiro recebido: " . $_FILES['foto']['name'] . " -->";
        echo "<!-- DEBUG: Tamanho: " . $_FILES['foto']['size'] . " bytes -->";
        echo "<!-- DEBUG: Tipo: " . $_FILES['foto']['type'] . " -->";
        
        $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $extensoes_permitidas = ['jpg', 'jpeg', 'png'];
        
        if (in_array($extensao, $extensoes_permitidas)) {
            if ($_FILES['foto']['size'] <= 2 * 1024 * 1024) {
                if (!is_dir('../uploads')) {
                    mkdir('../uploads', 0777, true);
                    echo "<!-- DEBUG: Pasta uploads criada -->";
                }
                
                $nome_ficheiro = 'aluno_' . $login . '_' . time() . '.' . $extensao;
                $destino = '../uploads/' . $nome_ficheiro;
                echo "<!-- DEBUG: Destino: $destino -->";
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                    $foto_path = 'uploads/' . $nome_ficheiro;
                    $upload_realizado = true;
                    echo "<!-- DEBUG: Upload OK: $foto_path -->";
                } else {
                    $mensagem = '<div class="alert alert-error">Erro ao mover o ficheiro.</div>';
                    echo "<!-- DEBUG: Erro ao mover ficheiro -->";
                }
            } else {
                $mensagem = '<div class="alert alert-error">Ficheiro demasiado grande (máx 2MB)</div>';
                echo "<!-- DEBUG: Ficheiro demasiado grande -->";
            }
        } else {
            $mensagem = '<div class="alert alert-error">Formato não permitido. Use JPG ou PNG</div>';
            echo "<!-- DEBUG: Formato inválido: $extensao -->";
        }
    } else {
        $erro_upload = $_FILES['foto']['error'] ?? 'sem ficheiro';
        echo "<!-- DEBUG: Nenhum ficheiro enviado ou erro no upload. Código erro: $erro_upload -->";
    }
    
    if ($ficha) {
        // Atualizar ficha existente
        if ($acao == 'submeter') {
            $sql = "UPDATE fichas_aluno SET 
                    curso_id = '$curso_id',
                    nome_completo = '$nome_completo',
                    data_nascimento = '$data_nascimento',
                    nif = '$nif',
                    morada = '$morada',
                    telefone = '$telefone',
                    email = '$email'";
            
            if ($upload_realizado) {
                $sql .= ", foto_path = '$foto_path'";
                echo "<!-- DEBUG: A atualizar foto para: $foto_path -->";
            } else {
                echo "<!-- DEBUG: A manter foto existente: " . ($ficha['foto_path'] ?? 'sem foto') . " -->";
            }
            
            $sql .= ", estado = 'submetida', data_submissao = NOW()
                    WHERE aluno_id = '$login'";
        } else {
            $sql = "UPDATE fichas_aluno SET 
                    curso_id = '$curso_id',
                    nome_completo = '$nome_completo',
                    data_nascimento = '$data_nascimento',
                    nif = '$nif',
                    morada = '$morada',
                    telefone = '$telefone',
                    email = '$email'";
            
            if ($upload_realizado) {
                $sql .= ", foto_path = '$foto_path'";
                echo "<!-- DEBUG: A atualizar foto para: $foto_path -->";
            } else {
                echo "<!-- DEBUG: A manter foto existente: " . ($ficha['foto_path'] ?? 'sem foto') . " -->";
            }
            
            $sql .= ", estado = 'rascunho'
                    WHERE aluno_id = '$login'";
        }
        
        echo "<!-- DEBUG SQL: " . str_replace("'", '"', $sql) . " -->";
        
        if (mysqli_query($conn, $sql)) {
            $mensagem = '<div class="alert alert-success">Ficha ' . ($acao == 'submeter' ? 'submetida' : 'guardada') . ' com sucesso!</div>';
            echo "<!-- DEBUG: Query executada com sucesso -->";
        } else {
            $mensagem = '<div class="alert alert-error">Erro: ' . mysqli_error($conn) . '</div>';
            echo "<!-- DEBUG: Erro na query: " . mysqli_error($conn) . " -->";
        }
    } else {
        // Criar nova ficha
        if ($acao == 'submeter') {
            $sql = "INSERT INTO fichas_aluno 
                    (aluno_id, curso_id, nome_completo, data_nascimento, nif, morada, telefone, email, foto_path, estado, data_submissao) 
                    VALUES ('$login', '$curso_id', '$nome_completo', '$data_nascimento', '$nif', '$morada', '$telefone', '$email', " . ($foto_path ? "'$foto_path'" : "NULL") . ", 'submetida', NOW())";
        } else {
            $sql = "INSERT INTO fichas_aluno 
                    (aluno_id, curso_id, nome_completo, data_nascimento, nif, morada, telefone, email, foto_path, estado) 
                    VALUES ('$login', '$curso_id', '$nome_completo', '$data_nascimento', '$nif', '$morada', '$telefone', '$email', " . ($foto_path ? "'$foto_path'" : "NULL") . ", 'rascunho')";
        }
        
        echo "<!-- DEBUG SQL (insert): " . str_replace("'", '"', $sql) . " -->";
        
        if (mysqli_query($conn, $sql)) {
            $mensagem = '<div class="alert alert-success">Ficha criada com sucesso!</div>';
            echo "<!-- DEBUG: Insert executado com sucesso -->";
        } else {
            $mensagem = '<div class="alert alert-error">Erro: ' . mysqli_error($conn) . '</div>';
            echo "<!-- DEBUG: Erro no insert: " . mysqli_error($conn) . " -->";
        }
    }
    
    // Recarregar dados
    $ficha = mysqli_query($conn, "SELECT * FROM fichas_aluno WHERE aluno_id = '$login'");
    $ficha = mysqli_fetch_assoc($ficha);
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Ficha - Aluno</title>
    <link rel="stylesheet" href="../estilo.css">
    <style>
        /* Estilo para foto tipo retrato (vertical) */
        .foto-container {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .foto-preview {
            width: 200px;
            height: 250px;
            border-radius: 10px;
            object-fit: cover;
            border: 3px solid #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .foto-placeholder {
            width: 200px;
            height: 250px;
            border-radius: 10px;
            background: linear-gradient(135deg, #e0e0e0 0%, #f0f0f0 100%);
            border: 3px dashed #667eea;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #666;
        }
        
        .foto-placeholder i {
            font-size: 3em;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .estado-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .estado-rascunho { background: #ffc107; color: #000; }
        .estado-submetida { background: #17a2b8; color: #fff; }
        .estado-aprovada { background: #28a745; color: #fff; }
        .estado-rejeitada { background: #dc3545; color: #fff; }
        
        .foto-upload {
            border: 2px dashed #667eea;
            padding: 15px;
            text-align: center;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
            width: 200px;
            margin-top: 15px;
        }
        
        .foto-upload:hover {
            background: #e9ecef;
            border-color: #5a67d8;
        }
        
        .foto-upload i {
            font-size: 2em;
            color: #667eea;
            margin-bottom: 5px;
            display: block;
        }
        
        .grid-ficha {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 40px;
        }
        
        @media (max-width: 768px) {
            .grid-ficha {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header com título à esquerda e menu de perfil -->
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <div>
                    <h1>📝 Ficha de Aluno</h1>
                    <p><strong>Bem-vindo, <?php echo $_SESSION['login']; ?></strong></p>
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
            <a href="minha_matricula.php">🎓 Matrícula</a>
            <a href="plano_estudos.php">📚 Plano de Estudos</a>
            <a href="ver_pedidos.php">📋 Pedidos</a>
        </div>
        
        <div class="content">
            <?php echo $mensagem; ?>
            
            <?php if ($ficha && $ficha['estado'] == 'aprovada'): ?>
                <div class="alert alert-success">
                    ✅ A sua ficha foi APROVADA! Já pode pedir matrícula.
                </div>
            <?php elseif ($ficha && $ficha['estado'] == 'rejeitada'): ?>
                <div class="alert alert-error">
                    ❌ Ficha rejeitada. Motivo: <?php echo $ficha['observacoes']; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
                    <h2>Dados Pessoais</h2>
                    <?php if ($ficha): ?>
                        <span class="estado-badge estado-<?php echo $ficha['estado']; ?>">
                            <?php echo strtoupper($ficha['estado']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid-ficha">
                        <!-- Coluna da foto -->
                        <div class="foto-container">
                            <label style="display: block; margin-bottom: 10px; font-weight: bold;">Fotografia</label>
                            
                            <?php if ($ficha && $ficha['foto_path']): ?>
                                <img src="../<?php echo $ficha['foto_path']; ?>" class="foto-preview" id="fotoPreview">
                            <?php else: ?>
                                <div class="foto-placeholder" id="fotoPlaceholder">
                                    <i>📷</i>
                                    <span>Sem foto</span>
                                </div>
                                <img src="" class="foto-preview" id="fotoPreview" style="display: none;">
                            <?php endif; ?>
                            
                            <!-- APENAS mostrar upload se NÃO TIVER foto -->
                            <?php if (!$ficha || !$ficha['foto_path']): ?>
                                <?php if ($ficha['estado'] != 'submetida'): ?>
                                    <label for="foto" class="foto-upload" id="uploadLabel">
                                        <i>📷</i>
                                        <strong>Selecionar foto</strong><br>
                                        <small>JPG/PNG • Máx 2MB</small>
                                    </label>
                                    <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png" style="display: none;" onchange="validarTamanhoFoto(this)">
                                    <div id="fotoErro" style="color: #dc3545; font-size: 0.85em; margin-top: 5px; display: none;"></div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($ficha && $ficha['estado'] == 'rejeitada'): ?>
                                <p style="color: #dc3545; margin-top: 10px; text-align: center;">
                                    <strong>Motivo da rejeição:</strong><br>
                                    <?php echo $ficha['observacoes']; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Coluna dos dados -->
                        <div>
                            <div class="form-group">
                                <label>Nome Completo:</label>
                                <input type="text" name="nome_completo" required 
                                       value="<?php echo $ficha['nome_completo'] ?? ''; ?>"
                                       <?php echo ($ficha && ($ficha['estado'] == 'submetida' || $ficha['estado'] == 'aprovada')) ? 'readonly' : ''; ?>>
                            </div>
                            
                            <div class="form-group">
                                <label>Data Nascimento:</label>
                                <input type="date" name="data_nascimento" required 
                                       value="<?php echo $ficha['data_nascimento'] ?? ''; ?>"
                                       <?php echo ($ficha && ($ficha['estado'] == 'submetida' || $ficha['estado'] == 'aprovada')) ? 'readonly' : ''; ?>>
                            </div>
                            
                            <div class="form-group">
                                <label>NIF:</label>
                                <input type="text" name="nif" 
                                       value="<?php echo $ficha['nif'] ?? ''; ?>"
                                       <?php echo ($ficha && ($ficha['estado'] == 'submetida' || $ficha['estado'] == 'aprovada')) ? 'readonly' : ''; ?>>
                            </div>
                            
                            <div class="form-group">
                                <label>Morada:</label>
                                <textarea name="morada" <?php echo ($ficha && ($ficha['estado'] == 'submetida' || $ficha['estado'] == 'aprovada')) ? 'readonly' : ''; ?>><?php echo $ficha['morada'] ?? ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Telefone:</label>
                                <input type="text" name="telefone" 
                                       value="<?php echo $ficha['telefone'] ?? ''; ?>"
                                       <?php echo ($ficha && ($ficha['estado'] == 'submetida' || $ficha['estado'] == 'aprovada')) ? 'readonly' : ''; ?>>
                            </div>
                            
                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" name="email" 
                                       value="<?php echo $ficha['email'] ?? ''; ?>"
                                       <?php echo ($ficha && ($ficha['estado'] == 'submetida' || $ficha['estado'] == 'aprovada')) ? 'readonly' : ''; ?>>
                            </div>
                            
                            <div class="form-group">
                                <label>Curso Pretendido:</label>
                                <select name="curso_id" required <?php echo ($ficha && ($ficha['estado'] == 'submetida' || $ficha['estado'] == 'aprovada')) ? 'disabled' : ''; ?>>
                                    <option value="">Selecione...</option>
                                    <?php while ($curso = mysqli_fetch_assoc($cursos)): ?>
                                        <option value="<?php echo $curso['ID']; ?>"
                                            <?php echo ($ficha && $ficha['curso_id'] == $curso['ID']) ? 'selected' : ''; ?>>
                                            <?php echo $curso['Nome']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <?php if ($ficha && ($ficha['estado'] == 'submetida' || $ficha['estado'] == 'aprovada')): ?>
                                    <input type="hidden" name="curso_id" value="<?php echo $ficha['curso_id']; ?>">
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!$ficha || $ficha['estado'] == 'rascunho' || $ficha['estado'] == 'rejeitada'): ?>
                                <div style="display: flex; gap: 10px; margin-top: 20px;">
                                    <button type="submit" name="acao" value="rascunho" class="btn" style="flex: 1;">
                                        💾 Guardar Rascunho
                                    </button>
                                    <button type="submit" name="acao" value="submeter" class="btn" style="flex: 1; background: #28a745;">
                                        📤 Submeter para Validação
                                    </button>
                                </div>
                            <?php elseif ($ficha['estado'] == 'submetida'): ?>
                                <p style="text-align: center; padding: 20px; background: #e3f2fd; border-radius: 10px;">
                                    ⏳ Ficha submetida. Aguarde validação do gestor.
                                </p>
                            <?php elseif ($ficha['estado'] == 'aprovada'): ?>
                                <p style="text-align: center; padding: 20px; background: #d4edda; border-radius: 10px;">
                                    ✅ Ficha aprovada! Pode pedir matrícula.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    function previewFoto(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById('fotoPreview');
                var placeholder = document.getElementById('fotoPlaceholder');
                
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
                
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function validarTamanhoFoto(input) {
        var erroDiv = document.getElementById('fotoErro');
        
        if (input.files && input.files[0]) {
            var fileSize = input.files[0].size / 1024 / 1024; // em MB
            
            if (fileSize > 2) {
                erroDiv.style.display = 'block';
                erroDiv.innerHTML = '❌ Ficheiro demasiado grande. O limite é 2MB.';
                input.value = '';
                
                var preview = document.getElementById('fotoPreview');
                var placeholder = document.getElementById('fotoPlaceholder');
                if (preview) preview.style.display = 'none';
                if (placeholder) placeholder.style.display = 'flex';
                
                return false;
            } else {
                erroDiv.style.display = 'none';
                previewFoto(input);
            }
        }
        return true;
    }
    </script>
</body>
</html>