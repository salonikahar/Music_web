<?php
require_once '../config/db.php';

header('Content-Type: text/html');

$BASE_URL = '..';
$offset = (int)($_GET['offset'] ?? 0);

try {
    $stmt = $pdo->query("
        SELECT id, name, image_path
        FROM artists
        ORDER BY RAND()
        LIMIT $offset, 5
    ");

    $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($artists as $artist) {
        echo '<div class="artist-card">';
        echo '<img src="' . $BASE_URL . ($artist['image_path'] ?: '/assets/default-artist.png') . '">';
        echo '<div class="artist-name">' . htmlspecialchars($artist['name']) . '</div>';
        echo '</div>';
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo '';
}
