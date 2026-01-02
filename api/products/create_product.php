<?php

require_once "../config.php";
require_once "../core.php";
// require_once "../session_config.php";

header('Content-Type: application/json; charset=utf-8');

// if (session_status() !== PHP_SESSION_ACTIVE) {
//     session_start();
// }

// ===== 1. VERIFICAR AUTENTICAÇÃO E PERMISSÕES =====
if (!isset($_SESSION['user_id']) || !($_SESSION['role'] ?? false)) {
    http_response_code(403);
    die('Access denied');
}

// ===== 2. VALIDAR E SANITIZAR DADOS RECEBIDOS =====
$name = isset($_POST['name']) ? trim($_POST['name']) : null;
$category_id = isset($_POST['category_id']) ? trim($_POST['category_id']) : null;
$price = isset($_POST['price']) ? trim($_POST['price']) : null;
$quantity = isset($_POST['quantity']) ? trim($_POST['quantity']) : null;
$description = isset($_POST['description']) ? trim($_POST['description']) : null;
$year = isset($_POST['year']) ? trim($_POST['year']) : null;
$alcohol = isset($_POST['alcohol']) ? trim($_POST['alcohol']) : null;

// Campos opcionais
$discount = isset($_POST['discount']) ? trim($_POST['discount']) : 0;
$producer = isset($_POST['producer']) ? trim($_POST['producer']) : null;
$region = isset($_POST['region']) ? trim($_POST['region']) : null;
$country = isset($_POST['country']) ? trim($_POST['country']) : 'Portugal';

// ===== 3. VALIDAR CAMPOS OBRIGATÓRIOS =====
if (!$name || !$category_id || !$price || !$quantity || !$year || !$alcohol) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Campos obrigatórios em falta',
        'required' => ['name', 'category_id', 'price', 'quantity', 'image', 'year', 'alcohol']
    ]);
    exit;
}

// Validar nome (mínimo 3 caracteres)
if (strlen($name) < 3) {
    http_response_code(400);
    echo json_encode(['error' => 'Nome do produto deve ter pelo menos 3 caracteres']);
    exit;
}

// ===== 4. VALIDAR CATEGORY_ID CONTRA TABELA CATEGORIAS =====
if (!ctype_digit($category_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de categoria inválido']);
    exit;
}

$category_id = intval($category_id);

try {
    $pdo = connectDB($db);

    // Verificar se categoria existe
    $sql_check_category = "SELECT id, nome FROM categorias WHERE id = :category_id";
    $stmt_check = $pdo->prepare($sql_check_category);
    $stmt_check->execute([':category_id' => $category_id]);
    $category = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Categoria não encontrada',
            'message' => 'A categoria selecionada não existe'
        ]);
        exit;
    }

    error_log("Categoria válida: {$category['nome']} (ID: $category_id)");
} catch (PDOException $e) {
    error_log("Erro ao validar categoria: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao validar categoria']);
    exit;
}

// ===== 5. VALIDAR PREÇO =====
if (!is_numeric($price) || floatval($price) <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Preço deve ser um número maior que 0']);
    exit;
}
$price = floatval($price);

// ===== 6. VALIDAR QUANTIDADE =====
if (!ctype_digit($quantity) || intval($quantity) < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Quantidade deve ser um número inteiro não negativo']);
    exit;
}
$quantity = intval($quantity);

// ===== 7. VALIDAR DESCONTO =====
if (!empty($discount)) {
    if (!is_numeric($discount) || floatval($discount) < 0 || floatval($discount) > 100) {
        http_response_code(400);
        echo json_encode(['error' => 'Desconto deve estar entre 0 e 100']);
        exit;
    }
    $discount = floatval($discount);
} else {
    $discount = 0;
}

// ===== 8. VALIDAR ANO =====
if (!empty($year)) {
    $current_year = date('Y');
    if (!ctype_digit($year) || intval($year) < 1900 || intval($year) > ($current_year + 1)) {
        http_response_code(400);
        echo json_encode(['error' => "Ano deve estar entre 1900 e " . ($current_year + 1)]);
        exit;
    }
    $year = intval($year);
} else {
    $year = null;
}

// ===== 9. VALIDAR TEOR ALCOÓLICO =====
if (!empty($alcohol)) {
    if (!is_numeric($alcohol) || floatval($alcohol) < 0 || floatval($alcohol) > 100) {
        http_response_code(400);
        echo json_encode(['error' => 'Teor alcoólico deve estar entre 0 e 100']);
        exit;
    }
    $alcohol = floatval($alcohol);
} else {
    $alcohol = null;
}

// ===== 10. PROCESSAR UPLOAD DE IMAGEM (OBRIGATÓRIO) =====
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE => 'Ficheiro excede o tamanho máximo do servidor',
        UPLOAD_ERR_FORM_SIZE => 'Ficheiro excede o tamanho máximo do formulário',
        UPLOAD_ERR_PARTIAL => 'Upload incompleto',
        UPLOAD_ERR_NO_FILE => 'Nenhuma imagem enviada. A imagem é obrigatória.',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada',
        UPLOAD_ERR_CANT_WRITE => 'Erro ao escrever ficheiro',
        UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
    ];

    $error_code = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    http_response_code(400);
    echo json_encode([
        'error' => 'Erro no upload da imagem',
        'message' => $upload_errors[$error_code] ?? 'Erro desconhecido'
    ]);
    exit;
}

$file = $_FILES['image'];

// Validar tipo MIME real do ficheiro
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$file_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];

if (!in_array($file_type, $allowed_types)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Tipo de ficheiro inválido',
        'message' => 'Apenas imagens JPG e PNG são permitidas',
        'received' => $file_type
    ]);
    exit;
}

// Validar extensão do ficheiro
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Extensão de ficheiro inválida',
        'message' => 'Apenas extensões .jpg, .jpeg e .png são permitidas'
    ]);
    exit;
}

// Validar tamanho (máximo 2MB)
$max_size = 2 * 1024 * 1024;
if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Ficheiro muito grande',
        'message' => 'Tamanho máximo: 2MB',
        'size_received' => round($file['size'] / 1024 / 1024, 2) . 'MB'
    ]);
    exit;
}

// Validar se é imagem válida
$image_info = getimagesize($file['tmp_name']);
if ($image_info === false) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Ficheiro não é uma imagem válida',
        'message' => 'O ficheiro enviado não é uma imagem'
    ]);
    exit;
}

// Gerar nome único e seguro para a imagem
$image_name = 'product_' . uniqid() . '_' . time() . '.' . $file_extension;

// ✅ USAR CAMINHO ABSOLUTO PARA A PASTA UPLOADS
$upload_dir = '\\\\arca.ua.pt\\Hosting\\esan-tesp-ds-paw.web.ua.pt\\tesp-ds-g32\\uploads\\';

error_log("Upload dir: $upload_dir");

// Verificar se diretório existe
if (!is_dir($upload_dir)) {
    error_log("Diretório não existe: $upload_dir");
    http_response_code(500);
    echo json_encode([
        'error' => 'Diretório não encontrado',
        'message' => 'A pasta de uploads não existe',
        'path' => $upload_dir
    ]);
    exit;
}

// Verificar permissões
if (!is_writable($upload_dir)) {
    error_log("Sem permissões de escrita em: $upload_dir");
    http_response_code(500);
    echo json_encode([
        'error' => 'Sem permissões de escrita',
        'message' => 'A pasta de uploads não tem permissões de escrita',
        'path' => $upload_dir,
        'php_user' => get_current_user()
    ]);
    exit;
}

$upload_path = $upload_dir . $image_name;

error_log("A mover ficheiro:");
error_log("   FROM: " . $file['tmp_name']);
error_log("   TO: $upload_path");

// Verificar se ficheiro já existe
if (file_exists($upload_path)) {
    http_response_code(409);
    echo json_encode([
        'error' => 'Ficheiro já existe',
        'message' => 'Já existe uma imagem com este nome'
    ]);
    exit;
}

// Mover ficheiro
if (!@move_uploaded_file($file['tmp_name'], $upload_path)) {
    $last_error = error_get_last();
    error_log("Erro ao mover ficheiro: " . print_r($last_error, true));

    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao fazer upload da imagem',
        'message' => 'Não foi possível guardar o ficheiro',
        'debug' => [
            'upload_path' => $upload_path,
            'tmp_file' => $file['tmp_name'],
            'tmp_exists' => file_exists($file['tmp_name']),
            'dir_writable' => is_writable($upload_dir),
            'error' => $last_error['message'] ?? 'Desconhecido'
        ]
    ]);
    exit;
}

error_log("Imagem guardada com sucesso: $image_name em $upload_path");

// ===== 11. INSERIR PRODUTO NA BASE DE DADOS =====
try {
    $sql = "INSERT INTO products 
            (name, category_id, price, quantity, description, image_url, 
             discont, produtor, regiao, pais, ano, volumeal) 
            VALUES 
            (:name, :category_id, :price, :quantity, :description, :image_url, 
             :discont, :produtor, :regiao, :pais, :ano, :volumeal)";

    $stmt = $pdo->prepare($sql);

    //DEBUG: Mostrar valores que vão ser inseridos
    $params = [
        ':name' => $name,
        ':category_id' => $category_id,
        ':price' => $price,
        ':quantity' => $quantity,
        ':description' => $description,
        ':image_url' => $image_name,
        ':discont' => $discount,
        ':produtor' => $producer,
        ':regiao' => $region,
        ':pais' => $country,
        ':ano' => $year,
        ':volumeal' => $alcohol
    ];

    error_log("Parâmetros SQL:");
    error_log(print_r($params, true));

    $stmt->execute($params);

    $product_id = $pdo->lastInsertId();

    error_log("Produto criado: ID $product_id - $name (Categoria: {$category['nome']})");

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'product_id' => $product_id,
        'message' => 'Produto criado com sucesso',
        'product' => [
            'id' => $product_id,
            'name' => $name,
            'category_id' => $category_id,
            'category_name' => $category['nome'],
            'price' => $price,
            'quantity' => $quantity,
            'image_url' => $image_name,
            'discount' => $discount
        ]
    ]);
} catch (PDOException $e) {
    // MOSTRAR ERRO COMPLETO DO SQL
    error_log("   Erro SQL completo:");
    error_log("   Mensagem: " . $e->getMessage());
    error_log("   Código: " . $e->getCode());
    error_log("   SQL State: " . $e->errorInfo[0] ?? 'N/A');
    error_log("   Driver Error Code: " . $e->errorInfo[1] ?? 'N/A');
    error_log("   Driver Error Message: " . $e->errorInfo[2] ?? 'N/A');

    // Se houve erro na BD, apagar imagem
    if (file_exists($upload_path)) {
        @unlink($upload_path);
        error_log("Imagem removida devido a erro na BD");
    }

    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao criar produto',
        'message' => 'Ocorreu um erro ao guardar na base de dados',
        'sql_error' => $e->getMessage(), // Mostrar erro SQL no JSON
        'sql_code' => $e->getCode()
    ]);
}
