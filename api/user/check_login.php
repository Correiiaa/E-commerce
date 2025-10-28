<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// considerar logged in se existir um identificador de utilizador na sessão
$logged = !empty($_SESSION['user_id']) || !empty($_SESSION['username']);

if (! $logged) {
    echo json_encode(['loggedin' => false]);
    exit;
}

echo json_encode([
    'loggedin'   => true,
    'username'   => $_SESSION['username'] ?? '',
    'first_name' => $_SESSION['first_name'] ?? '',
    'last_name'  => $_SESSION['last_name'] ?? '',
    'role'       => $_SESSION['role'] ?? ''
]);
exit;
