<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/premium_check.php';

$BASE_URL = '..';

/* ================= VALIDATE ID ================= */
$albumId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($albumId <= 0) die('Invalid album');

/* ================= FETCH ALBUM ================= */
$stmt = $pdo->prepare("SELECT * FROM albums WHERE id=?");
$stmt->execute([$albumId]);
$album = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$album) die('Album not found');

/* ================= FETCH SONGS ================= */
$stmt = $pdo->prepare("
    SELECT s.id, s.title, s.file_path, s.duration,
           s.image_path AS cover,
           a.name AS artist_name
    FROM songs s
    LEFT JOIN artists a ON a.id = s.artist_id
    WHERE s.album_id = ?
    ORDER BY s.id
");
$stmt->execute([$albumId]);
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= DURATION ================= */
$totalSeconds = 0;
foreach ($songs as $s) {
    if (!empty($s['duration'])) {
        [$m, $sec] = array_pad(explode(':', $s['duration']), 2, 0);
        $totalSeconds += ($m * 60) + $sec;
    }
}
$totalDuration = floor($totalSeconds / 60) . ' min ' . ($totalSeconds % 60) . ' sec';

/* ================= HERO ================= */
$coverImage = null;
$coverColor = $album['bg_color'] ?: '#121212';

if ($album['display_type'] === 'image' && !empty($album['image_path'])) {
    $coverImage = $BASE_URL . $album['image_path'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($album['name']) ?> – PulseWave</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/player.css">
<link rel="stylesheet" href="../assets/css/sidebar.css">
<link rel="stylesheet" href="../assets/css/modern.css">
<link rel="stylesheet" href="../assets/css/notification.css">

<!-- ================= ALBUM INTERNAL CSS ================= -->
<style id="album-inline-style">
:root {
    --green: #ff8a2d;
    --green-hover: #ffb067;
    --bg: #121212;
    --panel: #181818;
    --panel-hover: #282828;
    --text: #fff;
    --text-secondary: #b3b3b3;
    --text-muted: #6a6a6a;
    --border: #282828;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

*{margin:0;padding:0;box-sizing:border-box}

.main-content.album-page{
    margin-left:280px;
    background:var(--bg);
    min-height:100vh;
    color:var(--text);
}

/* HERO */
.album-hero{
    position:relative;
    min-height:480px;
    background:<?= $coverImage ? "url('$coverImage') center/cover no-repeat" : $coverColor ?>;
    background-attachment:fixed;
}

.album-hero::before {
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(135deg, rgba(29, 185, 84, 0.2), rgba(0, 0, 0, 0.6));
}

.album-hero::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(to bottom, rgba(0,0,0,.2) 0%, rgba(0,0,0,.4) 50%, var(--bg) 100%);
}

.album-hero-inner{
    position:relative;
    z-index:2;
    max-width:1300px;
    margin:auto;
    display:flex;
    align-items:flex-end;
    gap:40px;
    padding:160px 40px 50px;
}

.album-cover{
    width:240px;
    height:240px;
    object-fit:cover;
    border-radius:12px;
    box-shadow: 0 25px 60px rgba(0,0,0,.7), 0 0 0 1px rgba(255,255,255,0.1);
    flex-shrink:0;
    animation: slideInLeft 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.album-type{
    font-size:12px;
    text-transform:uppercase;
    letter-spacing: 1px;
    color:var(--text-secondary);
    font-weight:700;
}

.album-title{
    font-size:clamp(40px, 6vw, 90px);
    font-weight:900;
    line-height:1.05;
    margin:12px 0 16px;
    letter-spacing: -1px;
}

.album-desc{
    color:var(--text-secondary);
    max-width:600px;
    margin-bottom:12px;
    font-size: 15px;
}

.album-info{
    color:var(--text-muted);
    font-size:14px;
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}

.album-info-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ACTIONS */
.album-actions{
    padding:32px 40px;
    display:flex;
    gap:20px;
    align-items: center;
    border-bottom: 1px solid var(--border);
}

.album-play{
    width:60px;
    height:60px;
    border-radius:50%;
    border:none;
    background: linear-gradient(135deg, var(--green), var(--green-hover));
    color:#000;
    font-size:24px;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: 0 8px 24px rgba(29, 185, 84, 0.3);
}

.album-play:hover{
    transform: translateY(-2px) scale(1.06);
    box-shadow: 0 12px 32px rgba(29, 185, 84, 0.4);
}

.album-shuffle{
    width:60px;
    height:60px;
    border-radius:50%;
    background:transparent;
    border:1px solid var(--border);
    color:var(--green);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: var(--transition);
}

.album-shuffle:hover{
    border-color: var(--green);
    background: rgba(29, 185, 84, 0.1);
    transform: translateY(-2px);
}

/* SONG LIST */
.album-songs{
    padding:0 40px 140px;
}

.song-header,
.song-row{
    display:grid;
    grid-template-columns:50px 50px 1fr 100px 50px;
    gap:20px;
    align-items:center;
}

.song-header{
    color:var(--text-muted);
    font-size:11px;
    text-transform:uppercase;
    padding:16px 20px;
    border-bottom: 2px solid var(--border);
    font-weight: 700;
    letter-spacing: 0.5px;
}

.song-row{
    padding:14px 20px;
    border-radius:8px;
    cursor:pointer;
    transition: var(--transition);
    border: 1px solid transparent;
    margin-bottom: 4px;
}

.song-row:hover{
    background: rgba(255,255,255,.08);
    border-color: var(--border);
    backdrop-filter: blur(10px);
}

.song-number{
    color:var(--text-muted);
    font-weight: 500;
}

.song-play-icon{
    opacity:0;
    color:var(--green);
    font-size: 18px;
    transition: opacity 0.2s ease;
}

.song-row:hover .song-play-icon{
    opacity:1;
}

.song-row:hover .song-number{
    opacity:0;
}

.song-title{
    font-size:15px;
    font-weight: 500;
}

.song-artist{
    font-size:13px;
    color:var(--text-secondary);
    transition: var(--transition);
}

.song-row:hover .song-artist {
    color: var(--text);
}

.song-duration{
    text-align:right;
    color:var(--text-muted);
}

.song-download {
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--text-muted);
    opacity: 0;
    transition: var(--transition);
}

.song-row:hover .song-download {
    opacity: 1;
    color: var(--green);
}

.song-download:hover {
    color: var(--green-hover);
}

/* MOBILE */
@media(max-width:1024px){
    .main-content.album-page{ margin-left:0; padding: 120px 30px 200px 30px; }
    .album-hero-inner{ padding: 120px 30px 40px; gap: 30px; }
    .album-cover{ width: 180px; height: 180px; }
    .album-title{ font-size: 48px; }
    .album-songs{ padding: 0 0 140px; }
    .song-header, .song-row { grid-template-columns: 40px 40px 1fr 80px 40px; gap: 12px; }
}

@media(max-width:768px){
    .main-content.album-page{ padding: 100px 20px 180px 20px; }
    .album-hero{min-height:400px}
    .album-hero-inner{
        flex-direction:column;
        align-items:center;
        text-align:center;
        padding:100px 20px 30px;
        gap: 24px;
    }
    .album-cover{width:160px;height:160px}
    .album-title{ font-size: 32px; }
    .album-actions{ flex-direction: column; padding: 24px 20px; }
    .album-songs{padding:0 20px 140px}
    .song-header, .song-row { grid-template-columns: 40px 1fr 60px; }
    .song-row { grid-template-columns: 40px 1fr 40px; }
    .song-artist, .song-duration { display: none; }
}
</style>
</head>

<body>

<?php include '../partials/header.php'; ?>
<?php include '../partials/sidebar.php'; ?>

<div class="main-content album-page">

    <!-- HERO -->
    <div class="album-hero">
        <div class="album-hero-inner">
            <div>
                <div class="album-type">Album</div>
                <h1 class="album-title"><?= htmlspecialchars($album['name']) ?></h1>
                <?php if (!empty($album['description'])): ?>
                    <div class="album-desc"><?= htmlspecialchars($album['description']) ?></div>
                <?php endif; ?>
                <div class="album-info">
                    <?= count($songs) ?> songs • <?= $totalDuration ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ACTIONS -->
    <?php if ($songs): ?>
    <div class="album-actions">
        <button class="album-play" onclick="playAlbum(false)">
            <i class="bi bi-play-fill"></i>
        </button>
        <button class="album-shuffle" onclick="playAlbum(true)">
            <i class="bi bi-shuffle"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- SONG LIST -->
    <div class="album-songs">
        <div class="song-header">
            <div>#</div><div></div><div>Title</div><div>Time</div>
        </div>
        <?php foreach ($songs as $i => $song): ?>
        <div class="song-row" data-song='<?= json_encode($song) ?>' onclick="playAlbumSong(this)">
            <div class="song-number"><?= $i+1 ?></div>
            <div class="song-play-icon"><i class="bi bi-play-fill"></i></div>
            <div>
                <div class="song-title"><?= htmlspecialchars($song['title']) ?></div>
                <div class="song-artist"><?= htmlspecialchars($song['artist_name'] ?? 'Unknown') ?></div>
            </div>
            <div class="song-duration"><?= $song['duration'] ?? '—' ?></div>
            <div class="song-actions">
                <?php
                // Check premium status
                $userPremium = false;
                if (isset($_SESSION['user_id'])) {
                    $userPremium = isPremiumUser($_SESSION['user_id']);
                }

                if ($userPremium): ?>
                    <a href="../api/download.php?song_id=<?= $song['id'] ?>" class="btn btn-success btn-sm" title="Download">
                        <i class="bi bi-download"></i>
                    </a>
                <?php else: ?>
                    <a href="../premium.php" class="btn btn-warning btn-sm" title="Upgrade to Premium to Download">
                        <i class="bi bi-download"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../partials/player.php'; ?>

<!-- SPA SAFE CSS -->
<script>
(function(){
    const s=document.getElementById('album-inline-style');
    if(s && !document.getElementById('album-style-mounted')){
        const c=s.cloneNode(true);
        c.id='album-style-mounted';
        document.head.appendChild(c);
    }
})();
</script>

<script>const BASE_URL = "<?= $BASE_URL ?>";</script>
<script src="<?= $BASE_URL ?>/assets/js/player.js"></script>
<script src="<?= $BASE_URL ?>/assets/js/sidebar.js"></script>

</body>
</html>



