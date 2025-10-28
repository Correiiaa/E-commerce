<?php

require_once  "../config.php";
require_once  "../core.php";

error_log("Utilizador: " . ($db['username'] ?? 'unknown'));
$pdo = connectDB($db);

$username = filter_input(INPUT_POST, 'user', FILTER_UNSAFE_RAW);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = filter_input(INPUT_POST, 'pwd', FILTER_UNSAFE_RAW);
$pwdconfirm = filter_input(INPUT_POST, 'confirm', FILTER_UNSAFE_RAW);
$fname = filter_input(INPUT_POST, 'fname', FILTER_UNSAFE_RAW);
$lname = filter_input(INPUT_POST, 'lname', FILTER_UNSAFE_RAW);

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
    echo "User registered successfully";
    session_start();
}
