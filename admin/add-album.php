<!-- admin/add-album.php -->
<?php
require_once 'auth_check.php';
require_once '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $display_type = $_POST['display_type'] ?? 'color';
    $bg_color = $_POST['background_color'] ?? '#1DB954';
    $image_path = '';

    if (empty($name)) {
        $error = "Album name is required.";
    } else {
        // Handle image upload (only for 'image' display type)
        if ($display_type === 'image' && !empty($_FILES['image']['name'])) {
            $uploadDir = '../uploads/albums/';
            $webPath = '/uploads/albums/';

            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    $error = "Failed to create upload directory. Check permissions.";
                }
            }

            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = "Upload error: " . $_FILES['image']['error'];
            } else {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];

                if (!in_array($ext, $allowed)) {
                    $error = "Only JPG, PNG, GIF files allowed.";
                } else {
                    $filename = uniqid() . '.' . $ext;
                    $targetPath = $uploadDir . $filename;

                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $error = "Failed to save image. Check folder permissions.";
                    } else {
                        $image_path = $webPath . $filename;
                    }
                }
            }
        }

        // Save to database if no errors
        if (!$error) {
            try {
                $bg_color = $_POST['background_color'] ?? '#1DB954';

$stmt = $pdo->prepare("
INSERT INTO albums (name, description, display_type, bg_color, image_path)
VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$name, $description, $display_type, $bg_color, $image_path]);

                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = "Album '$name' already exists. Please choose a different name.";
                } else {
                    $error = "Database error. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Album</title>
    <!-- ✅ FIXED: Removed trailing spaces -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-theme.css">
    <style>
        body { background: #121212; color: white; margin: 0; padding: 0; }
        .main-content { margin-left: 240px; padding: 24px; }
        .form-control { background: #181818; border: 1px solid #282828; color: white; }
        .btn-primary { background: #1DB954; border: none; }
        .color-preview { width: 30px; height: 30px; border-radius: 4px; margin-right: 8px; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h2>Add New Album</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="mt-4">
        <div class="mb-3">
            <label class="form-label">Album Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Display Type *</label>
            <select name="display_type" class="form-control" required onchange="toggleFields()">
                <option value="color" <?= ($_POST['display_type'] ?? 'color') === 'color' ? 'selected' : '' ?>>Color Background</option>
                <option value="image" <?= ($_POST['display_type'] ?? '') === 'image' ? 'selected' : '' ?>>Album Image</option>
            </select>
        </div>
        <div class="mb-3" id="colorField">
            <label class="form-label">Background Color</label>
            <div class="d-flex align-items-center">
                <input type="color" name="background_color" class="form-control form-control-color" value="<?= htmlspecialchars($_POST['background_color'] ?? '#1DB954') ?>" title="Choose background color">
                <span class="ms-2" style="font-size: 14px;"><?= htmlspecialchars($_POST['background_color'] ?? '#1DB954') ?></span>
            </div>
        </div>
        <div class="mb-3" id="imageField" style="display:none;">
            <label class="form-label">Album Image</label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary">Save Album</button>
        <a href="albums.php" class="btn btn-secondary ms-2">Cancel</a>
    </form>

    <script>
        function toggleFields() {
            const type = document.querySelector('[name="display_type"]').value;
            const colorField = document.getElementById('colorField');
            const imageField = document.getElementById('imageField');

            if (type === 'color') {
                colorField.style.display = 'block';
                imageField.style.display = 'none';
            } else {
                colorField.style.display = 'none';
                imageField.style.display = 'block';
            }
        }
        // Initialize on page load
        toggleFields();
    </script>
</div>

</body>
</html>

