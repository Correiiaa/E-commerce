<?php

require_once  "../config.php";
require_once  "../core.php";

// Desabilitar warnings no output (temporário para debug)
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

error_log("Utilizador: " . ($db['username'] ?? 'unknown'));
$pdo = connectDB($db);

// Ler dados JSON do body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Dados invalidos']);
    exit;
}

$username = filter_var(($data['username']) ? trim($data['username']) : '', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_var(($data['email']) ? trim($data['email']) : '', FILTER_SANITIZE_EMAIL);
$password = filter_var(($data['password']) ? $data['password'] : '', FILTER_UNSAFE_RAW);
$fname = filter_var(($data['fname']) ? trim($data['fname']) : '', FILTER_SANITIZE_SPECIAL_CHARS);
$lname = filter_var(($data['lname']) ? trim($data['lname']) : '', FILTER_SANITIZE_SPECIAL_CHARS);

// Validar campos obrigatórios
if (empty($username) || empty($email) || empty($password) || empty($fname) || empty($lname)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Todos os campos são obrigatórios']);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Verificar se usuário já existe
$sql = "SELECT * FROM users WHERE username = :username OR email = :email";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->execute();

if ($stmt->rowCount() != 0) {
    http_response_code(409);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Usuário ou email já existem']);
    exit;
}

// Inserir novo usuário
$sql = "INSERT INTO users (username, email, password, fname, lname) VALUES (:username, :email, :password, :first_name, :last_name)";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR);
$stmt->bindParam(':first_name', $fname, PDO::PARAM_STR);
$stmt->bindParam(':last_name', $lname, PDO::PARAM_STR);

if ($stmt->execute()) {
    // Retornar sucesso com os dados que já temos
    http_response_code(201);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'username' => $username,
        'first_name' => $fname,
        'last_name' => $lname,
        'email' => $email,
        'role' => false
    ]);
} else {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao criar usuário']);
}
