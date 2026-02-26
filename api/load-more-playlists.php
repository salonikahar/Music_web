<?php
require_once '../config/db.php';

header('Content-Type: text/html');

$BASE_URL = '..';
$offset = (int)($_GET['offset'] ?? 0);

$stmt = $pdo->query("
    SELECT id, name, image_path
    FROM playlists
    ORDER BY created_at DESC
    LIMIT $offset, 5
");

$playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($playlists as $pl) {
    echo '<a href="pages/playlist.php?id='.$pl['id'].'" class="playlist-card">';
    echo '<img src="'.$BASE_URL.($pl['image_path'] ?: '/assets/default-playlist.png').'">';
    echo '<div class="playlist-name">'.htmlspecialchars($pl['name']).'</div>';
    echo '</a>';
}
