<!-- admin/albums.php -->
<?php
require_once 'auth_check.php';
require_once '../config/db.php';

/* ================= DELETE ================= */
if (!empty($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // delete image file if exists
    $stmt = $pdo->prepare("SELECT image_path FROM albums WHERE id=?");
    $stmt->execute([$id]);
    $album = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($album && !empty($album['image_path'])) {
        $file = $_SERVER['DOCUMENT_ROOT'] . '/spotify-clone-new/spotify-clone' . $album['image_path'];
        if (file_exists($file)) unlink($file);
    }

    $pdo->prepare("DELETE FROM albums WHERE id=?")->execute([$id]);
    header('Location: albums.php?msg=deleted');
    exit;
}

/* ================= PAGINATION ================= */
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $items_per_page;

// Get total count
$total_items = $pdo->query("SELECT COUNT(*) FROM albums")->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

/* ================= FETCH ================= */
$albums = $pdo->prepare("
    SELECT a.*,
           (SELECT COUNT(*) FROM songs s WHERE s.album_id = a.id) as song_count
    FROM albums a
    ORDER BY a.created_at DESC
    LIMIT :limit OFFSET :offset
");
$albums->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$albums->bindValue(':offset', $offset, PDO::PARAM_INT);
$albums->execute();
$albums = $albums->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Albums</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-theme.css">
<link rel="stylesheet" href="../assets/css/notification.css">
<link rel="stylesheet" href="../assets/css/admin-pagination.css">
<script src="../assets/js/notification.js"></script>

<style>
body { background:#121212; color:#fff; }
.main-content { margin-left:240px; padding:24px; }

.table { background: #181818; border-radius: 8px; overflow: hidden; }
.table thead th { background: #282828; border-bottom: 1px solid #333; font-weight: 600; }
.table tbody tr { border-bottom: 1px solid #333; }
.table tbody tr:hover { background: rgba(255,255,255,0.05); }

.album-cover {
    width:50px;
    height:50px;
    border-radius:6px;
    object-fit:cover;
    background:#282828;
}

.album-color {
    width:50px;
    height:50px;
    border-radius:6px;
    border:1px solid #333;
}

table th, table td { vertical-align:middle; }
.btn-primary { background:#1DB954; border:none; }
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Albums</h2>
        <a href="add-album.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Album
        </a>
    </div>

    <?php if (!empty($_GET['msg'])): ?>
        <div class="alert alert-success">Album <?= htmlspecialchars($_GET['msg']) ?> successfully.</div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('success', 'Album <?= addslashes(htmlspecialchars($_GET['msg'])) ?> successfully.');
            });
        </script>
    <?php endif; ?>

    <?php if (empty($albums)): ?>
        <div class="alert alert-secondary">No albums found.</div>
    <?php else: ?>

    <div class="table-responsive">
        <table class="table table-dark table-hover">
            <thead>
                <tr>
                    <th width="70">Cover</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Songs</th>
                    <th>Type</th>
                    <th width="160">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($albums as $album): ?>
                <tr>
                    <td>
                        <?php if ($album['display_type']==='image' && $album['image_path']): ?>
                            <img src="/spotify-clone-new/spotify-clone<?= htmlspecialchars($album['image_path']) ?>" class="album-cover">
                        <?php elseif ($album['display_type']==='color'): ?>
                            <div class="album-color" style="background:<?= htmlspecialchars($album['bg_color']) ?>"></div>

                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>

                    <td><?= htmlspecialchars($album['name']) ?></td>

                    <td><?= htmlspecialchars(mb_strimwidth($album['description'], 0, 60, '…')) ?></td>

                    <td><?= htmlspecialchars($album['song_count']) ?></td>

                    <td><?= ucfirst($album['display_type']) ?></td>

                    <td>
                        <a href="edit-album.php?id=<?= $album['id'] ?>" class="btn btn-sm btn-outline-warning me-1">
                            Edit
                        </a>
                        <a href="?delete=<?= $album['id'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this album permanently?')">
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
    <nav aria-label="Albums pagination">
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
    Showing <?= count($albums) ?> of <?= $total_items ?> albums
    (Page <?= $current_page ?> of <?= $total_pages ?>)
</p>
</div>
</body>
</html>


