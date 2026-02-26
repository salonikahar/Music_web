<!-- admin/edit-song.php -->
<?php
require_once 'auth_check.php';
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);

/* ================= FETCH SONG ================= */
$stmt = $pdo->prepare("SELECT * FROM songs WHERE id = ?");
$stmt->execute([$id]);
$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
    header('Location: songs.php');
    exit;
}

/* ================= DATA ================= */
$artists = $pdo->query("SELECT id, name FROM artists ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$albums  = $pdo->query("SELECT id, name FROM albums ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$error = '';

/* ================= UPDATE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title'] ?? '');
    $artist_id = (int)($_POST['artist_id'] ?? 0);
    $album_id  = !empty($_POST['album_id']) ? (int)$_POST['album_id'] : null;
    $duration  = trim($_POST['duration'] ?? '');

    $file_path  = $song['file_path'];   // keep existing
    $image_path = $song['image_path'];  // keep existing

    if ($title === '') {
        $error = "Song title is required.";
    } elseif ($artist_id === 0) {
        $error = "Please select an artist.";
    }

    /* ================= AUDIO UPDATE ================= */
    if (!$error && !empty($_FILES['audio_file']['name'])) {
        $uploadDir = '../uploads/songs/';
        $webPath   = '/uploads/songs/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['mp3', 'wav', 'ogg'];

        if (!in_array($ext, $allowed)) {
            $error = "Only MP3, WAV or OGG files allowed.";
        } else {
            $filename = uniqid('song_') . '.' . $ext;
            if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $uploadDir . $filename)) {
                $file_path = $webPath . $filename;
            } else {
                $error = "Failed to upload audio file.";
            }
        }
    }

    /* ================= COVER IMAGE UPDATE ================= */
    if (!$error && !empty($_FILES['cover_image']['name'])) {
        $imgDir = '../uploads/song-covers/';
        $imgWeb = '/uploads/song-covers/';

        if (!is_dir($imgDir)) {
            mkdir($imgDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $allowedImg = ['jpg', 'jpeg', 'png'];

        if (in_array($ext, $allowedImg)) {
            $imgName = uniqid('cover_') . '.' . $ext;
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $imgDir . $imgName)) {
                $image_path = $imgWeb . $imgName;
            }
        }
    }

    /* ================= SAVE ================= */
    if (!$error) {
        $update = $pdo->prepare("
            UPDATE songs SET
                title = ?,
                artist_id = ?,
                album_id = ?,
                file_path = ?,
                duration = ?,
                image_path = ?
            WHERE id = ?
        ");
        $update->execute([
            $title,
            $artist_id,
            $album_id,
            $file_path,
            $duration,
            $image_path,
            $id
        ]);

        header('Location: songs.php?msg=updated');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Song</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-theme.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <script src="../assets/js/notification.js"></script>
<style>
body { background:#121212; color:white; margin:0; }
.main-content { margin-left:240px; padding:24px; }
.form-control, select {
    background:#181818;
    border:1px solid #282828;
    color:white;
}
.form-control:focus { background:#181818; color:white; }
.btn-primary { background:#1DB954; border:none; }
.song-cover {
    width:120px;
    height:120px;
    object-fit:cover;
    border-radius:6px;
    border:1px solid #333;
}
</style>
</head>

<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h2>Edit Song</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('error', '<?= addslashes(htmlspecialchars($error)) ?>');
            });
        </script>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="mt-4">

        <!-- TITLE -->
        <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control"
                   value="<?= htmlspecialchars($song['title']) ?>" required>
        </div>

        <!-- ARTIST -->
        <div class="mb-3">
            <label class="form-label">Artist *</label>
            <select name="artist_id" class="form-control" required>
                <option value="">Select Artist</option>
                <?php foreach ($artists as $artist): ?>
                    <option value="<?= $artist['id'] ?>"
                        <?= $artist['id'] == $song['artist_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($artist['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ALBUM -->
        <div class="mb-3">
            <label class="form-label">Album (Optional)</label>
            <select name="album_id" class="form-control">
                <option value="">None</option>
                <?php foreach ($albums as $album): ?>
                    <option value="<?= $album['id'] ?>"
                        <?= $album['id'] == $song['album_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($album['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- DURATION -->
        <div class="mb-3">
            <label class="form-label">Duration</label>
            <input type="text" name="duration" class="form-control"
                   value="<?= htmlspecialchars($song['duration']) ?>">
        </div>

        <!-- CURRENT COVER -->
        <div class="mb-3">
            <label class="form-label">Current Song Cover</label><br>
            <?php if (!empty($song['image_path'])): ?>
                <img src="/Spotify-clone-new/Spotify-clone<?= htmlspecialchars($song['image_path']) ?>" class="song-cover">
            <?php else: ?>
                <span class="text-muted">No cover image</span>
            <?php endif; ?>
        </div>

        <!-- COVER UPLOAD -->
        <div class="mb-3">
            <label class="form-label">Replace Cover Image (optional)</label>
            <input type="file" name="cover_image" class="form-control" accept="image/*">
        </div>

        <!-- CURRENT AUDIO -->
        <div class="mb-3">
            <label class="form-label">Current Audio</label>
            <?php if (!empty($song['file_path'])): ?>
                <audio controls style="width:100%;">
                    <source src="/spotify-clone-new/spotify-clone<?= htmlspecialchars($song['file_path']) ?>">
                </audio>
            <?php else: ?>
                <p class="text-muted">No audio uploaded</p>
            <?php endif; ?>
        </div>

        <!-- AUDIO UPLOAD -->
        <div class="mb-3">
            <label class="form-label">Replace Audio File (optional)</label>
            <input type="file" name="audio_file" class="form-control" accept="audio/*">
        </div>

        <button type="submit" class="btn btn-primary">Update Song</button>
        <a href="songs.php" class="btn btn-secondary ms-2">Cancel</a>

    </form>
</div>

</body>
</html>


