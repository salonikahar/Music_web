<?php
session_start();
require_once 'config/db.php';
require_once 'includes/premium_check.php';
$BASE_URL = '.';
$userLoggedIn = isset($_SESSION['user_id']);

// Update premium status in session for logged in users
if ($userLoggedIn) {
    $_SESSION['is_premium'] = isPremiumUser($_SESSION['user_id']) ? 1 : 0;
}

/* ================= PLAYLISTS ================= */
$playlists = [];
if ($userLoggedIn) {
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            (
                SELECT s.image_path
                FROM user_playlist_songs ups
                JOIN songs s ON ups.song_id = s.id
                WHERE ups.playlist_id = p.id
                ORDER BY ups.added_at ASC
                LIMIT 1
            ) AS image_path
        FROM playlists p
        WHERE p.user_id = ? AND EXISTS (
            SELECT 1 FROM user_playlist_songs ups WHERE ups.playlist_id = p.id
        )
        ORDER BY RAND()
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $playlists = $stmt->fetchAll();
}

/* ================= ARTISTS ================= */
$artists = $pdo->query("
    SELECT id, name, image_path
    FROM artists
    ORDER BY RAND()
    LIMIT 10
")->fetchAll();

// Check if there are more than 10 artists
$totalArtists = $pdo->query("SELECT COUNT(*) as count FROM artists")->fetch()['count'];
$showMoreArtists = $totalArtists > 10;

/* ================= ALBUMS ================= */
$albums = $pdo->query("
    SELECT 
        id,
        name,
        display_type,
        image_path,
        bg_color
    FROM albums
    ORDER BY created_at DESC
")->fetchAll();

/* ================= SONGS ================= */
$songs = $pdo->query("
    SELECT
        songs.id,
        songs.title,
        songs.file_path,
        songs.image_path AS cover,
        artists.name AS artist_name
    FROM songs
    LEFT JOIN artists ON songs.artist_id = artists.id
    ORDER BY RAND()
    LIMIT 10
")->fetchAll();

// Check if there are more than 10 songs
$totalSongs = $pdo->query("SELECT COUNT(*) as count FROM songs")->fetch()['count'];
$showMoreSongs = $totalSongs > 10;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PulseWave</title>

    <link rel="icon" href="assets/default-playlist.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/assets/css/player.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/assets/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/assets/css/library.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/assets/css/modern.css">
</head>

<body>

    <script>
        const BASE_URL = "<?= $BASE_URL ?>";
    </script>


    <?php include 'partials/header.php'; ?>
    <?php include 'partials/sidebar.php'; ?>

    <!-- ================= MAIN CONTENT ================= -->
    <div class="main-content" id="app-content">


        <!-- SONGS -->
        <h2 class="section-title">Today's Biggest Hits</h2>

        <div class="scroll-row">
            <div class="today-hits horizontal-scroll" id="songContainer">

                <?php foreach ($songs as $song): ?>
                    <div class="hit-card" data-song='<?= json_encode($song) ?>' onclick="playHit(this)">
                        <img src="<?= $BASE_URL . ($song['cover'] ?: '/assets/default-song.png') ?>">
                        <div class="play-btn"><i class="bi bi-play-fill"></i></div>
                        <a href="api/download.php?song_id=<?= $song['id'] ?>" class="download-btn"><i class="bi bi-download"></i></a>
                        <div class="hit-title"><?= htmlspecialchars($song['title']) ?></div>
                        <div class="hit-desc"><?= htmlspecialchars($song['artist_name'] ?? 'Unknown Artist') ?></div>
                    </div>
                <?php endforeach; ?>

                <?php if ($showMoreSongs): ?>
                    <!-- SHOW MORE CARD (INSIDE SLIDER) -->
                    <div class="show-more-card" onclick="loadMoreSongs()">
                        <span>Show<br>More</span>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- PLAYLISTS -->
        <?php if ($userLoggedIn && !empty($playlists)): ?>
            <h2 class="section-title">Made For You</h2>
            <div class="scroll-row">
                <div class="playlist-grid horizontal-scroll">
                    <?php foreach ($playlists as $pl): ?>
                        <a href="pages/playlist.php?id=<?= $pl['id'] ?>" class="playlist-card">
                            <img src="<?= $BASE_URL . ($pl['image_path'] ?: '/assets/default-playlist.png') ?>">
                            <div class="playlist-play-overlay">
                                <i class="bi bi-play-fill"></i>
                            </div>
                            <div class="playlist-name"><?= htmlspecialchars($pl['name']) ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($userLoggedIn): ?>
            <!-- MOST PLAYED -->
            <div id="mostPlayedSection" style="display: none;">
                <h2 class="section-title">Most Played</h2>
                <div class="scroll-row">
                    <div class="today-hits horizontal-scroll" id="mostPlayedContainer">
                        <!-- Will be loaded via JavaScript -->
                    </div>
                </div>
            </div>
        <?php endif; ?>


        <!-- ALBUMS -->
        <h2 class="section-title">New Releases</h2>

        <div class="scroll-row">
            <div class="album-grid">

                <?php foreach ($albums as $album): ?>

                    <?php
                    if ($album['display_type'] === 'image' && !empty($album['image_path'])) {
                        $bg = "url('{$BASE_URL}{$album['image_path']}')";
                    } else {
                        $bg = "linear-gradient(135deg, {$album['bg_color']}, #000)";
                    }
                    ?>

                    <!-- <a href="<?= $BASE_URL ?>/album.php?id=<?= $album['id'] ?>" class="spotify-editorial-card"
                        style="background: <?= $bg ?>;"> -->
                    <a href="pages/album.php?id=<?= $album['id'] ?>" class="spotify-editorial-card"                         style="background: <?= $bg ?>;">



                        <div class=" editorial-play">
                            <i class="bi bi-play-fill"></i>
                        </div>

                        <div class="editorial-content">
                            <div class="editorial-title"><?= htmlspecialchars($album['name']) ?></div>
                            <div class="editorial-sub">Album</div>
                        </div>

                    </a>

                <?php endforeach; ?>

            </div>
        </div>


                <!-- ARTISTS -->
        <h2 class="section-title">Popular Artists</h2>
        <div class="scroll-row">
            <div class="artist-grid horizontal-scroll" id="artistContainer">
                <?php foreach ($artists as $artist): ?>
                    <div class="artist-card">
                        <img src="<?= $BASE_URL . ($artist['image_path'] ?: '/assets/default-artist.png') ?>">
                        <div class="artist-name"><?= htmlspecialchars($artist['name']) ?></div>
                    </div>
                <?php endforeach; ?>

                <?php if ($showMoreArtists): ?>
                    <!-- SHOW MORE CARD (INSIDE SLIDER) -->
                    <div class="show-more-card" onclick="loadMoreArtists()">
                        <span>Show<br>More</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <?php include 'partials/player.php'; ?>

    </div>


    <script src="<?php echo $BASE_URL; ?>/assets/js/player.js"></script>
    
    <script src="<?php echo $BASE_URL; ?>/assets/js/sidebar.js?v=2"></script>
    <script src="<?php echo $BASE_URL; ?>/assets/js/spa.js"></script>


    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('active');
        }

        let songOffset = 10;
        let artistOffset = 10;
        let loading = false;

        function loadMoreSongs() {
            if (loading) return;
            loading = true;

            fetch(`load-more-songs.php?offset=${songOffset}`)
                .then(res => res.text())
                .then(html => {
                    const container = document.getElementById("songContainer");
                    const showMore = container.querySelector(".show-more-card");

                    // insert new songs BEFORE show-more card
                    showMore.insertAdjacentHTML("beforebegin", html);

                    songOffset += 10;
                    loading = false;
                });
        }

        function loadMoreArtists() {
            if (loading) return;
            loading = true;

            fetch(`api/load-more-artists.php?offset=${artistOffset}`)
                .then(res => res.text())
                .then(html => {
                    const container = document.getElementById("artistContainer");
                    const showMore = container.querySelector(".show-more-card");

                    // insert new artists BEFORE show-more card
                    showMore.insertAdjacentHTML("beforebegin", html);

                    artistOffset += 5;
                    loading = false;
                });
        }

        // Load most played songs for logged-in users
        function loadMostPlayedSongs() {
            fetch(`${BASE_URL}/api/user-playlists.php`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.most_played && data.most_played.length > 0) {
                        const section = document.getElementById('mostPlayedSection');
                        const container = document.getElementById('mostPlayedContainer');
                        container.innerHTML = '';

                        data.most_played.forEach(song => {
                            const songCard = document.createElement('div');
                            songCard.className = 'hit-card';
                            songCard.setAttribute('data-song', JSON.stringify(song));
                            songCard.onclick = () => playHit(songCard);

                            songCard.innerHTML = `
                                <img src="${BASE_URL}${song.cover || '/assets/default-song.png'}">
                                <div class="play-btn"><i class="bi bi-play-fill"></i></div>
                                <a href="api/download.php?song_id=${song.id}" class="download-btn"><i class="bi bi-download"></i></a>
                                <div class="hit-title">${song.title}</div>
                                <div class="hit-desc">${song.artist_name || 'Unknown Artist'}</div>
                            `;

                            container.appendChild(songCard);
                        });

                        // Show the section only if there are songs
                        section.style.display = 'block';
                    }
                })
                .catch(error => console.error('Error loading most played songs:', error));
        }

        // Load most played songs on page load if user is logged in
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($userLoggedIn): ?>
                loadMostPlayedSongs();
            <?php else: ?>
                // For non-logged users, show placeholder
                const container = document.getElementById('mostPlayedContainer');
                container.innerHTML = `
                    <div class="hit-card placeholder">
                        <img src="/assets/default-song.png">
                        <div class="play-btn"><i class="bi bi-play-fill"></i></div>
                        <div class="hit-title">Login to see your most played songs</div>
                        <div class="hit-desc">Create an account to track your listening history</div>
                    </div>
                `;
            <?php endif; ?>
        });
    </script>

</body>

</html>

