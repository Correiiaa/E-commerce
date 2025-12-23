<?php
require_once '../config.php';
require_once '../core.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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
    // JOIN com users + contar items
    $sql = "
        SELECT 
            o.id,
            o.user_id,
            o.status,
            o.total_price,
            o.shipping_address,
            o.created_at,
            u.username,
            u.fname,
            u.lname,
            u.email,
            COUNT(oi.id) AS items_count
        FROM orders o
        INNER JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ";

    $stmt = $pdo->query($sql);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($orders);
} catch (Throwable $e) {
    error_log("DB error in get_all_orders: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
exit;
