<?php
session_start();
header("Content-Type: application/json");

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => true,
        "user_id" => $_SESSION['user_id']
    ]);
} else {
    echo json_encode(["status" => false]);
}
?>