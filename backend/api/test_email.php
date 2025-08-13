<?php
require_once 'config.php';
require_once 'vendor/PHPMailer/Exception.php';
require_once 'vendor/PHPMailer/PHPMailer.php';
require_once 'vendor/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// === Konfiguration für den Test ===
$testRecipientEmail = 'andre@travelywhere.com'; // BITTE ERSETZEN!
$testRecipientName = 'Andre';
// =================================

$mail = new PHPMailer(true);

echo "Versuche E-Mail zu senden an: " . htmlspecialchars($testRecipientEmail) . "\n";

try {
    // Server-Einstellungen
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Debug-Ausgabe aktivieren für detaillierte Logs
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // PHPMailer::ENCRYPTION_STARTTLS für Port 587
    $mail->Port       = SMTP_PORT; // 465 für SMTPS, 587 für STARTTLS
    $mail->CharSet    = 'UTF-8';

    // Empfänger
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($testRecipientEmail, $testRecipientName);

    // Inhalt
    $mail->isHTML(true);
    $mail->Subject = 'Test E-Mail von Travelywhere (PHPMailer)';
    $mail->Body    = "Hallo " . htmlspecialchars($testRecipientName) . ",<br><br>Dies ist eine Test-E-Mail, gesendet mit PHPMailer über die in config.php definierten SMTP-Einstellungen.<br><br>Viele Grüße,<br>Travelywhere Test Skript";
    $mail->AltBody = "Hallo " . htmlspecialchars($testRecipientName) . ",\n\nDies ist eine Test-E-Mail, gesendet mit PHPMailer über die in config.php definierten SMTP-Einstellungen.\n\nViele Grüße,\nTravelywhere Test Skript";

    $mail->send();
    echo 'Test-E-Mail erfolgreich gesendet!\n';
} catch (Exception $e) {
    echo "Fehler beim Senden der E-Mail: {$mail->ErrorInfo}\n";
    echo "Exception Message: {$e->getMessage()}\n";
}

?>
