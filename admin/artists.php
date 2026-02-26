<!-- admin/artists.php -->
<?php
require_once 'auth_check.php';
require_once '../config/db.php';

/* ================= DELETE ARTIST ================= */
if (!empty($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    // fetch image path before delete (optional cleanup)
    $stmt = $pdo->prepare("SELECT image_path FROM artists WHERE id = ?");
    $stmt->execute([$id]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($artist && !empty($artist['image_path'])) {
        $file = $_SERVER['DOCUMENT_ROOT'] . '/spotify-clone-new/spotify-clone' . $artist['image_path'];
        if (file_exists($file)) {
            unlink($file); // delete image file
        }
    }

    $pdo->prepare("DELETE FROM artists WHERE id = ?")->execute([$id]);

    header('Location: artists.php?msg=deleted');
    exit;
}

/* ================= PAGINATION ================= */
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $items_per_page;

// Get total count
$total_items = $pdo->query("SELECT COUNT(*) FROM artists")->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

/* ================= FETCH ARTISTS ================= */
$artists = $pdo->prepare("SELECT * FROM artists ORDER BY name LIMIT :limit OFFSET :offset");
$artists->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$artists->bindValue(':offset', $offset, PDO::PARAM_INT);
$artists->execute();
$artists = $artists->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Artists</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-theme.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <link rel="stylesheet" href="../assets/css/admin-pagination.css">
    <script src="../assets/js/notification.js"></script>

    <style>
        body { background:#121212; color:#fff; margin:0; }
        .main-content { margin-left:240px; padding:24px; }
        .btn-primary { background:#1DB954; border:none; }
        .artist-img {
            width:50px;
            height:50px;
            object-fit:cover;
            border-radius:6px;
        }
        table th, table td { vertical-align:middle; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Artists</h2>
        <a href="add-artist.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Artist
        </a>
    </div>

    <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success">Artist deleted successfully.</div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('success', 'Artist deleted successfully.');
            });
        </script>
    <?php endif; ?>

    <?php if (empty($artists)): ?>
        <div class="alert alert-secondary">No artists found.</div>
    <?php else: ?>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th width="70">Image</th>
                        <th>Name</th>
                        <th>Bio</th>
                        <th width="160">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($artists as $artist): ?>
                    <tr>
                        <td>
                            <?php if (!empty($artist['image_path'])): ?>
                                <img
                                    src="/spotify-clone-new/spotify-clone<?= htmlspecialchars($artist['image_path']) ?>"
                                    class="artist-img"
                                    alt="<?= htmlspecialchars($artist['name']) ?>"
                                    onerror="this.src='https://via.placeholder.com/50/181818/FFFFFF?text=No+Image';this.onerror=null;">
                            <?php else: ?>
                                <img
                                    src="https://via.placeholder.com/50/181818/FFFFFF?text=No+Image"
                                    class="artist-img"
                                    alt="No Image">
                            <?php endif; ?>
                        </td>

                        <td><?= htmlspecialchars($artist['name']) ?></td>

                        <td>
                            <?= htmlspecialchars(mb_strimwidth($artist['bio'], 0, 60, '...')) ?>
                        </td>

                        <td>
                            <a href="edit-artist.php?id=<?= $artist['id'] ?>"
                               class="btn btn-sm btn-outline-warning me-1">
                                Edit
                            </a>
                            <a href="?delete=<?= $artist['id'] ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Delete this artist permanently?')">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="d-flex justify-content-center mt-4">
    <nav aria-label="Artists pagination">
        <ul class="pagination admin-pagination mb-0">
            <!-- Previous -->
            <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $current_page - 1 ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <!-- Page numbers -->
            <?php
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <!-- Next -->
            <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $current_page + 1 ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
</div>
<?php endif; ?>

<p class="admin-pagination-info">
    Showing <?= count($artists) ?> of <?= $total_items ?> artists
    (Page <?= $current_page ?> of <?= $total_pages ?>)
</p>
</div>

</body>
</html>


