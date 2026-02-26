<?php
require_once '../config/db.php';

header('Content-Type: text/html');

$BASE_URL = '..';
$offset = (int)($_GET['offset'] ?? 0);

try {
    $stmt = $pdo->prepare("
        SELECT
            songs.id,
            songs.title,
            songs.file_path,
            songs.image_path AS cover,
            artists.name AS artist_name
        FROM songs
        LEFT JOIN artists ON songs.artist_id = artists.id
        ORDER BY songs.id DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([10, $offset]);

    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($songs as $song) {
        $songJson = htmlspecialchars(json_encode($song), ENT_QUOTES, 'UTF-8');

        echo '<div class="hit-card" data-song=\''.$songJson.'\' onclick="playHit(this)">';
        echo '<img src="'.$BASE_URL.($song['cover'] ?: '/assets/default-song.png').'">';
        echo '<div class="play-btn"><i class="bi bi-play-fill"></i></div>';
        echo '<div class="hit-title">'.htmlspecialchars($song['title']).'</div>';
        echo '<div class="hit-desc">'.htmlspecialchars($song['artist_name'] ?? 'Unknown Artist').'</div>';
        echo '</div>';
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo '';
}
