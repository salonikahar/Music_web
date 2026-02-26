<?php
require_once '../config/db.php';

$stmt = $pdo->prepare("
  SELECT
    songs.id,
    songs.title,
    songs.file_path,
    songs.image_path AS cover,
    artists.name AS artist_name
  FROM songs
  LEFT JOIN artists ON songs.artist_id = artists.id
  ORDER BY RAND()
  LIMIT 1
");

$stmt->execute();
$song = $stmt->fetch(PDO::FETCH_ASSOC);

if ($song) {
  header('Content-Type: application/json');
  echo json_encode($song);
} else {
  http_response_code(404);
  echo json_encode(['error' => 'No songs found']);
}
?>
