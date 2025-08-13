<?php
// remove_connection.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['connection_id'])) {
    echo json_encode(['success' => false, 'message' => 'user_id oder connection_id fehlt']);
    exit();
}

$current_user_id = intval($data['user_id']);
$connection_id_to_remove = intval($data['connection_id']);

try {
    // Die Verbindung in beide Richtungen aufheben
    $stmt = $pdo->prepare(
        "DELETE FROM user_shares 
         WHERE (inviter_user_id = ? AND invitee_user_id = ?) 
            OR (inviter_user_id = ? AND invitee_user_id = ?)"
    );

    $stmt->execute([
        $current_user_id, 
        $connection_id_to_remove, 
        $connection_id_to_remove, 
        $current_user_id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Verbindung erfolgreich entfernt']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Keine passende Verbindung zum Entfernen gefunden']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Serverfehler: ' . $e->getMessage()]);
}
