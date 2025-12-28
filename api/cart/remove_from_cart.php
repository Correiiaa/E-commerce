<?php

require_once "../config.php";
require_once "../core.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    die('User not logged in');
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Erro ao decodificar JSON']);
    exit;
}

$product_id = $data['product_id'] ?? null;
$quantity = $data['quantity'] ?? null;

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID obrigatorio']);
    exit;
}

$pdo = connectDB($db);

// Se quantity foi especificada, remover apenas essa quantidade
if ($quantity !== null && $quantity > 0) {
    // Buscar quantidade atual no carrinho
    $sql = "SELECT quantity, price FROM cart WHERE user_id = :user_id AND product_id = :product_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart_item) {
        http_response_code(404);
        echo json_encode(['error' => 'Produto nao encontrado no carrinho']);
        exit;
    }

    $current_quantity = $cart_item['quantity'];
    $new_quantity = $current_quantity - $quantity;

    // Se nova quantidade for 0 ou menos, remover completamente
    if ($new_quantity <= 0) {
        $sql = "DELETE FROM cart WHERE product_id = :product_id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        // Atualizar quantidade e preco
        $sql = "SELECT price FROM products WHERE id = :product_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        $unit_price = $product['price'];

        $new_total_price = $unit_price * $new_quantity;

        $sql = "UPDATE cart SET quantity = :quantity, price = :price WHERE user_id = :user_id AND product_id = :product_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
        $stmt->bindParam(':price', $new_total_price, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
    }
} else {
    // Se quantity nao foi especificada, remover produto completamente
    $sql = "DELETE FROM cart WHERE product_id = :product_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
}

// Retornar carrinho atualizado
$sql = "SELECT p.name, p.image_url, c.quantity, c.price, p.id FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'];
}

echo json_encode([
    'success' => true,
    'cart_items' => $cart_items,
    'total' => $total
]);
