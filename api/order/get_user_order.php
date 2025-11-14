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
$sql= "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['orders' => $orders]);