<?php

require_once '../config.php';
require_once '../core.php';

$pdo = connectDB($db);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// verificar se o utilizador está autenticado e é admin
if (!isset($_SESSION['user_id']) || !($_SESSION['role'] ?? false)) {
    http_response_code(403);
    die('Access denied');
}

// obter todos os utilizadores
$sql = "SELECT id, username, fname, lname, email, is_admin, created_at FROM users ORDER BY id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);


header('Content-Type: application/json; charset=utf-8');
echo json_encode($users);
exit;
