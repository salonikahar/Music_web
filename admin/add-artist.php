<!-- admin/add-artist.php -->
<?php
require_once 'auth_check.php';
require_once '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $image_path = '';

    // Validate name
    if (empty($name)) {
        $error = "Artist name is required.";
    } else {
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = '../uploads/artists/';
            $webPath = '/uploads/artists/';

            // Create folder if needed
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    $error = "Failed to create upload directory. Check permissions.";
                }
            }

            // Validate upload error
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = "Upload error: " . $_FILES['image']['error'];
            } else {
                // Validate file type
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];

                if (!in_array($ext, $allowed)) {
                    $error = "Only JPG, PNG, GIF files allowed.";
                } else {
                    // Generate unique filename
                    $filename = uniqid() . '.' . $ext;
                    $targetPath = $uploadDir . $filename;

                    // After successful upload
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $error = "Failed to move uploaded file. Check permissions.";
                    } else {
                        $image_path = $webPath . $filename;
                        // ✅ Debug: Uncomment to see what's being saved
                        // echo "<pre>DEBUG: Saving image_path = '$image_path'</pre>";
                    }
                }
            }
        }

        // If no errors, save to database
        if (!$error) {
            try {
                $stmt = $pdo->prepare("INSERT INTO artists (name, bio, image_path) VALUES (?, ?, ?)");
                $stmt->execute([$name, $bio, $image_path]);
                header('Location: artists.php?msg=added');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = "Artist '$name' already exists. Please choose a different name.";
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
    <title>Add Artist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-theme.css">
    <style>
        body {
            background: #121212;
            color: white;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 240px;
            padding: 24px;
        }

        .form-control {
            background: #181818;
            border: 1px solid #282828;
            color: white;
        }

        .btn-primary {
            background: #1DB954;
            border: none;
        }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2>Add New Artist</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="mt-4">
            <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" required
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Bio</label>
                <textarea name="bio" class="form-control"
                    rows="3"><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Profile Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Save Artist</button>
            <a href="artists.php" class="btn btn-secondary ms-2">Cancel</a>
        </form>
    </div>

</body>

</html>

