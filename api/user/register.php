<?php

require_once  "../config.php";
require_once  "../core.php";

error_log("Utilizador: " . ($db['username'] ?? 'unknown'));
$pdo = connectDB($db);

// Ler dados JSON do body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados invalidos']);
    exit;
}


$username = filter_var(($data['username']) ? trim($data['username']) : '', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_var(($data['email']) ? trim($data['email']) : '', FILTER_SANITIZE_EMAIL);
$password = filter_var(($data['password']) ? $data['password'] : '', FILTER_UNSAFE_RAW);
$pwdconfirm = filter_var(($data['confirm']) ? $data['confirm'] : '', FILTER_UNSAFE_RAW);
$fname = filter_var(($data['fname']) ? trim($data['fname']) : '', FILTER_SANITIZE_SPECIAL_CHARS);
$lname = filter_var(($data['lname']) ? trim($data['lname']) : '', FILTER_SANITIZE_SPECIAL_CHARS);

$passwordHash = password_hash($password, PASSWORD_DEFAULT);


if ($password !== $pwdconfirm) {
    die("Passwords do not match");
}

$sql = "SELECT * FROM users WHERE username = :username OR email = :email";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->execute();
if ($stmt->rowCount() != 0) {
    die("User already exists");
} else {
    $sql = "INSERT INTO users (username, email, password, fname, lname) VALUES (:username, :email, :password, :first_name, :last_name)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR);
    $stmt->bindParam(':first_name', $fname, PDO::PARAM_STR);
    $stmt->bindParam(':last_name', $lname, PDO::PARAM_STR);
    $stmt->execute();
    echo json_encode([
        'username'   => $row['username'],
        'first_name' => $row['fname'] ?? '',
        'last_name'  => $row['lname'] ?? '',
        'role'   => (bool) $row['is_admin'],
    ]);
}
