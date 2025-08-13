<?php
// Database Configuration
define('DB_HOST', 'localhost'); // Oft 'localhost' bei Hostinger, ggf. prüfen
define('DB_USERNAME', 'u952254373_travelandre');
define('DB_PASSWORD', 'Travel-Heinz234!');
define('DB_NAME', 'u952254373_travelywhereDB');

// Email Configuration (Beispiel - passen Sie dies an Ihre Hostinger SMTP-Einstellungen an)
define('SMTP_HOST', 'smtp.hostinger.com'); // Beispiel, prüfen Sie Ihre Hostinger SMTP-Daten
define('SMTP_USERNAME', 'andre@travelywhere.com'); // Ihre E-Mail-Adresse bei Hostinger
define('SMTP_PASSWORD', 'THeinz234!W'); // Ihr E-Mail-Passwort
define('SMTP_PORT', 465); // Üblicher Port für TLS, kann auch 465 für SSL sein
define('SMTP_FROM_EMAIL', 'andre@travelywhere.com');
define('SMTP_FROM_NAME', 'Travelywhere App');

// Base URL für Verifizierungslinks (passen Sie dies an Ihre Domain an)
define('APP_URL', 'https://travelywhere.com'); // Ersetzen Sie ihredomain.com mit Ihrer tatsächlichen Domain

// Zeitzone setzen
date_default_timezone_set('Europe/Berlin');

// Error Reporting für Entwicklung (später ggf. anpassen)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Header für JSON-Antworten setzen
header('Content-Type: application/json');

// CORS-Header (Cross-Origin Resource Sharing) - Wichtig für lokale Flutter-Entwicklung
// Passen Sie dies für die Produktion an, um nur Ihre App-Domain zuzulassen.
header("Access-Control-Allow-Origin: *"); // Erlaubt Anfragen von jeder Domain (für Entwicklung)
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Behandeln von OPTIONS-Requests (Preflight-Requests von Browsern)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}
// Datenbankverbindung (PDO)
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USERNAME,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB-Verbindung fehlgeschlagen: ' . $e->getMessage()]);
    exit();
}
?>

