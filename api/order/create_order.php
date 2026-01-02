<?php
// filepath: \\arca.ua.pt\Hosting\esan-tesp-ds-paw.web.ua.pt\tesp-ds-g32\E-commerce\api\order\create_order.php

require_once "../config.php";
require_once "../core.php";
require_once "../mail/send_email.php";

header('Content-Type: application/json; charset=utf-8');

// ✅ DEBUG: Verificar sessão
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Utilizador não autenticado',
        'debug' => [
            'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE',
            'session_id' => session_id(),
            'session_data' => $_SESSION
        ]
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// ===== 2. VALIDAR E SANITIZAR DADOS DE ENTRADA =====
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// ✅ DEBUG: Log dos dados recebidos
error_log("Create Order - User ID: $user_id");
error_log("Create Order - Dados recebidos: " . print_r($data, true));

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

$phone = isset($data['phone']) ? trim(filter_var($data['phone'], FILTER_SANITIZE_SPECIAL_CHARS)) : null;
$address = isset($data['address']) ? trim(filter_var($data['address'], FILTER_SANITIZE_SPECIAL_CHARS)) : null;
$postal_code = isset($data['postal_code']) ? trim(filter_var($data['postal_code'], FILTER_SANITIZE_SPECIAL_CHARS)) : null;
$city = isset($data['city']) ? trim(filter_var($data['city'], FILTER_SANITIZE_SPECIAL_CHARS)) : null;
$nif = isset($data['nif']) && !empty($data['nif']) ? trim(filter_var($data['nif'], FILTER_SANITIZE_SPECIAL_CHARS)) : null;

if (!$phone || !$address || !$postal_code || !$city) {
    http_response_code(400);
    echo json_encode(['error' => 'Todos os campos obrigatórios devem ser preenchidos']);
    exit;
}

if ($nif && (!ctype_digit($nif) || strlen($nif) !== 9)) {
    http_response_code(400);
    echo json_encode(['error' => 'NIF inválido (deve ter 9 dígitos)']);
    exit;
}

if (!preg_match('/^\d{4}-\d{3}$/', $postal_code)) {
    http_response_code(400);
    echo json_encode(['error' => 'Código postal inválido (formato: 1234-567)']);
    exit;
}

$shipping_address = $address . " " . $postal_code . " " . $city;

try {
    $pdo = connectDB($db);
    error_log("Create Order - Conexão BD OK");

    $pdo->beginTransaction();
    error_log("Create Order - Transação iniciada");

    // ===== 3. BUSCAR DADOS DO UTILIZADOR =====
    $sql_user = "SELECT fname, lname, email FROM users WHERE id = :user_id";
    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_user->execute();
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

    error_log("Create Order - User data: " . print_r($user_data, true));

    if (!$user_data) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Utilizador não encontrado', 'user_id' => $user_id]);
        exit;
    }

    // ===== 4. BUSCAR CARRINHO DO UTILIZADOR =====
    $sql = "SELECT c.product_id, c.quantity, p.price, p.name, p.image_url, p.quantity as stock
            FROM cart c 
            INNER JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = :user_id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Create Order - Cart items: " . count($cart_items));

    if (empty($cart_items)) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Carrinho vazio']);
        exit;
    }

    // ===== 5. VERIFICAR STOCK E CALCULAR TOTAL =====
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

    error_log("Create Order - Total: $total_price");

    // ===== 6. CRIAR ENCOMENDA =====
    $sql = "INSERT INTO orders (user_id, total_price, shipping_address, status, created_at) 
            VALUES (:user_id, :total_price, :shipping_address, 'pending', NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':total_price', $total_price);
    $stmt->bindParam(':shipping_address', $shipping_address, PDO::PARAM_STR);
    $stmt->execute();

    $order_id = $pdo->lastInsertId();
    error_log("Create Order - Order ID: $order_id");

    // ===== 7. INSERIR ITENS DA ENCOMENDA E ATUALIZAR STOCK =====
    $sql_insert_item = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                       VALUES (:order_id, :product_id, :quantity, :price)";
    $stmt_item = $pdo->prepare($sql_insert_item);

    $sql_update_stock = "UPDATE products 
                        SET quantity = quantity - :qty 
                        WHERE id = :product_id";
    $stmt_stock = $pdo->prepare($sql_update_stock);

    foreach ($cart_items as $item) {
        $item_total = $item['price'] * $item['quantity'];

        $stmt_item->execute([
            ':order_id' => $order_id,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':price' => $item_total
        ]);

        $stmt_stock->execute([
            ':qty' => $item['quantity'],
            ':product_id' => $item['product_id']
        ]);
    }

    error_log("Create Order - Items inseridos");

    // ===== 8. GUARDAR/ATUALIZAR DADOS PESSOAIS =====
    $sql_check = "SELECT cliente_id FROM dadospessoais WHERE cliente_id = :user_id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_check->execute();

    if ($stmt_check->rowCount() > 0) {
        $sql_update = "UPDATE dadospessoais 
                      SET phonenumber = :phone, 
                          nif = :nif, 
                          address = :address, 
                          postal_code = :postal_code, 
                          city = :city 
                      WHERE cliente_id = :user_id";
        $stmt_update = $pdo->prepare($sql_update);
    } else {
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

    error_log("Create Order - Dados pessoais guardados");

    // ===== 9. LIMPAR CARRINHO =====
    $sql_clear = "DELETE FROM cart WHERE user_id = :user_id";
    $stmt_clear = $pdo->prepare($sql_clear);
    $stmt_clear->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_clear->execute();

    error_log("Create Order - Carrinho limpo");

    // ===== 10. CONFIRMAR TRANSAÇÃO =====
    $pdo->commit();
    error_log("Create Order - Transação confirmada");

    // ===== 11. ENVIAR EMAIL DE CONFIRMAÇÃO =====
    try {
        $customer_name = $user_data['fname'] . ' ' . $user_data['lname'];

        error_log("Create Order - Gerando email para: {$user_data['email']}");

        $email_html = generateOrderConfirmationEmail(
            $customer_name,
            $order_id,
            $cart_items,
            $total_price,
            $shipping_address,
            $phone,
            $nif
        );

        error_log("Create Order - Email HTML gerado");

        $email_result = sendEmail(
            $user_data['email'],
            $customer_name,
            "Confirmação de Encomenda #$order_id - E-Commerce Vinhos",
            $email_html
        );

        error_log("Create Order - Email result: " . print_r($email_result, true));

        if (!$email_result['success']) {
            error_log("Erro ao enviar email para {$user_data['email']}: " . ($email_result['error'] ?? 'Desconhecido'));
        }
    } catch (Exception $e) {
        error_log("Exceção ao enviar email de confirmação: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }

    // ===== 12. RESPOSTA DE SUCESSO =====
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'total_price' => number_format($total_price, 2, '.', ''),
        'items_count' => count($cart_items),
        'message' => 'Encomenda criada com sucesso',
        'email_sent' => isset($email_result) ? $email_result['success'] : false
    ]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $error_msg = "Erro BD ao criar encomenda: " . $e->getMessage();
    error_log($error_msg);
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao processar encomenda',
        'message' => 'Ocorreu um erro no servidor. Tente novamente.',
        'debug' => DEBUG ? [
            'sql_error' => $e->getMessage(),
            'sql_code' => $e->getCode()
        ] : null
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $error_msg = "Erro inesperado: " . $e->getMessage();
    error_log($error_msg);
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'error' => 'Erro inesperado',
        'message' => 'Ocorreu um erro. Contacte o suporte.',
        'debug' => DEBUG ? [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ] : null
    ]);
}
// ===== FUNÇÃO PARA GERAR HTML DO EMAIL (FORA DOS BLOCOS TRY/CATCH) =====
function generateOrderConfirmationEmail($customer_name, $order_id, $items, $total, $address, $phone, $nif)
{
    $items_html = '';
    $base_url = 'https://esan-tesp-ds-paw.web.ua.pt/tesp-ds-g32/E-commerce/uploads';

    foreach ($items as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $image_url = $base_url . '/' . htmlspecialchars($item['image_url']);
        $item_name = htmlspecialchars($item['name']);

        $items_html .= "
        <tr>
            <td style='padding: 15px; border-bottom: 1px solid #eee;'>
                <img src='{$image_url}' alt='{$item_name}' 
                     style='width: 80px; height: 80px; object-fit: cover; border-radius: 8px;' />
            </td>
            <td style='padding: 15px; border-bottom: 1px solid #eee;'>
                <strong>{$item_name}</strong>
            </td>
            <td style='padding: 15px; border-bottom: 1px solid #eee; text-align: center;'>
                {$item['quantity']}
            </td>
            <td style='padding: 15px; border-bottom: 1px solid #eee; text-align: right;'>
                " . number_format($item['price'], 2, ',', '.') . " €
            </td>
            <td style='padding: 15px; border-bottom: 1px solid #eee; text-align: right;'>
                <strong>" . number_format($item_total, 2, ',', '.') . " €</strong>
            </td>
        </tr>";
    }

    $html = "
    <!DOCTYPE html>
    <html lang='pt'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Confirmação de Encomenda</title>
    </head>
    <body style='font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4;'>
        <div style='max-width: 650px; margin: 30px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);'>
            
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #8B0000, #A52A2A); color: white; padding: 40px 30px; text-align: center;'>
                <h1 style='margin: 0; font-size: 28px; font-weight: 600;'>Toni Correia</h1>
                <p style='margin: 10px 0 0 0; font-size: 16px; opacity: 0.95;'>Confirmação de Encomenda</p>
            </div>

            <!-- Conteúdo -->
            <div style='padding: 40px 30px;'>
                <h2 style='color: #8B0000; margin: 0 0 20px 0; font-size: 24px;'>
                    Olá, {$customer_name}! 👋
                </h2>
                
                <p style='color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 25px 0;'>
                    A sua encomenda foi <strong>confirmada com sucesso</strong>!<br>
                    Obrigado por confiar em nós. 🙏
                </p>

                <!-- Info Box -->
                <div style='background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #8B0000; margin-bottom: 30px;'>
                    <h3 style='margin: 0 0 15px 0; color: #8B0000; font-size: 18px;'>
                        📦 Detalhes da Encomenda
                    </h3>
                    <p style='margin: 5px 0; color: #555;'><strong>Número:</strong> #{$order_id}</p>
                    <p style='margin: 5px 0; color: #555;'><strong>Data:</strong> " . date('d/m/Y H:i') . "</p>
                    <p style='margin: 5px 0; color: #555;'><strong>Estado:</strong> <span style='color: #ff9800; font-weight: 600;'>Pendente</span></p>
                </div>

                <!-- Produtos -->
                <h3 style='color: #333; margin: 30px 0 15px 0; font-size: 20px;'>
                    🛒 Produtos
                </h3>
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 30px;'>
                    <thead>
                        <tr style='background: #f5f5f5;'>
                            <th style='padding: 12px; text-align: left; font-size: 14px; color: #666;'>Imagem</th>
                            <th style='padding: 12px; text-align: left; font-size: 14px; color: #666;'>Produto</th>
                            <th style='padding: 12px; text-align: center; font-size: 14px; color: #666;'>Qtd</th>
                            <th style='padding: 12px; text-align: right; font-size: 14px; color: #666;'>Preço</th>
                            <th style='padding: 12px; text-align: right; font-size: 14px; color: #666;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$items_html}
                        <tr>
                            <td colspan='4' style='padding: 20px 15px 15px; text-align: right; font-size: 18px; font-weight: 600;'>
                                TOTAL
                            </td>
                            <td style='padding: 20px 15px 15px; text-align: right; font-size: 20px; font-weight: 700; color: #8B0000;'>
                                " . number_format($total, 2, ',', '.') . " €
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Endereço de Envio -->
                <h3 style='color: #333; margin: 30px 0 15px 0; font-size: 20px;'>
                    📍 Endereço de Envio
                </h3>
                <div style='background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <p style='margin: 5px 0; color: #555; line-height: 1.6;'>{$address}</p>
                    <p style='margin: 10px 0 5px 0; color: #555;'><strong>Telefone:</strong> {$phone}</p>
                    " . ($nif ? "<p style='margin: 5px 0; color: #555;'><strong>NIF:</strong> {$nif}</p>" : "") . "
                </div>

                <!-- Próximos Passos -->
                <div style='background: #e8f5e9; padding: 20px; border-radius: 8px; border-left: 4px solid #4caf50; margin: 30px 0;'>
                    <h3 style='margin: 0 0 10px 0; color: #2e7d32; font-size: 18px;'>
                        ✅ Próximos Passos
                    </h3>
                    <ul style='margin: 0; padding-left: 20px; color: #555; line-height: 1.8;'>
                        <li>Receberá um email quando a encomenda for processada</li>
                        <li>Tempo estimado de entrega: 3-5 dias úteis</li>
                        <li>Pode acompanhar o estado na sua área de cliente</li>
                    </ul>
                </div>

                <p style='color: #666; font-size: 14px; line-height: 1.6; margin: 30px 0 0 0;'>
                    Se tiver alguma dúvida, não hesite em contactar-nos.<br>
                    <strong>Muito obrigado pela sua preferência!</strong> 🍷
                </p>
            </div>

            <!-- Footer -->
            <div style='background: #333; color: #999; text-align: center; padding: 25px 30px; font-size: 13px;'>
                <p style='margin: 5px 0;'><strong style='color: #ccc;'>E-Commerce Vinhos</strong></p>
                <p style='margin: 5px 0;'>TESP Desenvolvimento de Software</p>
                <p style='margin: 5px 0;'>Universidade de Aveiro</p>
                <p style='margin: 15px 0 5px 0; color: #666;'>
                    Este é um email automático, por favor não responda.
                </p>
            </div>
        </div>
    </body>
    </html>";

    return $html;
}
