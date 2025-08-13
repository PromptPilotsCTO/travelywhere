<?php
$host = 'localhost';
$dbname = 'u952254373_travelywhereDB';
$username = 'u952254373_travelandre';
$password = 'Travel-Heinz234!';
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database connection failed: " . $e->getMessage();
    exit;
}
?>
