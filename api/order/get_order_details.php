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

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

$pdo = connectDB($db);

try {
    // Buscar dados da order
    $sqlOrder = "
        SELECT 
            o.id,
            o.user_id,
            o.status,
            o.total_price,
            o.shipping_address,
            o.tracking_code,
            o.created_at,
            u.username,
            u.fname,
            u.lname,
            u.email,
            u.phone
        FROM orders o
        INNER JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ";

    $stmt = $pdo->prepare($sqlOrder);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    // Buscar items da order com dados dos produtos
    $sqlItems = "
        SELECT 
            oi.id,
            oi.product_id,
            oi.quantity,
            oi.price,
            p.name AS product_name,
            p.image_url,
            p.category,
            (oi.quantity * oi.price) AS subtotal
        FROM order_items oi
        INNER JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ";

    $stmt = $pdo->prepare($sqlItems);
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Juntar tudo
    $order['items'] = $items;

    echo json_encode($order);
} catch (Throwable $e) {
    error_log("DB error in get_order_details: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
exit;
