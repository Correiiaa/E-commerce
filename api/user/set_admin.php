<?php

require_once "../config.php";
require_once "../core.php";
header('Content-Type: application/json; charset=utf-8');

$pdo = connectDB($db);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// verificar se o utilizador está autenticado e é admin
if (!isset($_SESSION['user_id']) || !($_SESSION['role'] ?? false)) {
    http_response_code(403);
    die('Access denied');
}

// obter o ID do utilizador a atualizar
$data = json_decode(file_get_contents('php://input'), true);
$user_id = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT);

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

// verificar se o  utilizador é admin
$is_admin = filter_var($data['is_admin'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

if ($is_admin === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid is_admin value']);
    exit;
}

try {
    $check_user = "SELECT id FROM users WHERE id = :id";
    $stmt = $pdo->prepare($check_user);
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // atualizar o status de admin
    $sql = "UPDATE users SET is_admin = :is_admin WHERE id= :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':is_admin' => $is_admin ? 1 : 0,
        ':id' => $user_id
    ]);
    http_response_code(200);
    echo json_encode(['success' => true, 'new_status' => $is_admin]);
    exit;
} catch (Throwable $e) {

    error_log("DB error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
    exit;
}
