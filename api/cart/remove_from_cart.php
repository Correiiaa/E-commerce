<?php

require_once  "../config.php";
require_once  "../core.php";
require_once "../user/check_login.php";


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
    // Ocorreu um erro ao decodificar o JSON
    echo json_encode(['error' => 'Erro ao decodificar JSON']);
} else {
    $product_id = $data['product_id'] ?? null;

    $pdo = connectDB($db);
    $sql = "DELETE FROM cart WHERE product_id = :product_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
}
