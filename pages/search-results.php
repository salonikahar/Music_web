<?php
require_once '../config/db.php';
require_once '../api/saavn.php';

$BASE_URL = '/Spotify-clone-new/Spotify-clone';
$DEFAULT_IMG = 'data:image/gif;base64,R0lGODlhAQABAIAAAMLCwgAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==';

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    header('Location: index.php');
    exit;
}

/* ================= LOCAL SONGS ================= */
$songStmt = $pdo->prepare("
  SELECT
    songs.id,
    songs.title,
    songs.file_path,
    songs.image_path AS cover,
    artists.name AS artist_name
  FROM songs
  LEFT JOIN artists ON songs.artist_id = artists.id
  WHERE songs.title LIKE :q
     OR artists.name LIKE :q
  LIMIT 20
");
$songStmt->execute(['q' => "%$q%"]);
$songs = $songStmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= ALBUMS ================= */
$albumStmt = $pdo->prepare("
  SELECT id, name, image_path, bg_color, display_type
  FROM albums
  WHERE name LIKE :q
  LIMIT 10
");
$albumStmt->execute(['q' => "%$q%"]);
$albums = $albumStmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= ARTISTS ================= */
$artistStmt = $pdo->prepare("
  SELECT id, name, image_path
  FROM artists
  WHERE name LIKE :q
  LIMIT 10
");
$artistStmt->execute(['q' => "%$q%"]);
$artists = $artistStmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= SAAVN SONGS ================= */
/* Temporarily disabled online song functionality
$saavnResponse = saavn_api("search/songs?query=" . urlencode($q));
$saavnSongs = $saavnResponse['data']['results'] ?? [];
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results for "<?= htmlspecialchars($q) ?>"</title>

    <link rel="icon" href="../assets/default-playlist.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/player.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=2">
    <link rel="stylesheet" href="../assets/css/modern.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
</head>

<body>

<script>
    const BASE_URL = "<?= $BASE_URL ?>";
</script>

<?php include '../partials/sidebar.php'; ?>
<div class="overlay d-md-none" id="overlay" onclick="toggleSidebar()"></div>

<!-- ================= MAIN CONTENT ================= -->
<div class="main-content">

    <div class="search-header">
        <div class="search-query">Search Results</div>
        <h1 class="search-title"><?= htmlspecialchars($q) ?></h1>
    </div>

    <!-- ================= LOCAL SONGS ================= -->
    <?php if (!empty($songs)): ?>
        <div class="search-section">
            <h2 class="search-section-title">Songs</h2>
            <div class="scroll-row">
                <div class="today-hits horizontal-scroll">
                    <?php foreach ($songs as $song): ?>
                        <div class="hit-card"
                             data-song='<?= json_encode($song, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'
                             onclick="playHit(this)">
                            <img src="<?= $song['cover'] ? $BASE_URL . $song['cover'] : $DEFAULT_IMG ?>">
                            <div class="play-btn"><i class="bi bi-play-fill"></i></div>
                            <div class="hit-title"><?= htmlspecialchars($song['title']) ?></div>
                            <div class="hit-desc"><?= htmlspecialchars($song['artist_name'] ?? 'Unknown Artist') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================= SAAVN ONLINE SONGS ================= -->
    <?php if (!empty($saavnSongs)): ?>
        <div class="search-section">
            <h2 class="search-section-title">Online Songs</h2>
            <div class="scroll-row">
                <div class="today-hits horizontal-scroll">
                    <?php foreach ($saavnSongs as $song):

                        $songData = [
                            "id" => "saavn_" . $song['id'],
                            "title" => $song['name'],
                            "artist_name" => $song['primaryArtists'],
                            "file_path" => $song['downloadUrl'][4]['link'],
                            "cover" => $song['image'][1]['link']
                        ];
                    ?>
                        <div class="hit-card"
                             data-song='<?= json_encode($songData, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'
                             onclick="playHit(this)">
                            <img src="<?= $songData['cover'] ?>">
                            <div class="play-btn"><i class="bi bi-play-fill"></i></div>
                            <div class="hit-title"><?= htmlspecialchars($songData['title']) ?></div>
                            <div class="hit-desc">Online • <?= htmlspecialchars($songData['artist_name']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================= ALBUMS ================= -->
    <?php if (!empty($albums)): ?>
        <div class="search-section">
            <h2 class="search-section-title">Albums</h2>
            <div class="scroll-row">
                <div class="horizontal-scroll">
                    <?php foreach ($albums as $album):
                        $bg = ($album['display_type'] === 'image' && $album['image_path'])
                            ? "url('{$BASE_URL}{$album['image_path']}')"
                            : "linear-gradient(135deg, {$album['bg_color']}, #000)";
                    ?>
                        <a href="album.php?id=<?= $album['id'] ?>" class="spotify-editorial-card"
                           style="background: <?= $bg ?>;">
                            <div class="editorial-play"><i class="bi bi-play-fill"></i></div>
                            <div class="editorial-content">
                                <div class="editorial-title"><?= htmlspecialchars($album['name']) ?></div>
                                <div class="editorial-sub">Album</div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================= ARTISTS ================= -->
    <?php if (!empty($artists)): ?>
        <div class="search-section">
            <h2 class="search-section-title">Artists</h2>
            <div class="scroll-row">
                <div class="artist-grid horizontal-scroll">
                    <?php foreach ($artists as $artist): ?>
                        <div class="artist-card">
                            <img src="<?= $artist['image_path'] ? $BASE_URL . $artist['image_path'] : $DEFAULT_IMG ?>">
                            <div class="artist-name"><?= htmlspecialchars($artist['name']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================= EMPTY STATE ================= -->
    <?php if (empty($songs) && empty($albums) && empty($artists)): ?>
        <div class="search-results-empty">
            <div class="search-results-empty-icon">
                <i class="bi bi-search"></i>
            </div>
            <div class="search-results-empty-text">
                No results found for "<?= htmlspecialchars($q) ?>"
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include '../partials/player.php'; ?>

<audio id="audio-player"></audio>

<script src="../assets/js/player.js"></script>
<script src="../assets/js/sidebar.js?v=2"></script>

</body>
</html>


