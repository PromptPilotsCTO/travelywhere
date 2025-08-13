<?php
require_once 'config.php';

// Überschreibe den Content-Type für diese HTML-Seite
header('Content-Type: text/html; charset=utf-8');

$message = "Ein Fehler ist aufgetreten oder der Link ist ungültig."; // Standard-Fehlermeldung
$success = false;

if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = urldecode($_GET['email']); // E-Mail wurde URL-kodiert

    if (!empty($token) && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

        if ($conn->connect_error) {
            error_log('DB Connect Error in verify_email: ' . $conn->connect_error);
            // Für den Benutzer wird die Standard-Fehlermeldung angezeigt
        } else {
            $conn->set_charset("utf8mb4");

            // Benutzer suchen und Token überprüfen
            $stmt = $conn->prepare("SELECT id, is_confirmed FROM users WHERE email = ? AND activation_token = ?");
            $stmt->bind_param("ss", $email, $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if ($user['is_confirmed'] == 1) {
                    $message = "Ihre E-Mail-Adresse wurde bereits bestätigt. Sie können sich jetzt anmelden.";
                    $success = true; // Bereits bestätigt ist auch ein "Erfolg" im Sinne der Aktion
                } else {
                    // Benutzer als bestätigt markieren und Token entfernen/nullen
                    $update_stmt = $conn->prepare("UPDATE users SET is_confirmed = 1, activation_token = NULL WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    if ($update_stmt->execute()) {
                        $message = "Vielen Dank! Ihre E-Mail-Adresse wurde erfolgreich bestätigt. Sie können sich jetzt in der App anmelden.";
                        $success = true;
                    } else {
                        error_log('DB Update Error in verify_email: ' . $update_stmt->error);
                        $message = "Fehler beim Aktualisieren Ihres Kontos. Bitte versuchen Sie es später erneut oder kontaktieren Sie den Support.";
                    }
                    $update_stmt->close();
                }
            } else {
                // Token nicht gefunden oder E-Mail stimmt nicht überein
                $message = "Ungültiger oder abgelaufener Bestätigungslink. Bitte versuchen Sie, sich erneut zu registrieren oder kontaktieren Sie den Support.";
            }
            $stmt->close();
            $conn->close();
        }
    } else {
        $message = "Ungültige Parameter im Bestätigungslink.";
    }
} else {
    $message = "Kein Bestätigungslink angegeben.";
}

// Einfache HTML-Antwort für den Browser
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Mail Bestätigung - Travelywhere</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 90vh; text-align: center; background-color: #f4f4f4; }
        .container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 500px; }
        h1 { color: <?php echo $success ? '#4CAF50' : '#F44336'; ?>; }
        p { font-size: 1.1em; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $success ? 'Bestätigung erfolgreich!' : 'Bestätigung fehlgeschlagen'; ?></h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        <?php if ($success): ?>
            <p>Sie können dieses Fenster jetzt schließen und zur Travelywhere App zurückkehren.</p>
            <!-- Optional: Link zur App oder Webseite -->
            <!-- <p><a href="app-schema://login">Zurück zur App</a> oder <a href="<?php echo APP_URL; ?>">Zur Webseite</a></p> -->
        <?php endif; ?>
    </div>
</body>
</html>