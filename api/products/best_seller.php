<?php

require_once '../config.php';

require_once '../core.php';


error_log("Utilizador: " . ($db['username'] ?? 'unknown'));

$pdo = connectDB($db);

try {
    $sql = "SELECT  products.*, SUM(order_items.quantity) AS total_vendido FROM order_items JOIN products ON order_items.product_id = products.id GROUP BY order_items.product_id ORDER BY total_vendido DESC LIMIT 6;";
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
