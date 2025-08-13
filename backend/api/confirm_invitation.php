<?php
// Alle Header und die DB-Verbindung kommen jetzt aus der config.php
require 'config.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Fall 1: Nutzer klickt auf den Link in der E-Mail (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Den Content-Type für diese Antwort auf HTML setzen
    header('Content-Type: text/html; charset=utf-8');

    $token = $_GET['token'] ?? null;

    if (empty($token)) {
        http_response_code(400);
        echo 'Token is missing.';
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM user_shares WHERE token = ? AND status = 'pending'");
    $stmt->execute([$token]);
    $invitation = $stmt->fetch();

    if ($invitation) {
        // Status auf 'accepted' setzen. Die Spalte 'accepted_at' existiert nicht.
        $stmt_update = $pdo->prepare("UPDATE user_shares SET status = 'accepted' WHERE id = ?");
        if ($stmt_update->execute([$invitation['id']])) {
            http_response_code(200);
            echo "<h1>Invitation Accepted!</h1><p>Thank you for accepting the invitation. You can now see the shared triplogs next time you open the travelywhere app.</p>";
        } else {
            http_response_code(500);
            echo 'Failed to accept invitation.';
        }
    } else {
        http_response_code(404);
        echo 'Invitation not found or already accepted.';
    }

// Fall 2: Nutzer ist in der App und verknüpft den Account (POST)
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sicherstellen, dass die Antwort JSON ist
    header('Content-Type: application/json');

    $data = json_decode(file_get_contents('php://input'), true);
    $token = $data['token'];
    $user_id = $data['user_id'];

    if (empty($token) || empty($user_id)) {
        http_response_code(400);
        echo json_encode(['message' => 'Token and user ID are required.']);
        exit();
    }

    // Find the invitation by token
    $stmt = $pdo->prepare("SELECT * FROM user_shares WHERE token = ? AND status = 'pending'");
    $stmt->execute([$token]);
    $invitation = $stmt->fetch();

    if ($invitation) {
        // Update the invitation status and set the invitee_user_id
        $stmt_update = $pdo->prepare("UPDATE user_shares SET invitee_user_id = ?, status = 'accepted' WHERE id = ?");
        if ($stmt_update->execute([$user_id, $invitation['id']])) {
            http_response_code(200);
            echo json_encode(['message' => 'Invitation accepted successfully! You can now see shared triplogs.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to accept invitation.']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Invitation not found or already accepted.']);
    }
} else {
    // Für alle anderen Methoden, die nicht GET, POST oder OPTIONS sind
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Method not allowed.']);
}
?>
