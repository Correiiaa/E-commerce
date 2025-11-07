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

$pdo = connectDB($db);
$sql = "SELECT p.name, p.image_url, c.quantity, c.price, p.id
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = :user_id";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'];
}

echo json_encode([
    'items' => $cart_items,
    'total' => $total
]);
