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

// Redireciona para a página de login
$base = '/rendis/'; // Certifique-se de que esta variável $base corresponde à do seu template_start.php
header('Location: ' . $base . 'index.php');
exit();
?>
