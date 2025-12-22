<?php

require_once  "../config.php";
require_once  "../core.php";

error_log("Utilizador: " . ($db['username'] ?? 'unknown'));

$pdo = connectDB($db);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ler e validar input
$username = trim((string) (filter_input(INPUT_POST, 'user', FILTER_SANITIZE_SPECIAL_CHARS) ?? ''));
$password = (string) (filter_input(INPUT_POST, 'pwd', FILTER_UNSAFE_RAW) ?? '');

if ($username === '' || $password === '') {
    http_response_code(400);
    die('Invalid credentials');
}

// procurar o utilizador (somente campos necessários)
$sql = "SELECT id, username, password, is_admin, fname, lname FROM users WHERE username = :username LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':username', $username, PDO::PARAM_STR);
$stmt->execute();

$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || !password_verify($password, $row['password'])) {
    http_response_code(401);
    die('Username or password incorrect');
}

// autenticar
session_regenerate_id(true);
$_SESSION['user_id'] = $row['id'];
$_SESSION['username'] = $row['username'];
$_SESSION['role'] = $row['is_admin'];
$_SESSION['first_name'] = $row['fname'] ?? '';
$_SESSION['last_name'] = $row['lname'] ?? '';

// resposta JSON com dados do utilizador e URL de redireção
$response = [
    'username'   => $row['username'],
    'first_name' => $row['fname'] ?? '',
    'last_name'  => $row['lname'] ?? '',
    'role'   => (bool) $row['is_admin'],
    'redirect'   => '/index.html'
];

header('Content-Type: application/json; charset=utf-8');

echo json_encode($response);
exit;
