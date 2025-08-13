<?php
// create_triplog.php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Bei Preflight-Request sofort beenden
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Timeout setzen
set_time_limit(30);

// Logging-Funktion
function logDebug($message) {
    $logFile = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

logDebug("=== Neue Anfrage gestartet ===");
logDebug("Request Method: " . $_SERVER['REQUEST_METHOD']);
logDebug("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'nicht gesetzt'));

// config.php einbinden (stellt $pdo bereit)
require_once __DIR__ . '/config.php';

try {
    // JSON-Daten einlesen
    $rawInput = file_get_contents('php://input');
    logDebug("Raw Input: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logDebug("JSON Parse Error: " . json_last_error_msg());
        echo json_encode(['success' => false, 'message' => 'Ungültiges JSON-Format']);
        exit();
    }
    
    logDebug("Parsed Input: " . print_r($input, true));

    $user_id = isset($input['user_id']) ? intval($input['user_id']) : null;
    $title = isset($input['title']) ? trim($input['title']) : null;
    $remember_text = isset($input['remember_text']) ? trim($input['remember_text']) : null;

    logDebug("Extracted values - user_id: $user_id, title: '$title', remember_text: '$remember_text'");

    if (!$user_id || $title === null) {
        logDebug("Validation failed - missing required fields");
        echo json_encode(['success' => false, 'message' => 'Fehlende Felder: user_id und title sind erforderlich']);
        exit();
    }

    // Datenbank-Operation
    logDebug("Starting database operation...");
    $stmt = $pdo->prepare("INSERT INTO triplogs (user_id, title, remember_text, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $result = $stmt->execute([$user_id, $title, $remember_text]);
    
    if (!$result) {
        logDebug("Database insert failed");
        echo json_encode(['success' => false, 'message' => 'Datenbankfehler beim Einfügen']);
        exit();
    }
    
    $triplog_id = $pdo->lastInsertId();
    logDebug("Triplog successfully created with ID: $triplog_id");

    $response = [
        'success' => true,
        'message' => 'Triplog erfolgreich erstellt',
        'triplog_id' => $triplog_id
    ];
    
    logDebug("Sending response: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    logDebug("Exception occurred: " . $e->getMessage());
    logDebug("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Fehler beim Erstellen des Triplogs: ' . $e->getMessage()
    ]);
}

logDebug("=== Anfrage beendet ===");
?>
