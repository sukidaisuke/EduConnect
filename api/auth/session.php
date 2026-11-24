<?php
// api/auth/session.php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["status" => "unauthorized", "message" => "Not logged in."]);
    exit();
}

echo json_encode([
    "status" => "success",
    "user"   => $_SESSION['user']
]);
?>