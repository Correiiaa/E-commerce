<?php
// filepath: \\arca.ua.pt\Hosting\esan-tesp-ds-paw.web.ua.pt\tesp-ds-g32\E-commerce\api\products\get_products_section.php
require "../config.php";
require "../core.php";

header('Content-Type: application/json; charset=utf-8');

$pdo = connectDB($db);

try {
    $section = filter_input(INPUT_GET, 'section', FILTER_SANITIZE_NUMBER_INT) ?? '';

    if (empty($section)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing section parameter']);
        exit;
    }

    $sql = "SELECT * FROM products WHERE category_id = :section AND active = 1";
    $stm = $pdo->prepare($sql);
    $stm->bindParam(':section', $section, PDO::PARAM_INT);
    $stm->execute();
    $products = $stm->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products);
} catch (Throwable $e) {
    error_log("DB error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
