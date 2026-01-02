<?php

require_once "../config.php";
require_once "../core.php";
// require_once "../session_config.php";

header('Content-Type: application/json; charset=utf-8');

// if (session_status() !== PHP_SESSION_ACTIVE) {
//     session_start();
// }

//  VERIFICAR AUTENTICAÇÃO E PERMISSÕES =====
if (!isset($_SESSION['user_id']) || !($_SESSION['role'] ?? false)) {
    http_response_code(403);
    die('Access denied');
}

// Obter dados JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

// Validar campos obrigatórios
$id = isset($data['id']) ? intval($data['id']) : null;
$name = isset($data['name']) ? trim($data['name']) : null;
$category_id = isset($data['category_id']) ? intval($data['category_id']) : null;
$price = isset($data['price']) ? floatval($data['price']) : null;
$quantity = isset($data['quantity']) ? intval($data['quantity']) : null;

if (!$id || !$name || !$category_id || !$price || $quantity === null) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Campos obrigatórios em falta',
        'required' => ['id', 'name', 'category_id', 'price', 'quantity']
    ]);
    exit;
}

// Validações
if (strlen($name) < 3) {
    http_response_code(400);
    echo json_encode(['error' => 'Nome deve ter pelo menos 3 caracteres']);
    exit;
}

if ($price <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Preco deve ser maior que 0']);
    exit;
}

if ($quantity < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Quantidade nao pode ser negativa']);
    exit;
}

// Campos opcionais
$description = isset($data['description']) ? trim($data['description']) : null;
$discount = isset($data['discount']) ? floatval($data['discount']) : 0;
$producer = isset($data['producer']) ? trim($data['producer']) : null;
$region = isset($data['region']) ? trim($data['region']) : null;
$country = isset($data['country']) ? trim($data['country']) : null;
$year = isset($data['year']) ? intval($data['year']) : null;
$alcohol = isset($data['alcohol']) ? floatval($data['alcohol']) : null;

try {
    $pdo = connectDB($db);

    // Verificar se produto existe
    $check_sql = "SELECT id FROM products WHERE id = :id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':id' => $id]);

    if (!$check_stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Produto nao encontrado']);
        exit;
    }

    // Atualizar produto (usar os nomes corretos das colunas da BD)
    $sql = "UPDATE products SET 
            name = :name,
            category_id = :category_id,
            price = :price,
            quantity = :quantity,
            description = :description,
            discont = :discont,
            produtor = :produtor,
            regiao = :regiao,
            pais = :pais,
            ano = :ano,
            volumeal = :volumeal
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $id,
        ':name' => $name,
        ':category_id' => $category_id,
        ':price' => $price,
        ':quantity' => $quantity,
        ':description' => $description,
        ':discont' => $discount,
        ':produtor' => $producer,
        ':regiao' => $region,
        ':pais' => $country,
        ':ano' => $year,
        ':volumeal' => $alcohol
    ]);

    error_log("Produto atualizado: ID $id - $name");

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Produto atualizado com sucesso',
        'product_id' => $id
    ]);
} catch (PDOException $e) {
    error_log("Erro ao atualizar produto: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao atualizar produto',
        'message' => 'Ocorreu um erro ao guardar as alteracoes'
    ]);
}
