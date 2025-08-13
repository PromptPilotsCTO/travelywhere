<?php
require 'db_config.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    http_response_code(400);
    echo "Missing token.";
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET is_confirmed = 1 WHERE activation_token = ?");
$stmt->execute([$token]);

if ($stmt->rowCount()) {
    echo "Account successfully activated!";
} else {
    echo "Invalid or expired token.";
}
?>
