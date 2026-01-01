<?php

// Função helper para validar token
function validateUserToken($pdo)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Verificar se sessão existe
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Nao autenticado']);
        exit;
    }

    // Verificar se token é válido na BD
    $sql = "SELECT user_id FROM user_tokens 
            WHERE token = :token AND expires_at > NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':token' => $_SESSION['token']]);
    $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Token inválido ou expirado
    if (!$token_data || $token_data['user_id'] != $_SESSION['user_id']) {
        session_destroy();
        http_response_code(401);
        echo json_encode(['error' => 'Token expirado ou invalido']);
        exit;
    }

    // Token válido - opcional: renovar token (atualizar expires_at)
    echo json_encode(['success' => 'Token valido']);
}
