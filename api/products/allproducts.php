<?php

require_once '../config.php';

require_once '../core.php';


error_log("Utilizador: " . ($db['username'] ?? 'unknown'));

$pdo = connectDB($db);

try {
    $sql = "SELECT * FROM products";
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
