<?php

require_once "../config.php";
require_once "../core.php";

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = connectDB($db);

    // Obter todas as categorias da tabela categorias
    $sql = "SELECT id, nome FROM categorias ORDER BY nome ASC";
    $stmt = $pdo->query($sql);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($categories)) {
        http_response_code(404);
        echo json_encode([
            'error' => 'Nenhuma categoria encontrada',
            'categories' => []
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
} catch (PDOException $e) {
    error_log("Erro ao obter categorias: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao obter categorias',
        'message' => 'Ocorreu um erro no servidor'
    ]);
}
