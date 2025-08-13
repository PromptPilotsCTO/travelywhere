<?php
// get_triplogs.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';

// User ID aus dem GET-Parameter holen
if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'user_id fehlt']);
    exit();
}

$current_user_id = intval($_GET['user_id']);

if ($current_user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Ungültige user_id']);
    exit();
}

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
    . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);

try {
    // 1. Alle Benutzer-IDs sammeln (eigener + alle geteilten Partner)
    $user_ids_to_fetch = [$current_user_id];

    // Finde alle Benutzer, die den aktuellen Benutzer eingeladen haben
    $stmt_inviters = $pdo->prepare(
        "SELECT inviter_user_id FROM user_shares WHERE invitee_user_id = ? AND status = 'accepted'"
    );
    $stmt_inviters->execute([$current_user_id]);
    $inviter_ids = $stmt_inviters->fetchAll(PDO::FETCH_COLUMN);

    // Finde alle Benutzer, die vom aktuellen Benutzer eingeladen wurden
    $stmt_invitees = $pdo->prepare(
        "SELECT invitee_user_id FROM user_shares WHERE inviter_user_id = ? AND status = 'accepted'"
    );
    $stmt_invitees->execute([$current_user_id]);
    $invitee_ids = $stmt_invitees->fetchAll(PDO::FETCH_COLUMN);

    // Kombiniere alle IDs und entferne Duplikate
    $user_ids_to_fetch = array_merge($user_ids_to_fetch, $inviter_ids, $invitee_ids);
    $user_ids_to_fetch = array_unique($user_ids_to_fetch);

    if (empty($user_ids_to_fetch)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit();
    }

    // 2. Alle Triplogs für diese Benutzer abrufen
    // Sicherstellen, dass die IDs als Integer behandelt werden, um SQL-Injection zu vermeiden
    $user_ids_to_fetch = array_map('intval', $user_ids_to_fetch);
    $placeholders = implode(',', array_fill(0, count($user_ids_to_fetch), '?'));

    $sql = "SELECT t.id, t.user_id, t.title, t.remember_text, t.created_at, u.firstname 
            FROM triplogs t
            JOIN users u ON t.user_id = u.id
            WHERE t.user_id IN ($placeholders) 
            ORDER BY t.created_at ASC";

    $stmt_triplogs = $pdo->prepare($sql);
    $stmt_triplogs->execute(array_values($user_ids_to_fetch));
    $triplogs = $stmt_triplogs->fetchAll(PDO::FETCH_ASSOC);

    if (empty($triplogs)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit();
    }

    $triplog_ids = array_column($triplogs, 'id');

    // 3. Alle zugehörigen Medien abrufen, nur wenn Triplogs vorhanden sind
    $all_media = [];
    if (!empty($triplog_ids)) {
        $media_placeholders = implode(',', array_fill(0, count($triplog_ids), '?'));
        $stmt_media = $pdo->prepare("SELECT triplog_id, file_url, file_type, gps_latitude, gps_longitude FROM triplog_media WHERE triplog_id IN ($media_placeholders)");
        $stmt_media->execute($triplog_ids);
        $all_media = $stmt_media->fetchAll(PDO::FETCH_ASSOC);
    }

    // Medien den Triplogs zuordnen
    $media_by_triplog = [];
    foreach ($all_media as $media) {
        $media['full_url'] = $baseUrl . '/' . $media['file_url'];
        $media_by_triplog[$media['triplog_id']][] = $media;
    }

    // Triplogs mit Medien kombinieren
    foreach ($triplogs as &$triplog) {
        $triplog['media'] = $media_by_triplog[$triplog['id']] ?? [];
    }

    echo json_encode(['success' => true, 'data' => $triplogs]);

} catch (PDOException $e) { // Spezifischer für Datenbankfehler
    http_response_code(500);
    // Detailliertere Fehlermeldung für die Entwicklung (kann für Produktion entfernt werden)
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Allgemeiner Serverfehler: ' . $e->getMessage()]);
}
