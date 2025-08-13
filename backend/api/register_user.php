<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';
require_once 'vendor/PHPMailer/Exception.php';
require_once 'vendor/PHPMailer/PHPMailer.php';
require_once 'vendor/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$response = ['success' => false, 'message' => 'Ein unbekannter Fehler ist aufgetreten.'];

// Eingabedaten von Flutter App entgegennehmen (JSON-Body)
$input = json_decode(file_get_contents('php://input'), true);

// Sprache aus Eingabe oder Standard 'de'
// Stellen Sie sicher, dass Ihre Flutter-App ggf. $input['language'] (z.B. 'en' oder 'de') sendet.
$language = isset($input['language']) && in_array(strtolower($input['language']), ['en', 'de']) ? strtolower($input['language']) : 'de';

if (isset($input['name']) && isset($input['email']) && isset($input['password'])) {
    $name = trim($input['name']);
    $email = trim($input['email']);
    $password = trim($input['password']);

    if (empty($name) || empty($email) || empty($password)) {
        $response['message'] = 'Bitte alle Felder ausfüllen.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Ungültige E-Mail-Adresse.';
        echo json_encode($response);
        exit;
    }

    // Datenbankverbindung herstellen
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        $response['message'] = 'Datenbankverbindungsfehler: ' . $conn->connect_error;
        error_log('DB Connect Error: ' . $conn->connect_error);
        echo json_encode($response);
        exit;
    }
    $conn->set_charset("utf8mb4");

    // Prüfen, ob E-Mail bereits existiert
    $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_check_email->bind_param("s", $email);
    $stmt_check_email->execute();
    $stmt_check_email->store_result();

    if ($stmt_check_email->num_rows > 0) {
        $response['message'] = 'Ein Benutzer mit dieser E-Mail-Adresse existiert bereits.';
        $stmt_check_email->close();
        $conn->close();
        echo json_encode($response);
        exit;
    }
    $stmt_check_email->close();

    // Passwort hashen
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Aktivierungs-Token generieren
    $activation_token = bin2hex(random_bytes(32));

    // Benutzer in Datenbank einfügen
    $stmt_insert_user = $conn->prepare("INSERT INTO users (firstname, email, password_hash, activation_token, is_confirmed) VALUES (?, ?, ?, ?, 0)");
    $stmt_insert_user->bind_param("ssss", $name, $email, $password_hash, $activation_token);

    if ($stmt_insert_user->execute()) {
        $user_id = $stmt_insert_user->insert_id;

        // E-Mail-Versand vorbereiten
        $emailTemplateKey = 'registration_confirmation';
        $emailSubject = ''; 
        $emailBody = '';
        $templateFound = false;

        $stmt_template = $conn->prepare("SELECT subject, body FROM email_templates WHERE template_key = ? AND language = ?");
        
        if (!$stmt_template) {
            error_log("DB Prepare Error (template select): " . $conn->error);
            $response['success'] = true; // Benutzer ist registriert
            $response['message'] = 'Registrierung erfolgreich! Die Bestätigungs-E-Mail konnte aufgrund eines internen Fehlers (DBP) nicht vorbereitet werden. Bitte kontaktieren Sie den Support.';
        } else {
            $stmt_template->bind_param("ss", $emailTemplateKey, $language);
            $stmt_template->execute();
            $result_template = $stmt_template->get_result();

            if ($template_row = $result_template->fetch_assoc()) {
                $emailSubject = $template_row['subject'];
                $emailBody = $template_row['body'];
                $templateFound = true;
            } else {
                if ($language !== 'de') { // Nur Fallback versuchen, wenn nicht schon Deutsch versucht wurde
                    $fallbackLang = 'de';
                    $stmt_template->bind_param("ss", $emailTemplateKey, $fallbackLang);
                    $stmt_template->execute();
                    $result_template_fallback = $stmt_template->get_result();
                    if ($template_row_fallback = $result_template_fallback->fetch_assoc()) {
                        $emailSubject = $template_row_fallback['subject'];
                        $emailBody = $template_row_fallback['body'];
                        $templateFound = true;
                        error_log("E-Mail-Vorlage für Sprache '$language' nicht gefunden. Fallback auf '$fallbackLang' für Key: $emailTemplateKey");
                    }
                }
            }
            $stmt_template->close();
        }
        
        if (!$templateFound) {
            error_log("Keine E-Mail-Vorlage (auch kein Fallback 'de') gefunden für Key: $emailTemplateKey. Registrierung erfolgreich, aber E-Mail nicht gesendet.");
            if ($response['success'] !== true) { // Nur setzen, wenn nicht schon durch DBP-Fehler gesetzt
                $response['success'] = true; 
                $response['message'] = 'Registrierung erfolgreich! Die Bestätigungs-E-Mail konnte aufgrund eines internen Fehlers (TPL) nicht gesendet werden. Bitte kontaktieren Sie den Support.';
            }
        } else {
            // Platzhalter ersetzen
            $verification_link = APP_URL . '/api/verify_email.php?token=' . $activation_token . '&email=' . urlencode($email);
            
            $finalEmailSubject = str_replace('{{name}}', htmlspecialchars($name), $emailSubject);
            $finalEmailSubject = str_replace('{{verification_link}}', $verification_link, $finalEmailSubject);

            $finalEmailBody = str_replace('{{name}}', htmlspecialchars($name), $emailBody);
            $finalEmailBody = str_replace('{{verification_link}}', $verification_link, $finalEmailBody);

            $mail = new PHPMailer(true);
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // SMTP Debug-Ausgabe deaktiviert
            try {
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = SMTP_PORT;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($email, $name);

                $mail->isHTML(true);
                $mail->Subject = $finalEmailSubject;
                $mail->Body    = $finalEmailBody;
                $mail->AltBody = strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $finalEmailBody));

                $mail->send();
                $response['success'] = true;
                $response['message'] = 'Registrierung erfolgreich! Bitte überprüfen Sie Ihr E-Mail-Postfach, um Ihre Adresse zu bestätigen.';

            } catch (Exception $e) {
                error_log("PHPMailer Error: {$mail->ErrorInfo}");
                $response['message'] = "Registrierung war erfolgreich, aber die Bestätigungs-E-Mail konnte nicht gesendet werden. Fehler: {$mail->ErrorInfo}. Bitte kontaktieren Sie den Support.";
            }
        }
    } else {
        $response['message'] = 'Fehler bei der Registrierung: ' . (isset($stmt_insert_user) ? $stmt_insert_user->error : 'Unbekannter DB Fehler');
        error_log('DB Insert Error: ' . (isset($stmt_insert_user) ? $stmt_insert_user->error : 'Unbekannter DB Fehler'));
    }

    $stmt_insert_user->close();
    $conn->close();

} else {
    $response['message'] = 'Ungültige Eingabedaten.';
}

echo json_encode($response);
?>