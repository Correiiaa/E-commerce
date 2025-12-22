<?php
require_once "../config.php";
require_once "../core.php";

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;

// Se user não está logado, retornar array vazio (não erro)
if (!$user_id) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = connectDB($db);

    $sql = "
        SELECT 
            p.id,
            p.name,
            p.price,
            p.image_url,
            p.discont
        FROM favorites f
        INNER JOIN products p ON f.product_id = p.id
        WHERE f.user_id = ?
          AND p.active = 1
        ORDER BY f.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($favorites);
} catch (Throwable $e) {
    error_log("DB error in get_favorites: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
