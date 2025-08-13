<?php
require 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$token = bin2hex(random_bytes(16));
$hash = password_hash($password, PASSWORD_BCRYPT);

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO users (email, password_hash, is_confirmed, activation_token) VALUES (?, ?, 0, ?)");
if ($stmt->execute([$email, $hash, $token])) {
    // Normally you'd send email here
    echo json_encode(['status' => 'success', 'message' => 'User registered.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Registration failed.']);
}
?>
