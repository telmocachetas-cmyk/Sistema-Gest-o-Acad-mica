<?php
// init.php - Configurações de sessão (deve ser o primeiro a ser incluído)

// Configurações de sessão ANTES de iniciar
ini_set('session.gc_maxlifetime', 31536000); // 1 ano
ini_set('session.cookie_lifetime', 0); // Até fechar o navegador

// Iniciar sessão apenas se não existir
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerar ID periodicamente para segurança
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutos
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
?>