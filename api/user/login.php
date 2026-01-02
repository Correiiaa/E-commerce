<?php

require_once "../config.php";
require_once "../core.php";


header('Content-Type: application/json; charset=utf-8');


// Ler dados JSON do body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados invalidos']);
    exit;
}

// Validar input
$identifier = filter_var(($data['username'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
$identifier = trim($identifier);
$password = filter_var(($data['password']) ? $data['password'] : '', FILTER_UNSAFE_RAW);

if ($identifier === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Username e password obrigatorios']);
    exit;
}

try {
    $pdo = connectDB($db);

    // Procurar utilizador
    $sql = "SELECT id, username, password, is_admin, fname, lname FROM users WHERE username = :username OR email = :username LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':username', $identifier, PDO::PARAM_STR);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($password, $row['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Username ou password incorretos']);
        exit;
    }

    // Autenticar
    session_regenerate_id(true);
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['role'] = $row['is_admin'];
    $_SESSION['first_name'] = $row['fname'] ?? '';
    $_SESSION['last_name'] = $row['lname'] ?? '';

    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'username' => $row['username'],
        'first_name' => $row['fname'] ?? '',
        'last_name' => $row['lname'] ?? '',
        'role' => (bool) $row['is_admin'],
        'created_at' => $row['created_at'] ?? null
    ]);
} catch (PDOException $e) {
    error_log("Erro no login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao processar login']);
}
