<?php
// logout.php

// Inicia a sessão PHP
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destrói todas as variáveis de sessão
$_SESSION = array();

// Se for usar cookies de sessão, também é necessário deletar o cookie de sessão.
// Nota: Isso irá destruir a sessão, e não apenas os dados da sessão!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destrói a sessão.
session_destroy();
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1') {
    $ambiente = 'local';
} else {
    $ambiente = 'producao';
}

if ($ambiente === 'local') {
    $base = '/rendis/';
} else {
    $base = '/';
}

header('Location: ' . $base . 'index.php');
exit();
?>
