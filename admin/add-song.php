<?php
require_once 'auth_check.php';
require_once '../config/db.php';

/* ✅ getID3 */
require_once __DIR__ . '/../lib/getid3/getid3/getid3.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $artist_id = (int) ($_POST['artist_id'] ?? 0);
    $album_id = !empty($_POST['album_id']) ? (int) $_POST['album_id'] : null;

    $file_path = '';
    $image_path = '';
    $duration = '0:00';

    /* ================= VALIDATION ================= */
    if ($title === '') {
        $error = "Song title is required.";
    } elseif ($artist_id === 0) {
        $error = "Please select an artist.";
    }

    /* ================= AUDIO UPLOAD + DURATION ================= */
    if (!$error && !empty($_FILES['audio_file']['name'])) {

        $uploadDir = '../uploads/songs/';
        $webPath = '/uploads/songs/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if ($_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
            $error = "Audio upload failed.";
        } else {

            $ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['mp3', 'wav', 'ogg'];

            if (!in_array($ext, $allowed)) {
                $error = "Only MP3, WAV or OGG files allowed.";
            } else {

                $filename = uniqid('song_') . '.' . $ext;
                $fullPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $fullPath)) {

                    /* ✅ Web path for DB */
                    $file_path = $webPath . $filename;

                    /* ✅ READ DURATION USING getID3 */
                    $getID3 = new getID3;
                    $info = $getID3->analyze($fullPath);

                    if (!empty($info['playtime_seconds'])) {
                        $seconds = (int) $info['playtime_seconds'];
                        $minutes = floor($seconds / 60);
                        $remaining = $seconds % 60;
                        $duration = $minutes . ':' . str_pad($remaining, 2, '0', STR_PAD_LEFT);
                    }

                } else {
                    $error = "Failed to save audio file.";
                }
            }
        }

    } elseif (!$error) {
        $error = "Audio file is required.";
    }

    /* ================= OPTIONAL COVER IMAGE ================= */
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

    /* ================= INSERT ================= */
    if (!$error) {

        $stmt = $pdo->prepare("
            INSERT INTO songs
            (title, artist_id, album_id, file_path, duration, image_path)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $title,
            $artist_id,
            $album_id,
            $file_path,
            $duration,
            $image_path
        ]);

        header('Location: songs.php?msg=added');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Song</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-theme.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <script src="../assets/js/notification.js"></script>
    <style>
        body {
            background: #121212;
            color: #fff;
            margin: 0;
        }

        .main-content {
            margin-left: 240px;
            padding: 24px;
        }

        .form-control,
        select {
            background: #181818;
            border: 1px solid #282828;
            color: white;
        }

        .form-control:focus {
            background: #181818;
            color: white;
        }

        .btn-primary {
            background: #1DB954;
            border: none;
        }

        label {
            font-weight: 500;
        }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2>Add New Song</h2>

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
                <input type="text" name="title" class="form-control" required>
            </div>

            <!-- ARTIST -->
            <div class="mb-3">
                <label class="form-label">Artist *</label>
                <select name="artist_id" class="form-control" required>
                    <option value="">Select Artist</option>
                    <?php
                    $artists = $pdo->query("SELECT id, name FROM artists ORDER BY name")->fetchAll();
                    foreach ($artists as $a):
                        ?>
                        <option value="<?= $a['id'] ?>">
                            <?= htmlspecialchars($a['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- ALBUM -->
            <div class="mb-3">
                <label class="form-label">Album (Optional)</label>
                <select name="album_id" class="form-control">
                    <option value="">None</option>
                    <?php
                    $albums = $pdo->query("SELECT id, name FROM albums ORDER BY name")->fetchAll();
                    foreach ($albums as $al):
                        ?>
                        <option value="<?= $al['id'] ?>">
                            <?= htmlspecialchars($al['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- AUDIO -->
            <div class="mb-3">
                <label class="form-label">Audio File *</label>
                <input type="file" name="audio_file" class="form-control" accept="audio/*" required>
            </div>

            <!-- COVER -->
            <div class="mb-3">
                <label class="form-label">Song Cover Image (Optional)</label>
                <input type="file" name="cover_image" class="form-control" accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary">Save Song</button>
            <a href="songs.php" class="btn btn-secondary ms-2">Cancel</a>

        </form>
    </div>

</body>

</html>


