<?php
require_once '../config/db.php';
$BASE_URL = '/Spotify-clone-new/Spotify-clone';

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$sql = "
  SELECT 
    songs.id,
    songs.title,
    songs.file_path,
    songs.image_path AS cover,
    artists.name AS artist_name
  FROM songs
  LEFT JOIN artists ON songs.artist_id = artists.id
  ORDER BY songs.id DESC
  LIMIT 10 OFFSET $offset
";

$stmt = $pdo->query($sql);

foreach ($stmt as $song): ?>
  <div class="hit-card" data-song='<?= json_encode($song) ?>' onclick="playHit(this)">
    <img src="<?= $BASE_URL . ($song['cover'] ?: '/assets/default-song.png') ?>">
    <div class="play-btn"><i class="bi bi-play-fill"></i></div>
    <a href="<?= $BASE_URL ?>/api/download.php?song_id=<?= $song['id'] ?>" class="download-btn"><i class="bi bi-download"></i></a>
    <div class="hit-title"><?= htmlspecialchars($song['title']) ?></div>
    <div class="hit-desc"><?= htmlspecialchars($song['artist_name'] ?? 'Unknown Artist') ?></div>
  </div>
<?php endforeach; ?>
