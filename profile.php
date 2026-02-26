<?php
session_start();
require_once 'config/db.php';
require_once 'includes/premium_check.php';

$BASE_URL = '/Spotify-clone-new/Spotify-clone';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

/* ================= PROFILE IMAGE UPLOAD ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {

    $file = $_FILES['profile_picture'];

    if ($file['error'] === UPLOAD_ERR_OK) {

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (in_array($file['type'], $allowedTypes)) {

            $uploadDir = __DIR__ . '/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'profile_' . $userId . '_' . time() . '.' . $extension;
            $dbPath = 'uploads/profiles/' . $fileName;

            // delete old image
            $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $old = $stmt->fetchColumn();

            if ($old && file_exists(__DIR__ . '/' . $old)) {
                unlink(__DIR__ . '/' . $old);
            }

            if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$dbPath, $userId]);
                header("Location: profile.php");
                exit;
            }
        }
    }
}

/* ================= USER DATA ================= */
$stmt = $pdo->prepare("
    SELECT id, username, email, mobile_number, created_at, profile_picture, is_premium, premium_expires_at
    FROM users WHERE id = ?
");
$stmt->execute([$userId]);
$profile_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile_user) {
    die('User not found');
}


/* ================= PREMIUM CHECK ================= */
$userPremium = isPremiumUser($userId);
$_SESSION['is_premium'] = $userPremium ? 1 : 0;

/* ================= PLAYLISTS ================= */
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
        ) AS image_path,
        COUNT(ups.song_id) AS song_count
    FROM playlists p
    LEFT JOIN user_playlist_songs ups ON p.id = ups.playlist_id
    WHERE p.user_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$userId]);
$playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
$playlistCount = count($playlists);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($profile_user['username']) ?> – Profile</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/assets/css/player.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/assets/css/sidebar.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/assets/css/modern.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/assets/css/notification.css">

    <style>
        :root {
            --green: #ff8a2d;
            --green-hover: #ffb067;
            --bg: #121212;
            --bg-secondary: #1a1a1a;
            --panel: #181818;
            --panel-hover: #282828;
            --text: #fff;
            --text-secondary: #b3b3b3;
            --text-muted: #6a6a6a;
            --border: #282828;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background: var(--bg);
            color: var(--text);
        }

        .main-content {
            margin-left: 280px;
            padding-bottom: 200px;
            min-height: 100vh;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 120px 30px 200px 30px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 100px 20px 180px 20px;
            }
        }

        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .profile-hero {
            background: linear-gradient(135deg, #ff8a2d 0%, #1aa34a 100%);
            position: relative;
            padding: 80px 0;
            color: var(--text);
            margin-bottom: 60px;
            overflow: hidden;
        }

        .profile-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at top right, rgba(29, 185, 84, 0.3), transparent);
            pointer-events: none;
        }

        .profile-hero-inner {
            display: flex;
            align-items: center;
            gap: 40px;
            position: relative;
            z-index: 1;
        }

        .profile-avatar {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(29, 185, 84, 0.2), rgba(29, 185, 84, 0.05));
            border: 3px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            color: rgba(255, 255, 255, 0.5);
            flex-shrink: 0;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
            transition: var(--transition);
        }

        .profile-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 25px 70px rgba(29, 185, 84, 0.3), 0 0 0 1px rgba(29, 185, 84, 0.2);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-avatar-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--green), var(--green-hover));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(29, 185, 84, 0.4);
            border: 4px solid var(--bg);
            font-size: 20px;
            transition: var(--transition);
        }

        .profile-avatar-upload:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 32px rgba(29, 185, 84, 0.5);
        }

        .profile-info h1 {
            font-size: 56px;
            font-weight: 900;
            letter-spacing: -1px;
            margin: 10px 0;
            line-height: 1.1;
        }

        .profile-info>span {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 700;
        }

        .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            margin-top: 16px;
            margin-right: 12px;
        }

        .profile-badge.premium {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .profile-badge.upgrade {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: #000;
            border: none;
            text-decoration: none;
            transition: var(--transition);
        }

        .profile-badge.upgrade:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 152, 0, 0.3);
        }

        .profile-stats {
            margin-top: 20px;
            display: flex;
            gap: 30px;
            font-size: 14px;
        }

        .profile-stat {
            display: flex;
            flex-direction: column;
        }

        .profile-stat strong {
            font-size: 24px;
            font-weight: 700;
            color: var(--green);
            line-height: 1;
        }

        .profile-stat span {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 6px;
        }

        .profile-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .edit-profile-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--green), var(--green-hover));
            color: #000;
            border: none;
            border-radius: 24px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
        }

        .edit-profile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 138, 45, 0.3);
            color: #000;
        }

        .profile-details-section {
            background: linear-gradient(135deg, var(--panel) 0%, transparent 100%);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 30px;
            margin-top: 40px;
            margin-bottom: 60px;
        }

        .profile-details-section h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--text);
        }

        .profile-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .profile-detail-item {
            display: flex;
            flex-direction: column;
        }

        .profile-detail-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .profile-detail-value {
            font-size: 16px;
            color: var(--text);
            font-weight: 500;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 60px 0 30px 0;
        }

        .section-header h2 {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0;
            padding-bottom: 12px;
            border-bottom: 4px solid var(--green);
            display: inline-block;
        }

        .playlists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 24px;
        }

        @media (max-width: 1024px) {
            .playlists-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .playlists-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            }
        }

        .playlist-card {
            background: linear-gradient(135deg, var(--panel) 0%, #2a2a2a 100%);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            text-decoration: none;
            color: var(--text);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .playlist-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 0%, rgba(29, 185, 84, 0.15), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .playlist-card:hover {
            background: linear-gradient(135deg, #282828 0%, #333333 100%);
            transform: translateY(-8px);
            border-color: var(--green);
            box-shadow: 0 12px 24px rgba(29, 185, 84, 0.2);
        }

        .playlist-card:hover::before {
            opacity: 1;
        }

        .playlist-card-image {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 14px;
            transition: var(--transition);
        }

        .playlist-card:hover .playlist-card-image {
            transform: scale(1.05);
        }

        .playlist-card-title {
            font-weight: 700;
            font-size: 14px;
            line-height: 1.3;
            margin-bottom: 6px;
            color: var(--text);
            transition: var(--transition);
        }

        .playlist-card:hover .playlist-card-title {
            color: var(--green-hover);
        }

        .playlist-card-subtitle {
            font-size: 12px;
            color: var(--text-muted);
            transition: var(--transition);
        }

        .playlist-card:hover .playlist-card-subtitle {
            color: var(--text-secondary);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: linear-gradient(135deg, var(--panel) 0%, transparent 100%);
            border-radius: 12px;
            border: 1px dashed var(--border);
            margin: 40px 0;
        }

        .empty-state-icon {
            font-size: 64px;
            color: var(--text-muted);
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state-text {
            color: var(--text-secondary);
            font-size: 16px;
            margin-bottom: 24px;
        }

        .empty-state-action {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--green), var(--green-hover));
            color: #000;
            border-radius: 24px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .empty-state-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(29, 185, 84, 0.3);
        }

        @media (max-width: 768px) {
            .profile-hero-inner {
                flex-direction: column;
                text-align: center;
                gap: 30px;
            }

            .profile-info h1 {
                font-size: 40px;
            }

            .profile-avatar {
                width: 160px;
                height: 160px;
            }

            .section-header h2 {
                font-size: 24px;
            }

            .profile-stats {
                justify-content: center;
            }
        }
    </style>
</head>

<body>

    <?php include 'partials/header.php'; ?>
    <?php include 'partials/sidebar.php'; ?>

    <div class="main-content">

        <div class="profile-hero">
            <div class="container-fluid profile-container profile-hero-inner">

                <div style="position: relative; display: flex; justify-content: center;">
                    <div class="profile-avatar">
                       
                        <?php if ($profile_user['profile_picture']): ?>
                            <img src="<?= $BASE_URL . '/' . $profile_user['profile_picture'] . '?v=' . time() ?>">
                        <?php else: ?>
                            <i class="bi bi-person-fill"></i>
                        <?php endif; ?>
                    </div>

                    <form method="POST" enctype="multipart/form-data"
                        style="position: absolute; bottom: 0; right: 0; display: flex;">
                        <label class="profile-avatar-upload" style="margin: 0;">
                            <i class="bi bi-camera-fill text-white"></i>
                            <input type="file" name="profile_picture" hidden accept="image/*"
                                onchange="this.form.submit()">
                        </label>
                    </form>
                </div>

                <div class="profile-info">
                    <span>Profile</span>
                    <h1><?= htmlspecialchars($profile_user['username']) ?></h1>

                    <div>
                        <?php if ($userPremium): ?>
                            <span class="profile-badge premium"><i class="bi bi-star-fill"></i> Premium Active</span>
                        <?php else: ?>
                            <a href="premium.php" class="profile-badge upgrade text-decoration-none"><i
                                    class="bi bi-star"></i> Upgrade to Premium</a>
                        <?php endif; ?>
                    </div>

                    <div class="profile-stats">
                        <div class="profile-stat">
                            <strong><?= $playlistCount ?></strong>
                            <span>Playlists</span>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <a href="edit-profile.php" class="edit-profile-btn">
                            <i class="bi bi-pencil-fill"></i>Edit Profile
                        </a>
                    </div>
                </div>

            </div>
        </div>

        <div class="container-fluid profile-container">

            <div class="profile-details-section">
                <h3><i class="bi bi-info-circle me-2" style="color: var(--green);"></i>Account Information</h3>
                <div class="profile-detail-grid">
                    <div class="profile-detail-item">
                        <div class="profile-detail-label">Username</div>
                        <div class="profile-detail-value"><?= htmlspecialchars($profile_user['username']) ?></div>
                    </div>
                    <div class="profile-detail-item">
                        <div class="profile-detail-label">Email Address</div>
                        <div class="profile-detail-value"><?= htmlspecialchars($profile_user['email']) ?></div>
                    </div>
                    <div class="profile-detail-item">
                        <div class="profile-detail-label">Mobile Number</div>
                        <div class="profile-detail-value">
                            <?php if ($profile_user['mobile_number']): ?>
                                <?= htmlspecialchars($profile_user['mobile_number']) ?>
                            <?php else: ?>
                                <span style="color: var(--text-muted);">Not provided</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="profile-detail-item">
                        <div class="profile-detail-label">Member Since</div>
                        <div class="profile-detail-value"><?= date('M d, Y', strtotime($profile_user['created_at'])) ?></div>
                    </div>
                    <div class="profile-detail-item">
                        <div class="profile-detail-label">Account Status</div>
                        <div class="profile-detail-value">
                            <?php if ($userPremium): ?>
                                <span style="color: var(--green);"><i class="bi bi-star-fill me-1"></i>Premium</span>
                            <?php else: ?>
                                <span style="color: var(--text-secondary);">Free</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-header">
                <h2>Your Playlists</h2>
            </div>

            <?php if ($playlistCount > 0): ?>
                <div class="playlists-grid">
                    <?php foreach ($playlists as $p): ?>
                        <a href="pages/playlist.php?id=<?= $p['id'] ?>" class="playlist-card">
                            <img src="<?= $BASE_URL . '/' . ($p['image_path'] ?: 'assets/default-playlist.png') ?>"
                                class="playlist-card-image" alt="<?= htmlspecialchars($p['name']) ?>">
                            <strong class="playlist-card-title"><?= htmlspecialchars($p['name']) ?></strong>
                            <div class="playlist-card-subtitle"><?= $p['song_count'] ?> songs</div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-music-note-list"></i>
                    </div>
                    <div class="empty-state-text">No playlists yet. Create your first playlist!</div>
                    <a href="index.php" class="empty-state-action">Explore Music</a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php include 'partials/player.php'; ?>
    <script src="<?= $BASE_URL ?>/assets/js/player.js"></script>
    <script src="<?= $BASE_URL ?>/assets/js/sidebar.js"></script>

</body>

</html>
