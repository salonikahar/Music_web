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
$totalAlbums = count($albums);
$featuredSong = $songs[0] ?? null;
$freshAlbum = $albums[0] ?? null;

$bannerSlides = [
    [
        'badge' => 'Hot Right Now',
        'title' => 'Top charts are waiting for you',
        'description' => 'Jump into today\'s biggest songs and discover what everyone is streaming.',
        'cta_label' => 'Play Hits',
        'cta_link' => 'index.php?focus_search=1',
        'focus_search' => true,
        'image' => $featuredSong['cover'] ?? '/assets/default-playlist.png',
    ],
    [
        'badge' => 'Fresh Albums',
        'title' => 'New drops, fresh energy',
        'description' => 'Explore recently added albums and find your next repeat track.',
        'cta_label' => 'Browse Albums',
        'cta_link' => 'index.php?focus_search=1',
        'focus_search' => true,
        'image' => (!empty($freshAlbum['image_path']) && ($freshAlbum['display_type'] ?? '') === 'image')
            ? $freshAlbum['image_path']
            : '/assets/default-album.jpg',
    ],
    [
        'badge' => 'Your Zone',
        'title' => $userLoggedIn ? 'Your library is one tap away' : 'Create your personal music zone',
        'description' => $userLoggedIn
            ? 'Open playlists, liked tracks, and your recent favorites instantly.'
            : 'Sign up to build playlists, save favorites, and unlock personal recommendations.',
        'cta_label' => $userLoggedIn ? 'Open Library' : 'Sign Up Free',
        'cta_link' => $userLoggedIn ? 'pages/library.php' : 'register.php',
        'image' => '/assets/default-playlist.png',
    ],
];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PulseWave</title>

    <link rel="icon" href="assets/default-playlist.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Manrope:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/assets/css/player.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/assets/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/assets/css/library.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/assets/css/modern.css">
    <style>
        :root {
            --pw-ink: #0b1220;
            --pw-deep: #051827;
            --pw-aqua: #1ed8b5;
            --pw-amber: #f5b236;
            --pw-soft: #f3f7fb;
        }

        .main-content {
            font-family: "Manrope", sans-serif;
        }

        .promo-slider {
            position: relative;
            margin: 12px 0 20px;
            border-radius: 26px;
            overflow: hidden;
            box-shadow: 0 18px 38px rgba(7, 24, 42, 0.25);
            min-height: 250px;
        }

        .promo-track {
            display: flex;
            transition: transform 0.55s ease;
            will-change: transform;
        }

        .promo-slide {
            position: relative;
            min-width: 100%;
            min-height: 250px;
            padding: 28px;
            display: flex;
            align-items: flex-end;
            background-size: cover;
            background-position: center;
            isolation: isolate;
        }

        .promo-slide::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            background:
                linear-gradient(105deg, rgba(5, 15, 28, 0.86) 5%, rgba(5, 15, 28, 0.6) 55%, rgba(5, 15, 28, 0.24) 100%),
                radial-gradient(circle at 80% 10%, rgba(30, 216, 181, 0.35), transparent 45%);
        }

        .promo-content {
            color: #fff;
            max-width: 580px;
        }

        .promo-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.73rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            background: rgba(245, 178, 54, 0.24);
            border: 1px solid rgba(245, 178, 54, 0.45);
            margin-bottom: 10px;
        }

        .promo-content h2 {
            margin: 0 0 8px;
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(1.3rem, 2.8vw, 2.2rem);
            line-height: 1.15;
        }

        .promo-content p {
            margin: 0 0 14px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
        }

        .promo-cta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            text-decoration: none;
            padding: 9px 16px;
            font-size: 0.84rem;
            font-weight: 700;
            background: #1ed8b5;
            color: #00231d;
            box-shadow: 0 10px 20px rgba(30, 216, 181, 0.3);
        }

        .promo-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(1, 12, 22, 0.55);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
        }

        .promo-nav-btn.prev {
            left: 10px;
        }

        .promo-nav-btn.next {
            right: 10px;
        }

        .promo-dots {
            position: absolute;
            left: 50%;
            bottom: 12px;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 2;
        }

        .promo-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            border: 0;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            padding: 0;
        }

        .promo-dot.active {
            background: #fff;
            transform: scale(1.2);
        }

        .section-title {
            font-family: "Space Grotesk", sans-serif;
            letter-spacing: 0.02em;
        }

        .home-hero {
            margin: 10px 0 26px;
            padding: 28px;
            border-radius: 26px;
            color: #fff;
            background:
                radial-gradient(circle at 12% 12%, rgba(30, 216, 181, 0.4), transparent 42%),
                radial-gradient(circle at 88% 14%, rgba(245, 178, 54, 0.28), transparent 40%),
                linear-gradient(120deg, var(--pw-ink), var(--pw-deep));
            box-shadow: 0 18px 40px rgba(4, 17, 32, 0.38);
            overflow: hidden;
        }

        .home-hero h1 {
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(1.7rem, 3.6vw, 2.8rem);
            line-height: 1.1;
            margin-bottom: 10px;
            max-width: 640px;
        }

        .home-hero p {
            font-size: 0.97rem;
            color: rgba(255, 255, 255, 0.9);
            max-width: 590px;
            margin-bottom: 18px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }

        .hero-btn {
            padding: 10px 18px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.88rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .hero-btn.primary {
            background: var(--pw-aqua);
            color: #00231d;
            box-shadow: 0 8px 18px rgba(30, 216, 181, 0.3);
        }

        .hero-btn.secondary {
            border: 1px solid rgba(255, 255, 255, 0.38);
            color: #fff;
            background: rgba(255, 255, 255, 0.06);
        }

        .hero-btn:hover {
            transform: translateY(-1px);
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(110px, 1fr));
            gap: 10px;
            margin-top: 8px;
            max-width: 520px;
        }

        .hero-stat {
            padding: 12px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(5px);
        }

        .hero-stat strong {
            display: block;
            font-family: "Space Grotesk", sans-serif;
            font-size: 1.05rem;
        }

        .hero-stat span {
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.82);
        }

        .feature-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin: 0 0 24px;
        }

        .feature-item {
            border-radius: 16px;
            padding: 15px;
            background: linear-gradient(140deg, #ffffff, #eef5fb);
            border: 1px solid #e2ebf5;
            box-shadow: 0 10px 20px rgba(17, 43, 75, 0.08);
        }

        .feature-item i {
            display: inline-flex;
            width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            color: var(--pw-ink);
            background: linear-gradient(145deg, #b3f7eb, #f8e7c3);
            margin-bottom: 8px;
        }

        .feature-item h3 {
            margin: 0 0 6px;
            font-size: 0.97rem;
            font-family: "Space Grotesk", sans-serif;
            color: #0b2138;
        }

        .feature-item p {
            margin: 0;
            font-size: 0.82rem;
            color: #4d6177;
        }

        .spotlight-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            border-radius: 999px;
            background: rgba(245, 178, 54, 0.2);
            color: #fff9eb;
            font-size: 0.76rem;
            margin-bottom: 10px;
        }

        @media (max-width: 991px) {
            .feature-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .promo-slider,
            .promo-slide {
                min-height: 220px;
            }

            .promo-slide {
                padding: 18px 16px 26px;
            }

            .promo-nav-btn {
                display: none;
            }

            .home-hero {
                padding: 22px 18px;
                border-radius: 20px;
            }

            .hero-stats {
                grid-template-columns: repeat(2, minmax(100px, 1fr));
            }

            .feature-strip {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <script>
        const BASE_URL = "<?= $BASE_URL ?>";
    </script>


    <?php include 'partials/header.php'; ?>
    <?php include 'partials/sidebar.php'; ?>

    <!-- ================= MAIN CONTENT ================= -->
    <div class="main-content" id="app-content">

        <section class="promo-slider" id="promoSlider">
            <div class="promo-track" id="promoTrack">
                <?php foreach ($bannerSlides as $slide): ?>
                    <article class="promo-slide" style="background-image: url('<?= htmlspecialchars($BASE_URL . $slide['image']) ?>');">
                        <div class="promo-content">
                            <span class="promo-badge"><?= htmlspecialchars($slide['badge']) ?></span>
                            <h2><?= htmlspecialchars($slide['title']) ?></h2>
                            <p><?= htmlspecialchars($slide['description']) ?></p>
                            <a class="promo-cta <?= !empty($slide['focus_search']) ? 'search-cta-btn' : '' ?>" href="<?= htmlspecialchars($slide['cta_link']) ?>">
                                <?= htmlspecialchars($slide['cta_label']) ?>
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <button class="promo-nav-btn prev" id="promoPrev" aria-label="Previous banner">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button class="promo-nav-btn next" id="promoNext" aria-label="Next banner">
                <i class="bi bi-chevron-right"></i>
            </button>
            <div class="promo-dots" id="promoDots"></div>
        </section>

        <section class="home-hero">
            <span class="spotlight-badge">
                <i class="bi bi-vinyl-fill"></i>
                Fresh picks for your vibe
            </span>
            <h1>Turn every moment into a live soundtrack.</h1>
            <p>
                Stream trending songs, discover artists, and jump into curated albums with a faster and smoother PulseWave home experience.
            </p>

            <div class="hero-actions">
                <a class="hero-btn primary search-cta-btn" href="index.php?focus_search=1"><i class="bi bi-search me-1"></i>Explore Music</a>
                <?php if ($userLoggedIn): ?>
                    <a class="hero-btn secondary" href="pages/library.php"><i class="bi bi-collection-play me-1"></i>Your Library</a>
                <?php else: ?>
                    <a class="hero-btn secondary" href="register.php"><i class="bi bi-person-plus me-1"></i>Create Account</a>
                <?php endif; ?>
            </div>

            <div class="hero-stats">
                <div class="hero-stat">
                    <strong><?= number_format((int)$totalSongs) ?>+</strong>
                    <span>Songs</span>
                </div>
                <div class="hero-stat">
                    <strong><?= number_format((int)$totalArtists) ?>+</strong>
                    <span>Artists</span>
                </div>
                <div class="hero-stat">
                    <strong><?= number_format((int)$totalAlbums) ?>+</strong>
                    <span>Albums</span>
                </div>
            </div>
        </section>

        <section class="feature-strip">
            <article class="feature-item">
                <i class="bi bi-music-note-beamed"></i>
                <h3>Daily Mixes</h3>
                <p>Auto-picked songs that match your current listening mood.</p>
            </article>
            <article class="feature-item">
                <i class="bi bi-stars"></i>
                <h3>New Releases</h3>
                <p>Catch freshly uploaded albums and standout tracks first.</p>
            </article>
            <article class="feature-item">
                <i class="bi bi-lightning-charge-fill"></i>
                <h3>Instant Play</h3>
                <p>Start tracks with one click and keep the flow uninterrupted.</p>
            </article>
        </section>

        <!-- SONGS -->
        <h2 class="section-title">
            Today&apos;s Biggest Hits
            <?php if ($featuredSong): ?>
                <small class="text-muted d-block mt-1" style="font-family: Manrope, sans-serif; font-size: 0.84rem;">
                    Spotlight: <?= htmlspecialchars($featuredSong['title']) ?> by <?= htmlspecialchars($featuredSong['artist_name'] ?? 'Unknown Artist') ?>
                </small>
            <?php endif; ?>
        </h2>

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

        function initHomeBannerSlider() {
            const slider = document.getElementById('promoSlider');
            const track = document.getElementById('promoTrack');
            const dotsWrap = document.getElementById('promoDots');
            const prev = document.getElementById('promoPrev');
            const next = document.getElementById('promoNext');

            if (!slider || !track || !dotsWrap) return;

            const slides = Array.from(track.children);
            if (!slides.length) return;

            let currentIndex = 0;
            let intervalId = null;

            slides.forEach((_, index) => {
                const dot = document.createElement('button');
                dot.className = 'promo-dot' + (index === 0 ? ' active' : '');
                dot.setAttribute('aria-label', `Go to banner ${index + 1}`);
                dot.addEventListener('click', () => {
                    currentIndex = index;
                    renderSlide();
                    restartAutoPlay();
                });
                dotsWrap.appendChild(dot);
            });

            const dots = Array.from(dotsWrap.children);

            function renderSlide() {
                track.style.transform = `translateX(-${currentIndex * 100}%)`;
                dots.forEach((dot, i) => dot.classList.toggle('active', i === currentIndex));
            }

            function nextSlide() {
                currentIndex = (currentIndex + 1) % slides.length;
                renderSlide();
            }

            function prevSlide() {
                currentIndex = (currentIndex - 1 + slides.length) % slides.length;
                renderSlide();
            }

            function restartAutoPlay() {
                if (intervalId) clearInterval(intervalId);
                intervalId = setInterval(nextSlide, 4500);
            }

            next?.addEventListener('click', () => {
                nextSlide();
                restartAutoPlay();
            });

            prev?.addEventListener('click', () => {
                prevSlide();
                restartAutoPlay();
            });

            slider.addEventListener('mouseenter', () => {
                if (intervalId) clearInterval(intervalId);
            });
            slider.addEventListener('mouseleave', restartAutoPlay);

            renderSlide();
            restartAutoPlay();
        }

        function focusSearchInput() {
            const searchInput = document.getElementById('searchInput');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            if (!searchInput) return;

            if (window.innerWidth <= 768 && sidebar && !sidebar.classList.contains('open')) {
                sidebar.classList.add('open');
                if (overlay) overlay.classList.add('active');
            }

            searchInput.focus();
            searchInput.select();
            searchInput.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        function bindSearchCtaButtons() {
            const buttons = document.querySelectorAll('.search-cta-btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    focusSearchInput();
                    history.replaceState({}, '', 'index.php?focus_search=1');
                });
            });
        }

        // Load most played songs on page load if user is logged in
        document.addEventListener('DOMContentLoaded', function() {
            initHomeBannerSlider();
            bindSearchCtaButtons();

            const shouldFocusSearch = new URLSearchParams(window.location.search).get('focus_search');
            if (shouldFocusSearch === '1') {
                setTimeout(focusSearchInput, 120);
            }
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

