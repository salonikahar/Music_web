<?php
require_once 'auth_check.php';
require_once '../config/db.php';

$error = '';
$id = (int)($_GET['id'] ?? 0);

/* ================= FETCH ALBUM ================= */
$stmt = $pdo->prepare("SELECT * FROM albums WHERE id=?");
$stmt->execute([$id]);
$album = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$album) {
    header('Location: albums.php?error=not_found');
    exit;
}

/* ================= UPDATE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $display_type = $_POST['display_type'] ?? 'color';
    $bg_color = $_POST['background_color'] ?? '#1DB954';

    $image_path = $album['image_path']; // keep existing by default

    if ($name === '') {
        $error = "Album name is required.";
    }

    /* ================= IMAGE MODE ================= */
    if (!$error && $display_type === 'image') {

        if (!empty($_FILES['image']['name'])) {

            $allowed = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $error = "Only JPG, PNG, WEBP allowed.";
            } else {

                $uploadDir = __DIR__ . '/../uploads/albums/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                /* delete old image */
                if (!empty($album['image_path'])) {
                    $old = __DIR__ . '/../' . ltrim($album['image_path'], '/');
                    if (file_exists($old)) unlink($old);
                }

                $filename = 'album_' . uniqid() . '.' . $ext;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $image_path = '/uploads/albums/' . $filename;
                } else {
                    $error = "Failed to upload image.";
                }
            }
        }

    } else {
        /* ================= COLOR MODE ================= */

        /* delete old image if switching from image → color */
        if ($album['display_type'] === 'image' && !empty($album['image_path'])) {
            $old = __DIR__ . '/../' . ltrim($album['image_path'], '/');
            if (file_exists($old)) unlink($old);
        }

        $image_path = '';
    }

    /* ================= UPDATE DB ================= */
    if (!$error) {
        try {
            $stmt = $pdo->prepare("
                UPDATE albums 
                SET name=?, description=?, display_type=?, bg_color=?, image_path=? 
                WHERE id=?
            ");
            $stmt->execute([
                $name,
                $description,
                $display_type,
                $bg_color,
                $image_path,
                $id
            ]);

            header('Location: albums.php?msg=updated');
            exit;

        } catch (PDOException $e) {
            $error = "Database error. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Album</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-theme.css">
<style>
body { background:#121212; color:#fff; }
.main-content { margin-left:240px; padding:24px; }

.form-control {
    background:#181818;
    border:1px solid #282828;
    color:#fff;
}

.btn-primary { background:#1DB954; border:none; }

.album-preview {
    width:160px;
    height:160px;
    border-radius:10px;
    object-fit:cover;
    background:#282828;
    box-shadow:0 8px 24px rgba(0,0,0,.6);
}

.color-preview {
    width:160px;
    height:160px;
    border-radius:10px;
    border:1px solid #333;
}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

<h2>Edit Album</h2>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="mt-4">

    <div class="mb-3">
        <label class="form-label">Album Name *</label>
        <input type="text" name="name" class="form-control"
               value="<?= htmlspecialchars($album['name']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($album['description']) ?></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">Display Type *</label>
        <select name="display_type" class="form-control" onchange="toggleFields()">
            <option value="color" <?= $album['display_type']==='color'?'selected':'' ?>>Color Background</option>
            <option value="image" <?= $album['display_type']==='image'?'selected':'' ?>>Album Image</option>
        </select>
    </div>

    <!-- COLOR -->
    <div class="mb-3" id="colorField">
        <label class="form-label">Background Color</label>
        <input type="color" name="background_color"
               value="<?= htmlspecialchars($album['bg_color'] ?? '#1DB954') ?>"
               class="form-control form-control-color">

        <div class="color-preview mt-2"
             style="background:<?= htmlspecialchars($album['bg_color'] ?? '#1DB954') ?>"></div>
    </div>

    <!-- IMAGE -->
    <div class="mb-3" id="imageField">
        <label class="form-label">Album Image</label><br>

        <?php if ($album['image_path']): ?>
            <img src="/spotify-clone-new/spotify-clone<?= htmlspecialchars($album['image_path']) ?>" class="album-preview mb-2">
        <?php else: ?>
            <div class="text-muted mb-2">No image uploaded</div>
        <?php endif; ?>

        <input type="file" name="image" class="form-control" accept="image/*">
        <small class="text-muted">JPG, PNG, WEBP only</small>
    </div>

    <button class="btn btn-primary">Update Album</button>
    <a href="albums.php" class="btn btn-secondary ms-2">Cancel</a>

</form>

</div>

<script>
function toggleFields() {
    const type = document.querySelector('[name="display_type"]').value;
    document.getElementById('colorField').style.display = type === 'color' ? 'block' : 'none';
    document.getElementById('imageField').style.display = type === 'image' ? 'block' : 'none';
}
toggleFields();
</script>

</body>
</html>


