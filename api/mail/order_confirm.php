<?php

require_once "../config.php";
require_once "../core.php";

header('Content-Type: application/json; charset=utf-8');


$pdo = connectDB($db);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
