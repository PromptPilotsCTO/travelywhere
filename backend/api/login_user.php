<?php
require_once 'config.php';

$response = ['success' => false, 'message' => 'Ein unbekannter Fehler ist aufgetreten.'];

// Eingabedaten von Flutter App entgegennehmen (JSON-Body)
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['email']) && isset($input['password'])) {
    $email = trim($input['email']);
    $password = trim($input['password']);

    if (empty($email) || empty($password)) {
        $response['message'] = 'Bitte E-Mail und Passwort eingeben.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Ungültige E-Mail-Adresse.';
        echo json_encode($response);
        exit;
    }

    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        $response['message'] = 'Datenbankverbindungsfehler.';
        error_log('DB Connect Error in login_user: ' . $conn->connect_error);
        echo json_encode($response);
        exit;
    }
    $conn->set_charset("utf8mb4");

    // Benutzer suchen
    $stmt = $conn->prepare("SELECT id, firstname, lastname, username, email, password_hash, is_confirmed FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Passwort überprüfen
        if (password_verify($password, $user['password_hash'])) {
            // E-Mail Bestätigung prüfen
            if ($user['is_confirmed'] == 1) {
                $response['success'] = true;
                $response['message'] = 'Login erfolgreich!';
                // Benutzerdaten für die App (ohne sensible Infos wie Passwort-Hash)
                $response['user'] = [
                    'id' => $user['id'],
                    'firstname' => $user['firstname'],
                    'lastname' => $user['lastname'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ];
                // Hier könnte man auch eine Session starten oder einen JWT-Token generieren,
                // aber für den Anfang halten wir es einfach.
            } else {
                $response['message'] = 'Bitte bestätigen Sie zuerst Ihre E-Mail-Adresse. Überprüfen Sie Ihr Postfach.';
                // Optional: Link zum erneuten Senden der Bestätigungs-E-Mail anbieten (erfordert separate Logik)
            }
        } else {
            $response['message'] = 'Ungültige E-Mail-Adresse oder falsches Passwort.';
        }
    } else {
        $response['message'] = 'Benutzer nicht gefunden. Bitte registrieren Sie sich zuerst.';
    }

    $stmt->close();
    $conn->close();

} else {
    $response['message'] = 'Ungültige Eingabedaten.';
}

echo json_encode($response);
?>