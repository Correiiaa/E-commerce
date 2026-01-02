<?php
require "../config.php";
require "../core.php";
// require_once "../session_config.php";

header('Content-Type: application/json; charset=utf-8');

// if (session_status() !== PHP_SESSION_ACTIVE) {
//     session_start();
// }

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
    // Verificar se produto existe
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND active = 1");
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }
    
    // Inserir favorito (IGNORAR se já existe)
    $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $product_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Produto adicionado aos favoritos']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Produto já está nos favoritos']);
    }
    
} catch (Throwable $e) {
    error_log("DB error in add_favorite: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}