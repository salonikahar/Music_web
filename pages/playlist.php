<?php
session_start();
require_once '../config/db.php';

$BASE_URL = '/Spotify-clone-new/Spotify-clone';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$playlistId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify playlist ownership
$stmt = $pdo->prepare("SELECT id, name FROM playlists WHERE id = ? AND user_id = ?");
$stmt->execute([$playlistId, $userId]);
$playlist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$playlist) {
    die('Playlist not found or access denied');
}

// Handle adding songs to playlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_song'])) {
    $songId = (int)$_POST['song_id'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO user_playlist_songs (playlist_id, song_id) VALUES (?, ?)");
    $stmt->execute([$playlistId, $songId]);
    header("Location: playlist.php?id=$playlistId");
    exit;
}

// Handle removing songs from playlist
if (isset($_GET['remove_song'])) {
    $songId = (int)$_GET['remove_song'];
    $stmt = $pdo->prepare("DELETE FROM user_playlist_songs WHERE playlist_id = ? AND song_id = ?");
    $stmt->execute([$playlistId, $songId]);
    header("Location: playlist.php?id=$playlistId");
    exit;
}

// Fetch playlist songs
$stmt = $pdo->prepare("
    SELECT s.id, s.title, s.duration, s.file_path, s.image_path AS cover, a.name AS artist_name
    FROM songs s
    LEFT JOIN artists a ON s.artist_id = a.id
    INNER JOIN user_playlist_songs ups ON s.id = ups.song_id
    WHERE ups.playlist_id = ?
    ORDER BY ups.added_at ASC
");
$stmt->execute([$playlistId]);
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get playlist image (first song's cover or default)
$playlistImage = '../assets/default-playlist.png';
if (!empty($songs)) {
    $playlistImage = $BASE_URL . '/' . $songs[0]['cover'];
}

// Fetch all songs for adding to playlist
$stmt = $pdo->query("
    SELECT s.id, s.title, a.name AS artist_name
    FROM songs s
    LEFT JOIN artists a ON s.artist_id = a.id
    ORDER BY s.title ASC
");
$allSongs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($playlist['name']) ?> – Playlist</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/player.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/playlist.css">
</head>

<body>

    <?php include '../partials/sidebar.php'; ?>

    <!-- ================= MAIN CONTENT ================= -->
    <div class="main-content">

        <!-- Playlist Header -->
        <div class="playlist-hero">
            <img class="playlist-cover" src="<?= $playlistImage ?>">
            <div class="playlist-meta">
                <span class="playlist-type">Playlist</span>
                <h1 class="playlist-title"><?= htmlspecialchars($playlist['name']) ?></h1>
                <div class="playlist-info">
                    <span><?= count($songs) ?> songs</span>
                </div>
            </div>
        </div>



        <div class="add-song-btn">
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSongModal">
        <i class="bi bi-plus-lg"></i> Add Songs
    </button>
</div>


        <!-- Song List -->
        <div class="spotify-table">

    <div class="spotify-table-head">
        <div>#</div>
        <div>Title</div>
        <div></div>
        <div><i class="bi bi-clock"></i></div>
        <div></div>
    </div>

    <?php foreach ($songs as $i => $song): ?>
        <div class="spotify-row"
             data-song='<?= json_encode($song) ?>'
             onclick="playPlaylistSong(this)">

            <div class="col-index">
                <span class="index"><?= $i + 1 ?></span>
                <i class="bi bi-play-fill play-hover"></i>
            </div>

            <div class="col-title">
                <img src="<?= $BASE_URL . '/' . $song['cover'] ?>" class="song-thumb">
                <div>
                    <div class="song-name"><?= htmlspecialchars($song['title']) ?></div>
                    <div class="song-artist"><?= htmlspecialchars($song['artist_name']) ?></div>
                </div>
            </div>

            <div></div>

            <div class="col-duration">
                <?= htmlspecialchars($song['duration'] ?? '—') ?>
            </div>

            <div class="col-actions">
                <a href="?id=<?= $playlistId ?>&remove_song=<?= $song['id'] ?>"
                   onclick="return confirm('Remove this song?')">
                    <i class="bi bi-trash"></i>
                </a>
                <a href="api/download.php?song_id=<?= $song['id'] ?>" target="_blank">
                    <i class="bi bi-download"></i>
                </a>
            </div>
        </div>
    <?php endforeach; ?>

</div>


    </div>

    <?php include '../partials/player.php'; ?>

    <!-- Add Song Modal -->
    <div class="modal fade" id="addSongModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title">Add Songs to Playlist</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group">
                        <?php foreach ($allSongs as $song): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="song_id" value="<?= $song['id'] ?>">
                                <button type="submit" name="add_song" class="list-group-item list-group-item-action bg-dark text-white border-secondary">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($song['title']) ?></strong>
                                            <small class="text-muted">by <?= htmlspecialchars($song['artist_name']) ?></small>
                                        </div>
                                        <i class="bi bi-plus-circle"></i>
                                    </div>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const BASE_URL = "<?= $BASE_URL ?>";

        function playPlaylist() {
            const rows = document.querySelectorAll(".song-row[data-song]");
            if (!rows.length) return;
            let songs = Array.from(rows).map(r => JSON.parse(r.dataset.song));
            currentPlaylist = songs;
            loadSong(currentPlaylist[0]);
        }

        function toggleShuffle() {
            isShuffle = !isShuffle;
            const btn = document.getElementById('shuffle-btn');
            btn.classList.toggle('active', isShuffle);
        }

        function toggleRepeat() {
            isRepeat = !isRepeat;
            const btn = document.getElementById('repeat-btn');
            btn.classList.toggle('active', isRepeat);
        }
    </script>
    <script src="../assets/js/player.js"></script>
    <script src="../assets/js/sidebar.js?v=2"></script>

</body>

</html>
