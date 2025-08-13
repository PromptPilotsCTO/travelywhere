<?php
// test_create_triplog.php - Test der create_triplog.php API

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test der create_triplog.php API</h1>";

// Test-Daten
$testData = [
    'user_id' => 1,
    'title' => 'Test Triplog',
    'remember_text' => 'Dies ist ein Test-Triplog'
];

echo "<h2>Test-Daten:</h2>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

// API-Aufruf simulieren
$url = 'https://travelywhere.com/api/create_triplog.php';
$jsonData = json_encode($testData);

echo "<h2>API-Aufruf:</h2>";
echo "URL: $url<br>";
echo "Daten: " . $jsonData . "<br><br>";

// cURL verwenden
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
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
?> 