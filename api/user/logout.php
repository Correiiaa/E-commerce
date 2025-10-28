<?php

if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['loggedout' => true]);
exit;
