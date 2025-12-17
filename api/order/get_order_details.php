<?php
require_once '../config.php';
require_once '../core.php';

header('Content-Type: application/json; charset=utf-8');


if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Verificar se é admin
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    die('User not logged in');
}


$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

error_log("=== get_order_details.php ===");
error_log("Order ID: " . $order_id);

try {
    $pdo = connectDB($db);

    // ✅ LEFT JOIN com dadospessoais para buscar phone/nif
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
            dp.phonenumber AS phone,
            dp.nif
        FROM orders o
        INNER JOIN users u ON o.user_id = u.id
        LEFT JOIN dadospessoais dp ON u.id = dp.cliente_id
        WHERE o.id = ?
    ";

    error_log("SQL Order: " . $sqlOrder);

    $stmt = $pdo->prepare($sqlOrder);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        error_log("❌ Order not found: " . $order_id);
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    error_log("✅ Order found: " . $order['id']);

    // ✅ Buscar items da order
    $sqlItems = "
        SELECT 
            oi.id,
            oi.product_id,
            oi.quantity,
            oi.price,
            p.name AS product_name,
            p.image_url,
            c.nome AS category,
            (oi.quantity * oi.price) AS subtotal
        FROM order_items oi
        INNER JOIN products p ON oi.product_id = p.id
        LEFT JOIN categorias c ON p.category_id = c.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ";

    error_log("SQL Items: " . $sqlItems);

    $stmt = $pdo->prepare($sqlItems);
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("✅ Items found: " . count($items));

    // ✅ Juntar tudo
    $order['items'] = $items;

    http_response_code(200);
    echo json_encode($order, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log("❌ PDO Error in get_order_details: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
} catch (Throwable $e) {
    error_log("❌ General Error in get_order_details: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ]);
}
exit;
