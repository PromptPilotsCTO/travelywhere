<?php
// test_db.php - Test der Datenbankverbindung und Tabellenstruktur

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    // Test der Verbindung
    echo "Datenbankverbindung erfolgreich\n";
    
    // Alle Tabellen auflisten
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Verfügbare Tabellen:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // Struktur der triplogs Tabelle anzeigen (falls vorhanden)
    if (in_array('triplogs', $tables)) {
        echo "\nStruktur der triplogs Tabelle:\n";
        $stmt = $pdo->query("DESCRIBE triplogs");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
    } else {
        echo "\nWARNUNG: triplogs Tabelle nicht gefunden!\n";
    }
    
    // Struktur der users Tabelle anzeigen
    if (in_array('users', $tables)) {
        echo "\nStruktur der users Tabelle:\n";
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
}
?> 