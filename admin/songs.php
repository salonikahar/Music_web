<?php
require_once '../config/db.php';
require_once 'auth_check.php';

/* ================= DELETE ================= */
if (!empty($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $pdo->prepare("DELETE FROM songs WHERE id = ?")->execute([$id]);
    header("Location: songs.php?msg=deleted");
    exit;
}

/* ================= PAGINATION ================= */
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $items_per_page;

// Get total count
$total_items = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

/* ================= FETCH ================= */
$songs = $pdo->prepare("
    SELECT
        s.*,
        a.name AS artist_name,
        al.name AS album_name
    FROM songs s
    LEFT JOIN artists a ON s.artist_id = a.id
    LEFT JOIN albums al ON s.album_id = al.id
    ORDER BY s.created_at DESC
    LIMIT :limit OFFSET :offset
");
$songs->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$songs->bindValue(':offset', $offset, PDO::PARAM_INT);
$songs->execute();
$songs = $songs->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Songs</title>

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

.audio-player{
    display:flex;
    align-items:center;
    gap:10px;
    background:#181818;
    border:1px solid #2a2a2a;
    border-radius:30px;
    padding:6px 12px;
    width:220px;
}
.audio-btn{
    width:34px;
    height:34px;
    border-radius:50%;
    background:#1DB954;
    border:none;
    color:#000;
}
.audio-progress{
    flex:1;
    height:4px;
    background:#333;
    border-radius:2px;
    cursor:pointer
}
.audio-progress span{
    height:100%;
    width:0;
    background:#1DB954;
    display:block
}
.audio-time{
    font-size:11px;
    color:#aaa;
    min-width:40px
}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

<div class="d-flex justify-content-between mb-4">
    <h2>Songs</h2>
    <a href="add-song.php" class="btn btn-success">+ Add Song</a>
</div>

<?php if (!empty($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Song deleted successfully.</div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('success', 'Song deleted successfully.');
        });
    </script>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-dark table-hover align-middle">
<thead>
<tr>
    <th>Image</th>
    <th>Title</th>
    <th>Artist</th>
    <th>Album</th>
    <th>Duration</th>
    <th>File</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>

<?php foreach ($songs as $song): ?>

<?php
$cover = $song['image_path'] ?? '';

if ($cover && $cover[0] !== '/') {
    $cover = '/' . $cover;
}

$coverUrl = '/Spotify-clone-new/Spotify-clone' . $cover;
?>

<tr>

<td>
    <img src="<?= htmlspecialchars($coverUrl) ?>"
         alt="cover"
         width="50"
         height="50"
         style="object-fit:cover;border-radius:6px"
         onerror="this.src='https://via.placeholder.com/50/121212/FFFFFF?text=♪';this.onerror=null;">
</td>

<td><?= htmlspecialchars($song['title']) ?></td>
<td><?= htmlspecialchars($song['artist_name'] ?? '—') ?></td>
<td><?= htmlspecialchars($song['album_name'] ?? '—') ?></td>
<td><?= htmlspecialchars($song['duration']) ?></td>

<td>
<?php if (!empty($song['file_path'])): ?>
    <div class="audio-player" data-audio>
        <button class="audio-btn">
            <i class="bi bi-play-fill"></i>
        </button>
        <div class="audio-progress"><span></span></div>
        <div class="audio-time">0:00</div>
        <audio src="/spotify-clone-new/spotify-clone<?= htmlspecialchars($song['file_path']) ?>"></audio>
    </div>
<?php else: ?>
    —
<?php endif; ?>
</td>

<td>
    <a href="edit-song.php?id=<?= $song['id'] ?>" class="btn btn-sm btn-outline-warning">Edit</a>
    <a href="?delete=<?= $song['id'] ?>"
       class="btn btn-sm btn-outline-danger"
       onclick="return confirm('Delete this song?')">Delete</a>
</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="d-flex justify-content-center mt-4">
    <nav aria-label="Songs pagination">
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
    Showing <?= count($songs) ?> of <?= $total_items ?> songs
    (Page <?= $current_page ?> of <?= $total_pages ?>)
</p>
</div>

<script>
document.querySelectorAll('[data-audio]').forEach(p => {
    const a = p.querySelector('audio');
    const b = p.querySelector('button');
    const i = b.querySelector('i');
    const bar = p.querySelector('.audio-progress');
    const f = bar.querySelector('span');
    const t = p.querySelector('.audio-time');

    b.onclick = () => {
        document.querySelectorAll('audio').forEach(o => o !== a && o.pause());
        if (a.paused) {
            a.play();
            i.className = 'bi bi-pause-fill';
        } else {
            a.pause();
            i.className = 'bi bi-play-fill';
        }
    };

    a.ontimeupdate = () => {
        if (!a.duration) return;
        f.style.width = (a.currentTime / a.duration * 100) + '%';
        t.textContent =
            Math.floor(a.currentTime / 60) + ':' +
            String(Math.floor(a.currentTime % 60)).padStart(2, '0');
    };

    a.onended = () => {
        i.className = 'bi bi-play-fill';
        f.style.width = '0%';
    };

    bar.onclick = e => {
        const r = bar.getBoundingClientRect();
        a.currentTime = ((e.clientX - r.left) / r.width) * a.duration;
    };
});
</script>

</body>
</html>


