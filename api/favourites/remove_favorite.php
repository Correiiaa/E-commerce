<?php
require "../config.php";
require "../core.php";

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

$product_id = filter_var($data['product_id'] ?? null, FILTER_VALIDATE_INT);

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product_id']);
    exit;
}

$pdo = connectDB($db);

try {
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Produto removido dos favoritos']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Produto não estava nos favoritos']);
    }
} catch (Throwable $e) {
    error_log("DB error in remove_favorite: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
