<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'ipca';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die('Erro de ligação: ' . mysqli_connect_error());
}

// Função para verificar se é ADMIN
function isAdmin() {
    return isset($_SESSION['grupo']) && $_SESSION['grupo'] == 'ADMIN';
}

// Função para verificar se é ALUNO
function isAluno() {
    return isset($_SESSION['grupo']) && $_SESSION['grupo'] == 'ALUNO';
}

function isFuncionario() {
    return isset($_SESSION['grupo']) && strtoupper($_SESSION['grupo']) == 'FUNCIONARIO';
}

// Função para redirecionar conforme o perfil
function redirecionarParaDashboard() {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: aluno/dashboard.php');
    }
    exit;
}

// Função para obter informações do aluno
function getAlunoInfo($conn, $login) {
    $sql = "SELECT * FROM users WHERE login = '$login'";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}
?>