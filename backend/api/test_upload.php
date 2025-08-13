<?php
// test_upload.php - Test der upload_triplog_media.php API

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test der upload_triplog_media.php API</h1>";

// Test-Daten
$testData = [
    'triplog_id' => 1, // Ersetzen Sie dies durch eine echte Triplog-ID
    'file_type' => 'image',
    'gps_latitude' => '50.123456',
    'gps_longitude' => '8.123456'
];

echo "<h2>Test-Daten:</h2>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

// Test-Datei erstellen (kleines Testbild)
$testImagePath = __DIR__ . '/test_image.png';
if (!file_exists($testImagePath)) {
    // Ein einfaches 1x1 PNG-Bild erstellen
    $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    file_put_contents($testImagePath, $pngData);
    echo "<p>Test-Bild erstellt: $testImagePath</p>";
}

echo "<h2>API-Aufruf:</h2>";
echo "URL: https://travelywhere.com/api/upload_triplog_media.php<br>";
echo "Datei: $testImagePath<br><br>";

// cURL verwenden
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://travelywhere.com/api/upload_triplog_media.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'triplog_id' => $testData['triplog_id'],
    'file_type' => $testData['file_type'],
    'gps_latitude' => $testData['gps_latitude'],
    'gps_longitude' => $testData['gps_longitude'],
    'file' => new CURLFile($testImagePath, 'image/png', 'test_image.png')
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

echo "<h2>Antwort:</h2>";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "<p style='color: red;'>cURL Fehler: $error</p>";
} else {
    echo "<p>HTTP Status Code: $httpCode</p>";
    echo "<p>Antwort:</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // JSON parsen
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "<p>Geparste Antwort:</p>";
        echo "<pre>" . json_encode($decoded, JSON_PRETTY_PRINT) . "</pre>";
        
        if ($decoded['success']) {
            echo "<p style='color: green;'>✅ Upload erfolgreich!</p>";
            if (isset($decoded['file_url'])) {
                echo "<p>Datei-URL: <a href='{$decoded['file_url']}' target='_blank'>{$decoded['file_url']}</a></p>";
            }
            if (isset($decoded['file_path'])) {
                echo "<p>Datei-Pfad: {$decoded['file_path']}</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Upload fehlgeschlagen: {$decoded['message']}</p>";
        }
    } else {
        echo "<p style='color: red;'>Fehler beim Parsen der JSON-Antwort</p>";
    }
}

curl_close($ch);

echo "<h2>Debug-Log:</h2>";
$logFile = __DIR__ . '/debug.log';
if (file_exists($logFile)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
} else {
    echo "<p>Debug-Log-Datei nicht gefunden.</p>";
}

echo "<h2>Verzeichnisstruktur:</h2>";
$imagesDir = __DIR__ . '/images/';
if (is_dir($imagesDir)) {
    echo "<p>Images-Verzeichnis gefunden:</p>";
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($imagesDir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            echo "- " . $file->getPathname() . " (" . filesize($file->getPathname()) . " bytes)<br>";
        }
    }
} else {
    echo "<p>Images-Verzeichnis noch nicht erstellt.</p>";
}
?> 