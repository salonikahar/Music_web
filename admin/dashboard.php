<?php
require_once 'auth_check.php';
require_once '../config/db.php';

// Basic counts
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$artistCount = $pdo->query("SELECT COUNT(*) FROM artists")->fetchColumn();
$songCount = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();
$albumCount = $pdo->query("SELECT COUNT(*) FROM albums")->fetchColumn();

// Recent activity (last 7 days)
$recentUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$recentSongs = $pdo->query("SELECT COUNT(*) FROM songs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// Album types distribution
$albumTypes = $pdo->query("SELECT display_type, COUNT(*) as count FROM albums GROUP BY display_type")->fetchAll(PDO::FETCH_KEY_PAIR);

// Top artists by song count
$topArtists = $pdo->query("
    SELECT a.name, COUNT(s.id) as song_count
    FROM artists a
    LEFT JOIN songs s ON a.id = s.artist_id
    GROUP BY a.id, a.name
    ORDER BY song_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Monthly user registrations (last 6 months)
$monthlyUsers = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-theme.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <script src="../assets/js/notification.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #121212; color: white; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .main-content { margin-left: 240px; padding: 24px; }
        .welcome-header { background: linear-gradient(135deg, #ff8a2d, #191414); border-radius: 16px; padding: 32px; margin-bottom: 32px; color: white; }
        .welcome-header h1 { margin: 0; font-size: 2.5rem; font-weight: 700; }
        .welcome-header p { margin: 8px 0 0 0; opacity: 0.9; font-size: 1.1rem; }
        .stat-card { background: #181818; border-radius: 16px; padding: 24px; text-align: center; border: 1px solid #333; transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.3); }
        .stat-number { font-size: 2.5rem; font-weight: bold; color: #ff8a2d; margin-bottom: 8px; }
        .stat-label { font-size: 0.9rem; color: #b3b3b3; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-icon { font-size: 2rem; color: #ff8a2d; margin-bottom: 16px; }
        .chart-container { background: #181818; border-radius: 16px; padding: 24px; margin-bottom: 24px; border: 1px solid #333; }
        .chart-container h3 { margin-bottom: 20px; color: #ff8a2d; font-weight: 600; }
        .recent-activity { background: #181818; border-radius: 16px; padding: 24px; border: 1px solid #333; }
        .activity-item { display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid #333; }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon { width: 40px; height: 40px; border-radius: 50%; background: #ff8a2d; display: flex; align-items: center; justify-content: center; margin-right: 16px; }
        .activity-content h4 { margin: 0; font-size: 1rem; color: white; }
        .activity-content p { margin: 4px 0 0 0; font-size: 0.85rem; color: #b3b3b3; }
        .top-artists { background: #181818; border-radius: 16px; padding: 24px; border: 1px solid #333; }
        .artist-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #333; }
        .artist-item:last-child { border-bottom: none; }
        .artist-info { display: flex; align-items: center; }
        .artist-avatar { width: 40px; height: 40px; border-radius: 50%; background: #ff8a2d; display: flex; align-items: center; justify-content: center; margin-right: 16px; font-weight: bold; color: white; }
        .artist-name { font-weight: 500; }
        .song-count { color: #ff8a2d; font-weight: bold; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <!-- Welcome Header -->
    <div class="welcome-header">
        <h1>Welcome back, Admin!</h1>
        <p>Here's what's happening with your PulseWave platform today.</p>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-number"><?= number_format($userCount) ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-mic-fill"></i></div>
                <div class="stat-number"><?= number_format($artistCount) ?></div>
                <div class="stat-label">Artists</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-music-note-beamed"></i></div>
                <div class="stat-number"><?= number_format($songCount) ?></div>
                <div class="stat-label">Songs</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-collection-fill"></i></div>
                <div class="stat-number"><?= number_format($albumCount) ?></div>
                <div class="stat-label">Albums</div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Content Distribution Pie Chart -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3><i class="bi bi-pie-chart-fill me-2"></i>Content Distribution</h3>
                <canvas id="contentChart" width="400" height="300"></canvas>
            </div>
        </div>

        <!-- Album Types Pie Chart -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3><i class="bi bi-diagram-3-fill me-2"></i>Album Types</h3>
                <canvas id="albumTypesChart" width="400" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Bottom Row -->
    <div class="row g-4">
        <!-- Recent Activity -->
        <div class="col-lg-6">
            <div class="recent-activity">
                <h3 class="mb-3"><i class="bi bi-activity me-2"></i>Recent Activity</h3>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <div class="activity-content">
                        <h4>New Users</h4>
                        <p><?= $recentUsers ?> users joined in the last 7 days</p>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="bi bi-music-note-beamed"></i>
                    </div>
                    <div class="activity-content">
                        <h4>New Songs</h4>
                        <p><?= $recentSongs ?> songs added in the last 7 days</p>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div class="activity-content">
                        <h4>Growth Rate</h4>
                        <p>Steady increase in user engagement</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Artists -->
        <div class="col-lg-6">
            <div class="top-artists">
                <h3 class="mb-3"><i class="bi bi-trophy me-2"></i>Top Artists by Songs</h3>
                <?php foreach ($topArtists as $artist): ?>
                    <div class="artist-item">
                        <div class="artist-info">
                            <div class="artist-avatar">
                                <?= strtoupper(substr($artist['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="artist-name"><?= htmlspecialchars($artist['name']) ?></div>
                                <div class="text-muted small">Artist</div>
                            </div>
                        </div>
                        <div class="song-count"><?= $artist['song_count'] ?> songs</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Content Distribution Pie Chart
const contentCtx = document.getElementById('contentChart').getContext('2d');
new Chart(contentCtx, {
    type: 'pie',
    data: {
        labels: ['Users', 'Artists', 'Songs', 'Albums'],
        datasets: [{
            data: [<?= $userCount ?>, <?= $artistCount ?>, <?= $songCount ?>, <?= $albumCount ?>],
            backgroundColor: [
                '#ff8a2d',
                '#37c9ff',
                '#7fd8ff',
                '#ffb067'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#b3b3b3',
                    padding: 20,
                    font: {
                        size: 12
                    }
                }
            }
        }
    }
});

// Album Types Pie Chart
const albumTypesCtx = document.getElementById('albumTypesChart').getContext('2d');
new Chart(albumTypesCtx, {
    type: 'pie',
    data: {
        labels: ['Color Albums', 'Image Albums'],
        datasets: [{
            data: [<?= $albumTypes['color'] ?? 0 ?>, <?= $albumTypes['image'] ?? 0 ?>],
            backgroundColor: [
                '#ff8a2d',
                '#37c9ff'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#b3b3b3',
                    padding: 20,
                    font: {
                        size: 12
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>



