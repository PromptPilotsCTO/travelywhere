<?php
// upload_triplog_media.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Bei Preflight-Request sofort beenden
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// config.php einbinden (stellt $pdo bereit)
require_once __DIR__ . '/config.php';

// Logging-Funktion
function logDebug($message) {
    $logFile = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] UPLOAD: $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

logDebug("=== Neue Upload-Anfrage gestartet ===");

// Hilfsfunktion für Antwort
function respond($arr) {
    logDebug("Response: " . json_encode($arr));
    echo json_encode($arr);
    exit();
}

// Prüfen, ob ein Triplog ausgewählt wurde
if (!isset($_POST['triplog_id']) || !isset($_POST['file_type'])) {
    logDebug("Fehler: triplog_id oder file_type fehlt");
    respond(['success' => false, 'message' => 'triplog_id oder file_type fehlt']);
}

// triplog_id validieren und als Integer konvertieren
$triplog_id_raw = $_POST['triplog_id'];
if (!is_numeric($triplog_id_raw)) {
    logDebug("Fehler: triplog_id ist nicht numerisch: " . $triplog_id_raw);
    respond(['success' => false, 'message' => 'Ungültige triplog_id: muss eine Zahl sein']);
}

$triplog_id = intval($triplog_id_raw);
if ($triplog_id <= 0) {
    logDebug("Fehler: triplog_id ist nicht positiv: " . $triplog_id);
    respond(['success' => false, 'message' => 'Ungültige triplog_id: muss größer als 0 sein']);
}

$file_type = $_POST['file_type']; // 'image' oder 'video'
$latitude = isset($_POST['gps_latitude']) ? $_POST['gps_latitude'] : null;
$longitude = isset($_POST['gps_longitude']) ? $_POST['gps_longitude'] : null;

logDebug("triplog_id: $triplog_id (validiert), file_type: $file_type");

// User ID aus der triplogs Tabelle holen
try {
    $stmt = $pdo->prepare("SELECT user_id FROM triplogs WHERE id = ?");
    $stmt->execute([$triplog_id]);
    $triplog = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$triplog) {
        logDebug("Fehler: Triplog mit ID $triplog_id nicht gefunden");
        respond(['success' => false, 'message' => 'Triplog nicht gefunden']);
    }
    
    $user_id = $triplog['user_id'];
    logDebug("user_id gefunden: $user_id");
    
} catch (Exception $e) {
    logDebug("Datenbankfehler beim Abrufen der user_id: " . $e->getMessage());
    respond(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
}

// Datei-Upload prüfen
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    logDebug("Fehler: Datei-Upload fehlgeschlagen - Error Code: " . ($_FILES['file']['error'] ?? 'keine Datei'));
    respond(['success' => false, 'message' => 'Datei-Upload fehlgeschlagen']);
}

logDebug("Datei-Upload erfolgreich - Originalname: " . $_FILES['file']['name']);

// Zielverzeichnis für Uploads: images/<user_id>/
$uploadDir = __DIR__ . '/images/' . $user_id . '/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        logDebug("Fehler: Verzeichnis konnte nicht erstellt werden: $uploadDir");
        respond(['success' => false, 'message' => 'Verzeichnis konnte nicht erstellt werden']);
    }
    logDebug("Verzeichnis erstellt: $uploadDir");
}

// Eindeutigen Dateinamen mit Timestamp erzeugen
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
$timestamp = date('Y-m-d_H-i-s');
$uniqueId = uniqid();
$filename = $timestamp . '_' . $uniqueId . '.' . $ext;
$targetFile = $uploadDir . $filename;

logDebug("Zieldatei: $targetFile");

// Datei verschieben
if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
    logDebug("Fehler: Datei konnte nicht verschoben werden");
    respond(['success' => false, 'message' => 'Datei konnte nicht gespeichert werden']);
}

logDebug("Datei erfolgreich verschoben");

// Relativen Pfad für die Datenbank (ohne __DIR__)
$relativePath = 'images/' . $user_id . '/' . $filename;

// Dateigröße ermitteln
$fileSize = filesize($targetFile);

try {
    // Eintrag in triplog_media mit den korrekten Spaltennamen
    $stmt = $pdo->prepare("INSERT INTO triplog_media (triplog_id, file_url, file_type, file_size, gps_latitude, gps_longitude, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $triplog_id,
        $relativePath,
        $file_type,
        $fileSize,
        $latitude,
        $longitude
    ]);
    
    $mediaId = $pdo->lastInsertId();
    logDebug("Datenbankeintrag erfolgreich - Media ID: $mediaId");
    
    // URL für die Antwort (für die App)
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
        . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
    $file_url = $baseUrl . '/' . $relativePath;
    
    respond([
        'success' => true, 
        'message' => 'Datei erfolgreich hochgeladen', 
        'file_url' => $file_url,
        'file_path' => $relativePath,
        'media_id' => $mediaId
    ]);
    
} catch (Exception $e) {
    logDebug("Datenbankfehler: " . $e->getMessage());
    // Datei löschen, wenn Datenbankeintrag fehlschlägt
    if (file_exists($targetFile)) {
        unlink($targetFile);
        logDebug("Datei gelöscht nach Datenbankfehler");
    }
    respond(['success' => false, 'message' => 'Fehler beim Speichern in der Datenbank: ' . $e->getMessage()]);
}

logDebug("=== Upload-Anfrage beendet ===");
?>
