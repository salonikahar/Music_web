<?php
require_once '../config/db.php';
require_once __DIR__ . '/../api/saavn.php';

$BASE_URL = '/Spotify-clone-new/Spotify-clone';
$DEFAULT_IMG = 'data:image/gif;base64,R0lGODlhAQABAIAAAMLCwgAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==';

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    exit;
}

$resultsFound = false;

/* ================= LOCAL SONGS ================= */
$songStmt = $pdo->prepare("
    SELECT
        songs.id,
        songs.title,
        songs.file_path,
        songs.image_path AS cover,
        artists.name AS artist_name
    FROM songs
    LEFT JOIN artists ON songs.artist_id = artists.id
    WHERE songs.title LIKE :q OR artists.name LIKE :q
    LIMIT 4
");
$songStmt->execute(['q' => "%$q%"]);
$songs = $songStmt->fetchAll(PDO::FETCH_ASSOC);

if ($songs) {
    $resultsFound = true;
    foreach ($songs as $song) { ?>
        <div class="search-item"
             data-song='<?= json_encode($song, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'
             onclick="playSong(this)">

            <img src="<?= $song['cover'] ? $BASE_URL . $song['cover'] : $DEFAULT_IMG ?>" class="search-item-img">

            <div class="search-item-info">
                <div class="search-item-title"><?= htmlspecialchars($song['title']) ?></div>
                <div class="search-item-type">Song</div>
            </div>

            <a href="<?= $BASE_URL . '/' . $song['file_path'] ?>" download class="search-item-download">
                <i class="bi bi-download"></i>
            </a>
        </div>
    <?php }
}

/* ================= ALBUMS ================= */
$albumStmt = $pdo->prepare("
    SELECT id, name, image_path, display_type, bg_color
    FROM albums
    WHERE name LIKE :q
    LIMIT 3
");
$albumStmt->execute(['q' => "%$q%"]);
$albums = $albumStmt->fetchAll(PDO::FETCH_ASSOC);

if ($albums) {
    $resultsFound = true;
    foreach ($albums as $album) {

        $imgSrc = $DEFAULT_IMG;

        if ($album['display_type'] === 'image' && !empty($album['image_path'])) {
            $imgSrc = $BASE_URL . $album['image_path'];
        } elseif ($album['display_type'] === 'color') {
            $imgSrc = 'data:image/svg+xml;base64,' . base64_encode(
                '<svg width="64" height="64" xmlns="http://www.w3.org/2000/svg">
                    <rect width="64" height="64" fill="' . htmlspecialchars($album['bg_color']) . '"/>
                 </svg>'
            );
        }
        ?>
        <a href="pages/album.php?id=<?= (int)$album['id'] ?>" class="search-item">
            <img src="<?= $imgSrc ?>" class="search-item-img">
            <div class="search-item-info">
                <div class="search-item-title"><?= htmlspecialchars($album['name']) ?></div>
                <div class="search-item-type">Album</div>
            </div>
        </a>
    <?php }
}

/* ================= ARTISTS ================= */
$artistStmt = $pdo->prepare("
    SELECT id, name, image_path
    FROM artists
    WHERE name LIKE :q
    LIMIT 3
");
$artistStmt->execute(['q' => "%$q%"]);
$artists = $artistStmt->fetchAll(PDO::FETCH_ASSOC);

if ($artists) {
    $resultsFound = true;
    foreach ($artists as $artist) { ?>
        <div class="search-item">
            <img src="<?= $artist['image_path'] ? $BASE_URL . $artist['image_path'] : $DEFAULT_IMG ?>" class="search-item-img">
            <div class="search-item-info">
                <div class="search-item-title"><?= htmlspecialchars($artist['name']) ?></div>
                <div class="search-item-type">Artist</div>
            </div>
        </div>
    <?php }
}

/* ================= SAAVN ONLINE SONGS ================= */
/* Temporarily disabled online song functionality
$saavn = saavn_api("search/songs?query=" . urlencode($q));
$saavnSongs = $saavn['data']['results'] ?? [];

if ($saavnSongs) {
    $resultsFound = true;

    foreach (array_slice($saavnSongs, 0, 4) as $song) {

        $songData = [
            "id" => "saavn_" . $song['id'],
            "title" => $song['name'],
            "artist_name" => $song['primaryArtists'],
            "file_path" => $song['downloadUrl'][4]['link'],
            "cover" => $song['image'][1]['link'],
            "source" => "saavn"
        ];
        ?>
        <div class="search-item"
             data-song='<?= json_encode($songData, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'
             onclick="playSong(this)">

            <img src="<?= $songData['cover'] ?>" class="search-item-img">

            <div class="search-item-info">
                <div class="search-item-title"><?= htmlspecialchars($songData['title']) ?></div>
                <div class="search-item-type">Online Song</div>
            </div>

            <span class="search-item-download">
                <i class="bi bi-cloud"></i>
            </span>
        </div>
    <?php }
}
*/

if (!$resultsFound) {
    echo '<div class="search-no-results">No results found.</div>';
}
