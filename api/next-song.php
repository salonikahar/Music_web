<?php
require_once '../config/db.php'; // Adjust path as needed

// Basic security, check if it's an AJAX request
if (
    !isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest'
) {
    http_response_code(403);
    exit('Direct access not allowed.');
}

header('Content-Type: application/json');

$current_song_id = $_GET['current_song_id'] ?? 0;
$direction = $_GET['direction'] ?? 'next';

if ($direction === 'next') {
    // Find the next song with ID greater than current
    $query = "SELECT
                s.id,
                s.title,
                s.file_path,
                s.image_path as cover,
                a.name as artist_name
            FROM songs s
            LEFT JOIN artists a ON s.artist_id = a.id
            WHERE s.id > ?
            ORDER BY s.id ASC
            LIMIT 1";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$current_song_id]);
    $song = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no next song, wrap to the first song
    if (!$song) {
        $query = "SELECT
                    s.id,
                    s.title,
                    s.file_path,
                    s.image_path as cover,
                    a.name as artist_name
                FROM songs s
                LEFT JOIN artists a ON s.artist_id = a.id
                ORDER BY s.id ASC
                LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $song = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} else { // prev
    // Find the previous song with ID less than current
    $query = "SELECT
                s.id,
                s.title,
                s.file_path,
                s.image_path as cover,
                a.name as artist_name
            FROM songs s
            LEFT JOIN artists a ON s.artist_id = a.id
            WHERE s.id < ?
            ORDER BY s.id DESC
            LIMIT 1";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$current_song_id]);
    $song = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no previous song, wrap to the last song
    if (!$song) {
        $query = "SELECT
                    s.id,
                    s.title,
                    s.file_path,
                    s.image_path as cover,
                    a.name as artist_name
                FROM songs s
                LEFT JOIN artists a ON s.artist_id = a.id
                ORDER BY s.id DESC
                LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $song = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($song) {
    echo json_encode($song);
} else {
    // This would only happen if there are 0 songs in the database
    http_response_code(404);
    echo json_encode(['error' => 'No songs found.']);
}
