<?php

require_once '../config.php';

require_once '../core.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// verificar se o utilizador está autenticado e é admin
if (!isset($_SESSION['user_id']) || !($_SESSION['role'] ?? false)) {
    http_response_code(403);
    die('Access denied');
}

$pdo = connectDB($db);

try {
    $sql = "SELECT p.*, c.nome AS category
            FROM products p
            LEFT JOIN categorias c ON p.category_id = c.id";
    error_log("SQL: $sql");

    $stm = $pdo->query($sql);
    $products = $stm->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode(['products' => $products, 'count' => count($products)], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("DB error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Internal Server Error']);
}
exit;
