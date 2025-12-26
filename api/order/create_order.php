<?php

require_once "../config.php";
require_once "../core.php";

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ===== 1. VALIDAR AUTENTICAÇÃO =====
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Utilizador não autenticado']);
    exit;
}

// ===== 2. VALIDAR E SANITIZAR DADOS DE ENTRADA =====
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

// Sanitizar e validar campos obrigatórios
$phone = isset($data['phone']) ? trim(filter_var($data['phone'], FILTER_SANITIZE_SPECIAL_CHARS)) : null;
$address = isset($data['address']) ? trim(filter_var($data['address'], FILTER_SANITIZE_SPECIAL_CHARS)) : null;
$postal_code = isset($data['postal_code']) ? trim(filter_var($data['postal_code'], FILTER_SANITIZE_SPECIAL_CHARS)) : null;
$city = isset($data['city']) ? trim(filter_var($data['city'], FILTER_SANITIZE_SPECIAL_CHARS)) : null;

// Campo opcional
$nif = isset($data['nif']) && !empty($data['nif']) ? trim(filter_var($data['nif'], FILTER_SANITIZE_SPECIAL_CHARS)) : null;

// Validar campos obrigatórios
if (!$phone || !$address || !$postal_code || !$city) {
    http_response_code(400);
    echo json_encode(['error' => 'Todos os campos obrigatórios devem ser preenchidos']);
    exit;
}

// Validar formato do NIF (se fornecido)
if ($nif && (!ctype_digit($nif) || strlen($nif) !== 9)) {
    http_response_code(400);
    echo json_encode(['error' => 'NIF inválido (deve ter 9 dígitos)']);
    exit;
}

// Validar formato do código postal
if (!preg_match('/^\d{4}-\d{3}$/', $postal_code)) {
    http_response_code(400);
    echo json_encode(['error' => 'Código postal inválido (formato: 1234-567)']);
    exit;
}

// Criar endereço de envio formatado
$shipping_address = $address . " " . $postal_code . " " . $city;

try {
    $pdo = connectDB($db);
    $pdo->beginTransaction();

    // ===== 3. BUSCAR CARRINHO DO UTILIZADOR =====
    $sql = "SELECT c.product_id, c.quantity, p.price, p.name, p.quantity as stock
            FROM cart c 
            INNER JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = :user_id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        http_response_code(400);
        echo json_encode(['error' => 'Carrinho vazio']);
        exit;
    }

    // ===== 4. VERIFICAR STOCK E CALCULAR TOTAL =====
    $total_price = 0;
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'error' => 'Stock insuficiente',
                'product' => $item['name'],
                'available' => $item['stock'],
                'requested' => $item['quantity']
            ]);
            exit;
        }
        $total_price += ($item['price'] * $item['quantity']);
    }

    // ===== 5. CRIAR ENCOMENDA =====
    $sql = "INSERT INTO orders (user_id, total_price, shipping_address, status, created_at) 
            VALUES (:user_id, :total_price, :shipping_address, 'pending', NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':total_price', $total_price);
    $stmt->bindParam(':shipping_address', $shipping_address, PDO::PARAM_STR);
    $stmt->execute();

    $order_id = $pdo->lastInsertId();

    // ===== 6. INSERIR ITENS DA ENCOMENDA E ATUALIZAR STOCK =====
    $sql_insert_item = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                       VALUES (:order_id, :product_id, :quantity, :price)";
    $stmt_item = $pdo->prepare($sql_insert_item);

    $sql_update_stock = "UPDATE products 
                        SET quantity = quantity - :qty 
                        WHERE id = :product_id";
    $stmt_stock = $pdo->prepare($sql_update_stock);

    foreach ($cart_items as $item) {
        $item_total = $item['price'] * $item['quantity'];

        // Inserir item da encomenda
        $stmt_item->execute([
            ':order_id' => $order_id,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':price' => $item_total
        ]);

        // Atualizar stock
        $stmt_stock->execute([
            ':qty' => $item['quantity'],
            ':product_id' => $item['product_id']
        ]);
    }

    // ===== 7. GUARDAR/ATUALIZAR DADOS PESSOAIS =====
    $sql_check = "SELECT cliente_id FROM dadospessoais WHERE cliente_id = :user_id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_check->execute();

    if ($stmt_check->rowCount() > 0) {
        // Atualizar
        $sql_update = "UPDATE dadospessoais 
                      SET phonenumber = :phone, 
                          nif = :nif, 
                          address = :address, 
                          postal_code = :postal_code, 
                          city = :city 
                      WHERE cliente_id = :user_id";
        $stmt_update = $pdo->prepare($sql_update);
    } else {
        // Inserir
        $sql_update = "INSERT INTO dadospessoais (cliente_id, phonenumber, nif, address, postal_code, city) 
                      VALUES (:user_id, :phone, :nif, :address, :postal_code, :city)";
        $stmt_update = $pdo->prepare($sql_update);
    }

    $stmt_update->execute([
        ':user_id' => $user_id,
        ':phone' => $phone,
        ':nif' => $nif,
        ':address' => $address,
        ':postal_code' => $postal_code,
        ':city' => $city
    ]);

    // ===== 8. LIMPAR CARRINHO =====
    $sql_clear = "DELETE FROM cart WHERE user_id = :user_id";
    $stmt_clear = $pdo->prepare($sql_clear);
    $stmt_clear->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_clear->execute();

    // ===== 9. CONFIRMAR TRANSAÇÃO =====
    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'total_price' => number_format($total_price, 2, '.', ''),
        'items_count' => count($cart_items),
        'message' => 'Encomenda criada com sucesso'
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Erro ao criar encomenda: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao processar encomenda',
        'message' => 'Ocorreu um erro no servidor. Tente novamente.',

    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Erro inesperado: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'Erro inesperado',
        'message' => 'Ocorreu um erro. Contacte o suporte.'
    ]);
}
