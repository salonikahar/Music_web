<?php
header('Content-Type: application/json');
require_once '../config/db.php';

try {
    $stmt = $pdo->prepare("
        SELECT s.*, a.name as album_name, a.image_path as album_image, ar.name as artist_name
        FROM songs s
        LEFT JOIN albums a ON s.album_id = a.id
        LEFT JOIN artists ar ON s.artist_id = ar.id
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($songs);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch songs']);
}
?>