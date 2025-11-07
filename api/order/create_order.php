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

$shipping_address = $data['shipping_address'] ?? null;

$sql = "SELECT * FROM cart WHERE user_id = :user_id";
$pdo = connectDB($db);
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    http_response_code(400);
    die('Cart is empty');
}

$total_price = 0;
foreach ($cart_items as $item) {
    $total_price += $item['price'];
}

$sql = "INSERT INTO orders (user_id, total_price, shipping_address, status) 
        VALUES (:user_id, :total_price, :shipping_address, 'pending')";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':total_price', $total_price, PDO::PARAM_STR);
$stmt->bindParam(':shipping_address', $shipping_address, PDO::PARAM_STR);
$stmt->execute();

$order_id = $pdo->lastInsertId();

$sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
        VALUES (:order_id, :product_id, :quantity, :price)";
$stmt = $pdo->prepare($sql);
foreach ($cart_items as $item) {
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
    $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
    $stmt->bindParam(':price', $item['price'], PDO::PARAM_STR);
    $stmt->execute();
}

echo json_encode(
    [
        'order_id' => $order_id,
        'message' => 'Order created successfully',
        'cart_items' => $cart_items
    ]
);
