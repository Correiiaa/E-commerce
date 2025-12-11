<?php

require_once  "../config.php";
require_once  "../core.php";

// garantir sessão iniciada para poder fazer logout corretamente
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// limpar dados da sessão
$_SESSION = [];
session_unset();
session_destroy();
session_write_close();

// remover cookie da sessão do browser
$params = session_get_cookie_params();
setcookie(
    session_name(),
    '',
    time() - 42000,
    $params['path'] ?? '/',
    $params['domain'] ?? '',
    $params['secure'] ?? false,
    $params['httponly'] ?? true
);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['loggedout' => true]);
exit;
