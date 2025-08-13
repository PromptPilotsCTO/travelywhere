<?php
// setup_database.php - Erstellt die notwendigen Tabellen für die Travelywhere App

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    // SQL-Befehle zum Erstellen der Tabellen
    $sql_commands = [
        // Users Tabelle
        "CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `firstname` varchar(100) NOT NULL,
            `lastname` varchar(100) DEFAULT NULL,
            `username` varchar(100) DEFAULT NULL,
            `email` varchar(255) NOT NULL,
            `password` varchar(255) NOT NULL,
            `email_verified` tinyint(1) DEFAULT 0,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`),
            KEY `idx_email` (`email`),
            KEY `idx_email_verified` (`email_verified`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Email Verification Tokens Tabelle
        "CREATE TABLE IF NOT EXISTS `email_verification_tokens` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `token` varchar(255) NOT NULL,
            `expires_at` timestamp NOT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `token` (`token`),
            KEY `idx_token` (`token`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_expires_at` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Triplogs Tabelle
        "CREATE TABLE IF NOT EXISTS `triplogs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `title` varchar(255) NOT NULL,
            `remember_text` text DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Triplog Media Tabelle
        "CREATE TABLE IF NOT EXISTS `triplog_media` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `triplog_id` int(11) NOT NULL,
            `file_path` varchar(500) NOT NULL,
            `file_type` enum('image','video') NOT NULL,
            `file_size` int(11) DEFAULT NULL,
            `gps_latitude` decimal(10,8) DEFAULT NULL,
            `gps_longitude` decimal(11,8) DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_triplog_id` (`triplog_id`),
            KEY `idx_file_type` (`file_type`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // User Sessions Tabelle
        "CREATE TABLE IF NOT EXISTS `user_sessions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `session_token` varchar(255) NOT NULL,
            `device_info` varchar(255) DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `expires_at` timestamp NOT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `session_token` (`session_token`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_expires_at` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    $results = [];
    
    foreach ($sql_commands as $sql) {
        try {
            $pdo->exec($sql);
            $results[] = "Tabelle erfolgreich erstellt/überprüft";
        } catch (Exception $e) {
            $results[] = "Fehler beim Erstellen der Tabelle: " . $e->getMessage();
        }
    }
    
    // Überprüfen der erstellten Tabellen
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'message' => 'Datenbank-Setup abgeschlossen',
        'results' => $results,
        'tables' => $tables
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Datenbank-Setup: ' . $e->getMessage()
    ]);
}
?> 