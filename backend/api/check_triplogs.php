<?php
// check_triplogs.php - Überprüft Triplogs und Media-Einträge

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    echo "<h1>Datenbank-Überprüfung: Triplogs und Media</h1>";
    
    // Alle Triplogs anzeigen
    echo "<h2>Alle Triplogs:</h2>";
    $stmt = $pdo->query("SELECT * FROM triplogs ORDER BY created_at DESC LIMIT 10");
    $triplogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($triplogs)) {
        echo "<p>Keine Triplogs gefunden.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Titel</th><th>Erstellt am</th></tr>";
        foreach ($triplogs as $triplog) {
            echo "<tr>";
            echo "<td>{$triplog['id']}</td>";
            echo "<td>{$triplog['user_id']}</td>";
            echo "<td>{$triplog['title']}</td>";
            echo "<td>{$triplog['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Alle Media-Einträge anzeigen
    echo "<h2>Alle Media-Einträge:</h2>";
    $stmt = $pdo->query("SELECT * FROM triplog_media ORDER BY created_at DESC LIMIT 10");
    $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($media)) {
        echo "<p>Keine Media-Einträge gefunden.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Triplog ID</th><th>Datei-Pfad</th><th>Typ</th><th>Größe</th><th>Erstellt am</th></tr>";
        foreach ($media as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['triplog_id']}</td>";
            echo "<td>{$item['file_path']}</td>";
            echo "<td>{$item['file_type']}</td>";
            echo "<td>{$item['file_size']}</td>";
            echo "<td>{$item['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verzeichnisstruktur überprüfen
    echo "<h2>Images-Verzeichnis:</h2>";
    $imagesDir = __DIR__ . '/images/';
    if (is_dir($imagesDir)) {
        echo "<p>Images-Verzeichnis gefunden: $imagesDir</p>";
        
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($imagesDir));
        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        
        if (empty($files)) {
            echo "<p>Keine Dateien im Images-Verzeichnis gefunden.</p>";
        } else {
            echo "<p>Gefundene Dateien:</p>";
            echo "<ul>";
            foreach ($files as $file) {
                $relativePath = str_replace(__DIR__ . '/', '', $file);
                $size = filesize($file);
                echo "<li>$relativePath ($size bytes)</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p>Images-Verzeichnis nicht gefunden.</p>";
    }
    
    // Debug-Log anzeigen
    echo "<h2>Debug-Log (letzte 20 Zeilen):</h2>";
    $logFile = __DIR__ . '/debug.log';
    if (file_exists($logFile)) {
        $lines = file($logFile);
        $lastLines = array_slice($lines, -20);
        echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 400px; overflow-y: auto;'>";
        foreach ($lastLines as $line) {
            echo htmlspecialchars($line);
        }
        echo "</pre>";
    } else {
        echo "<p>Debug-Log-Datei nicht gefunden.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Fehler: " . $e->getMessage() . "</p>";
}
?> 