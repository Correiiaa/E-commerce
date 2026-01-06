<?php
require_once  "../config.php";
require_once  "../core.php";
// require_once "../session_config.php";

function check_login()
{
    // if (session_status() !== PHP_SESSION_ACTIVE) {
    //     session_start();
    // }

    header('Content-Type: application/json; charset=utf-8');

    // verificar os campos que o login.php realmente define (user_id / username)
    $logged = !empty($_SESSION['user_id']) || !empty($_SESSION['username']);

    if (! $logged) {
        echo json_encode([
            'is_logged_in' => false,
            'username'     => null,
            'first_name'   => null,
            'last_name'    => null,
            'session_id'   => session_id()
        ]);
        exit;
    }

    echo json_encode([
        'is_logged_in' => true,
        'user_id'      => $_SESSION['user_id'] ?? '',
        'username'     => $_SESSION['username'] ?? '',
        'fname'        => $_SESSION['first_name'] ?? '',
        'last_name'    => $_SESSION['last_name'] ?? '',
        'role'         => $_SESSION['role'] ?? '',
        'session_id'   => session_id()
    ]);
    exit;
}
check_login();
