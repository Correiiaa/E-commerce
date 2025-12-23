<?php

require_once "../config.php";
require_once "../core.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// ✅ CORRIGIDO: Decodificar JSON corretamente
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data || !isset($data['shipping_address'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

// ✅ Sanitizar apenas a string, não o array todo
$shipping_address = filter_var($data['shipping_address'], FILTER_SANITIZE_SPECIAL_CHARS);

try {
    $pdo = connectDB($db);

    // Buscar itens do carrinho
    $sql = "SELECT c.*, p.price as unit_price, p.name as product_name 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        http_response_code(400);
        echo json_encode(['error' => 'Cart is empty']);
        exit;
    }

    // ✅ Calcular total corretamente (preço * quantidade)
    $total_price = 0;
    foreach ($cart_items as $item) {
        $total_price += ($item['unit_price'] * $item['quantity']);
    }

    // Iniciar transação
    $pdo->beginTransaction();

    // Criar encomenda
    $sql = "INSERT INTO orders (user_id, total_price, shipping_address, status, created_at) 
            VALUES (:user_id, :total_price, :shipping_address, 'pending', NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':total_price', $total_price);
    $stmt->bindParam(':shipping_address', $shipping_address, PDO::PARAM_STR);
    $stmt->execute();

    $order_id = $pdo->lastInsertId();

    // Inserir itens da encomenda
    $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
            VALUES (:order_id, :product_id, :quantity, :price)";
    $stmt = $pdo->prepare($sql);

    foreach ($cart_items as $item) {
        $item_total = $item['unit_price'] * $item['quantity'];

        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
        $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
        $stmt->bindParam(':price', $item_total);
        $stmt->execute();

        // ✅ Opcional: Atualizar stock do produto
        $update_stock = "UPDATE products 
                        SET quantity = quantity - :qty 
                        WHERE id = :product_id AND quantity >= :qty";
        $update_stmt = $pdo->prepare($update_stock);
        $update_stmt->bindParam(':qty', $item['quantity'], PDO::PARAM_INT);
        $update_stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
        $update_stmt->execute();
    }

    // ✅ Limpar carrinho após criar encomenda
    $sql = "DELETE FROM cart WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Confirmar transação
    $pdo->commit();

    // Resposta de sucesso
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'total_price' => $total_price,
        'message' => 'Encomenda criada com sucesso',
        'items_count' => count($cart_items)
    ]);
} catch (Exception $e) {
    // Reverter transação em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to create order',
        'message' => $e->getMessage()
    ]);
}
