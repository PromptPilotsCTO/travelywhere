<?php
// get_connections.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/config.php';

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'user_id fehlt']);
    exit();
}

$current_user_id = intval($_GET['user_id']);

try {
    // Finde alle Benutzer, die den aktuellen Benutzer eingeladen haben
    $stmt_inviters = $pdo->prepare(
        "SELECT u.id, u.firstname, u.email FROM users u JOIN user_shares s ON u.id = s.inviter_user_id WHERE s.invitee_user_id = ? AND s.status = 'accepted'"
    );
    $stmt_inviters->execute([$current_user_id]);
    $inviters = $stmt_inviters->fetchAll(PDO::FETCH_ASSOC);

    // Finde alle Benutzer, die vom aktuellen Benutzer eingeladen wurden
    $stmt_invitees = $pdo->prepare(
        "SELECT u.id, u.firstname, u.email FROM users u JOIN user_shares s ON u.id = s.invitee_user_id WHERE s.inviter_user_id = ? AND s.status = 'accepted'"
    );
    $stmt_invitees->execute([$current_user_id]);
    $invitees = $stmt_invitees->fetchAll(PDO::FETCH_ASSOC);

    // Kombiniere die Ergebnisse und entferne Duplikate
    $connections = array_merge($inviters, $invitees);
    $unique_connections = [];
    $seen_ids = [];

    foreach ($connections as $conn) {
        if (!in_array($conn['id'], $seen_ids)) {
            $unique_connections[] = $conn;
            $seen_ids[] = $conn['id'];
        }
    }

    echo json_encode(['success' => true, 'data' => $unique_connections]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Serverfehler: ' . $e->getMessage()]);
}
