<!-- admin/edit-artist.php -->
<?php
require_once 'auth_check.php';
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);

/* ================= FETCH ARTIST ================= */
$stmt = $pdo->prepare("SELECT * FROM artists WHERE id = ?");
$stmt->execute([$id]);
$artist = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artist) {
    header('Location: artists.php');
    exit;
}

/* ================= UPDATE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $bio  = trim($_POST['bio']);

    // Keep old image unless replaced
    $image_path = $artist['image_path'];

    if (!empty($_FILES['image']['name'])) {

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            die('Invalid image format');
        }

        $uploadDir = '../uploads/artists/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = uniqid('artist_', true) . '.' . $ext;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
            $image_path = '/uploads/artists/' . $filename;
        }
    }

    $stmt = $pdo->prepare("
        UPDATE artists 
        SET name = ?, bio = ?, image_path = ? 
        WHERE id = ?
    ");

    $stmt->execute([$name, $bio, $image_path, $id]);

    header('Location: artists.php?msg=updated');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Artist</title>

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
        img.preview {
            width:120px;
            height:120px;
            object-fit:cover;
            border-radius:8px;
            margin:8px 0;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h2>Edit Artist</h2>

    <form method="POST" enctype="multipart/form-data" class="mt-4">

        <div class="mb-3">
            <label class="form-label">Artist Name *</label>
            <input type="text" name="name" class="form-control"
                   value="<?= htmlspecialchars($artist['name']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Bio</label>
            <textarea name="bio" class="form-control" rows="3"><?= htmlspecialchars($artist['bio']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Current Image</label><br>

            <?php if (!empty($artist['image_path'])): ?>
                <img src="/spotify-clone-new/spotify-clone<?= htmlspecialchars($artist['image_path']) ?>" class="preview">
            <?php else: ?>
                <div class="text-muted">No image uploaded</div>
            <?php endif; ?>

            <label class="form-label mt-2">Replace Image (optional)</label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>

        <button class="btn btn-primary">Update Artist</button>
        <a href="artists.php" class="btn btn-secondary ms-2">Cancel</a>

    </form>
</div>

</body>
</html>


