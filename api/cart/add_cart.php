<?php

require_once  "../config.php";
require_once  "../core.php";


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
$product_id = $data['product_id'] ?? null;
$quantity = $data['quantity'] ?? 1;


$pdo = connectDB($db);
$sql = "SELECT * FROM products WHERE id = :product_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->rowCount();
$product = $stmt->fetch(PDO::FETCH_ASSOC);
$price = $product['price'] ?? 0;
$stock = $product['quantity'] ?? 0;

if ($row == 0) {
    http_response_code(400);
    die('Invalid product');
}

if ($quantity > $stock) {
    http_response_code(400);
    die('Insufficient stock');
}

$sql = "SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
$stmt->execute();
$existing_cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_cart_item) {
    $new_quantity = $existing_cart_item['quantity'] + $quantity;
    $new_price = $existing_cart_item['price'] + $price;
    $sql = "UPDATE cart SET quantity = :quantity, price = :price WHERE user_id = :user_id AND product_id = :product_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
    $stmt->bindParam(':price', $new_price, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $sql = "INSERT INTO cart (product_id, user_id, quantity, price) VALUES (:product_id, :user_id, :quantity, :price)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
    $stmt->bindParam(':price', $price, PDO::PARAM_STR);
    $stmt->execute();
}

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
    'cart_items' => $cart_items,
    'total' => $total
]);



// if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
//     // Ocorreu um erro ao decodificar o JSON
//     echo json_encode(['error' => 'Erro ao decodificar JSON']);
// } else {
//     $product_id = $data['product_id'] ?? null;
//     $quantity = $data['quantity'] ?? 1;
//     $pdo = connectDB($db);
//     $sql = "INSERT INTO cart (product_id, user_id, quantity) VALUES (:product_id, :user_id, :quantity)
//             ON DUPLICATE KEY UPDATE quantity = quantity + :quantity";
//     $stmt = $pdo->prepare($sql);
//     $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
//     $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
//     $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
//     $stmt->execute();
// }
