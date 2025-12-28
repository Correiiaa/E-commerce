<?php
require_once "../config.php";
require_once "../core.php";

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user_id']) || !($_SESSION['role'] ?? false)) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$product_id = isset($data['product_id']) ? intval($data['product_id']) : null;

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do produto nao fornecido']);
    exit;
}

try {
    $pdo = connectDB($db);

    // Buscar estado atual
    $sql = "SELECT active FROM products WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Produto nao encontrado']);
        exit;
    }

    // Alternar estado (se 1 vira 0, se 0 vira 1)
    $new_status = $product['active'] == 1 ? 0 : 1;

    $update_sql = "UPDATE products SET active = :active WHERE id = :id";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([
        ':active' => $new_status,
        ':id' => $product_id
    ]);

    $status_text = $new_status == 1 ? 'ativado' : 'desativado';

    error_log("Produto $product_id $status_text");

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Produto $status_text com sucesso",
        'new_status' => $new_status
    ]);
} catch (PDOException $e) {
    error_log("Erro ao alterar estado do produto: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao alterar estado do produto'
    ]);
}
