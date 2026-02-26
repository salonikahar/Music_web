<?php
session_start();
require_once '../config/db.php';
require_once '../includes/premium_check.php';

$BASE_URL = '/Spotify-clone-new/Spotify-clone';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Check premium status
$userPremium = isPremiumUser($userId);
$_SESSION['is_premium'] = $userPremium ? 1 : 0;

/* ================= CREATE PLAYLIST ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_playlist'])) {
    $playlistName = trim($_POST['playlist_name']);

    if ($playlistName !== '') {
        $stmt = $pdo->prepare("INSERT INTO playlists (user_id, name) VALUES (?, ?)");
        $stmt->execute([$userId, $playlistName]);
    }

    header("Location: library.php");
    exit;
}

/* ================= DELETE PLAYLIST ================= */
if (isset($_POST['delete_playlist'])) {
    $playlistId = (int)$_POST['playlist_id'];

    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
    $stmt->execute([$playlistId, $userId]);

    if ($stmt->fetch()) {
        $pdo->prepare("DELETE FROM user_playlist_songs WHERE playlist_id = ?")
            ->execute([$playlistId]);

        $pdo->prepare("DELETE FROM playlists WHERE id = ?")
            ->execute([$playlistId]);
    }

    header("Location: library.php");
    exit;
}

/* ================= FETCH PLAYLISTS ================= */
$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.name,
        (
            SELECT s.image_path
            FROM user_playlist_songs ups
            JOIN songs s ON ups.song_id = s.id
            WHERE ups.playlist_id = p.id
            ORDER BY ups.added_at ASC
            LIMIT 1
        ) AS image_path
    FROM playlists p
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$userId]);
$playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Library</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/player.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/library.css">
</head>

<body>

<script>
    const BASE_URL = "<?= $BASE_URL ?>";
</script>

<?php include '../partials/sidebar.php'; ?>

<!-- ================= MAIN CONTENT ================= -->
<div class="main-content">

    <h1 class="section-title">Your Library</h1>

    <div class="mb-4">
        <button class="btn btn-primary" id="createPlaylistBtn">
            <i class="bi bi-plus-lg"></i> Create Playlist
        </button>
    </div>

    <?php if (!empty($playlists)): ?>
        <div class="spotify-library-grid">

            <?php foreach ($playlists as $pl): ?>
                <?php
                if (!empty($pl['image_path'])) {
                    $cleanPath = ltrim(str_replace(['../', './'], '', $pl['image_path']), '/');
                    $img = $BASE_URL . '/' . $cleanPath;
                } else {
                    $img = $BASE_URL . '/assets/default-playlist.png';
                }
                ?>

                <div class="library-card-wrapper">

                    <a href="playlist.php?id=<?= $pl['id'] ?>" class="library-card">
                        <div class="card-img">
                            <!-- ✅ IMAGE FIXED -->
                            <img src="<?= $img ?>" alt="<?= htmlspecialchars($pl['name']) ?>">

                            <div class="play-overlay">
                                <i class="bi bi-play-fill"></i>
                            </div>
                        </div>

                        <div class="card-title"><?= htmlspecialchars($pl['name']) ?></div>
                        <div class="card-subtitle">Playlist</div>
                    </a>

                    <!-- DELETE -->
                    <form method="POST"
                          class="playlist-menu"
                          onsubmit="return confirm('Delete this playlist permanently?')">
                        <input type="hidden" name="playlist_id" value="<?= $pl['id'] ?>">
                        <button type="submit" name="delete_playlist">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>

                </div>
            <?php endforeach; ?>

        </div>
    <?php else: ?>
        <p class="text-muted">You don't have any playlists yet. Create your first playlist!</p>
    <?php endif; ?>

</div>

<?php include '../partials/player.php'; ?>

<!-- CREATE PLAYLIST MODAL -->
<div class="modal fade" id="createPlaylistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title">Create New Playlist</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST">
                <div class="modal-body">
                    <label class="form-label">Playlist Name</label>
                    <input type="text" class="form-control" name="playlist_name" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_playlist" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/player.js"></script>
<script src="../assets/js/sidebar.js?v=2"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const createPlaylistBtn = document.getElementById('createPlaylistBtn');
    const isPremium = <?= $userPremium ? 'true' : 'false' ?>;

    createPlaylistBtn.addEventListener('click', function(e) {
        e.preventDefault();

        if (!isPremium) {
            // Show beautiful premium notification for non-premium users
            showNotification('warning',
                '<div style="line-height: 1.5;">' +
                '<strong>🎵 Premium Feature</strong><br>' +
                'Create unlimited playlists and organize your music library.<br>' +
                '<a href="../premium.php" style="color: #1db954; text-decoration: underline; font-weight: bold;">Upgrade to Premium</a> to unlock this feature!' +
                '</div>',
                8000 // Show for 8 seconds
            );
        } else {
            // Open modal for premium users
            const modal = new bootstrap.Modal(document.getElementById('createPlaylistModal'));
            modal.show();
        }
    });
});
</script>

</body>
</html>
