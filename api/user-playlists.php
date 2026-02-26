<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get most played songs for the user
    $stmt = $pdo->prepare("
        SELECT
            songs.id,
            songs.title,
            songs.file_path,
            songs.image_path AS cover,
            artists.name AS artist_name,
            COUNT(play_history.id) as play_count
        FROM play_history
        JOIN songs ON play_history.song_id = songs.id
        LEFT JOIN artists ON songs.artist_id = artists.id
        WHERE play_history.user_id = ?
        GROUP BY songs.id
        ORDER BY play_count DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $mostPlayedSongs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'most_played' => $mostPlayedSongs
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
