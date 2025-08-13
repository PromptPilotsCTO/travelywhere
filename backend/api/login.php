<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// CORS-Header hinzufügen
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight-Anfrage (OPTIONS) behandeln
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['email']) || !isset($data['password'])) {
        echo json_encode(['success' => false, 'message' => 'Ungültige Eingabedaten']);
        exit();
    }

    $email = $data['email'];
    $password = $data['password'];

    try {
        $stmt = $pdo->prepare('SELECT id, password_hash, firstname FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Nach erfolgreichem Login, prüfe und verknüpfe offene Einladungen
            try {
                $update_stmt = $pdo->prepare("UPDATE user_shares SET invitee_user_id = ? WHERE invitee_email = ? AND status = 'accepted' AND invitee_user_id IS NULL");
                $update_stmt->execute([$user['id'], $email]);
            } catch (PDOException $e) {
                // Fehler beim Update ignorieren oder loggen, der Login selbst war erfolgreich.
            }

            echo json_encode([
                'success' => true,
                'message' => 'Login erfolgreich',
                'data' => [
                    'user_id' => (int)$user['id'],
                    'firstname' => $user['firstname']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'E-Mail oder Passwort ungültig']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
    }
}
