<?php

require_once  "../config.php";
require_once  "../core.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !($_SESSION['role'] ?? false)) {
    http_response_code(403);
    die('Access denied');
}

try {
    $pdo = connectDB($db);

    // obter dados da requisição
    $data = json_decode(file_get_contents('php://input'), true);
    $new_category_name = filter_var(trim($data['category_name'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
    $category_id = filter_var(isset($data['category_id']) ? intval($data['category_id']) : null, FILTER_VALIDATE_INT);


    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }

    // validar dados
    if ($new_category_name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Category name is required']);
        exit;
    }

    if (!$category_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid category_id is required']);
        exit;
    }

    // atualizar categoria
    $sql = "UPDATE categorias SET nome = :name WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':name' => $new_category_name, ':id' => $category_id]);
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
    exit;
    
} catch (Throwable $e) {
    error_log("DB connection error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
    exit;
}
