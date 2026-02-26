<style>
    .admin-sidebar {
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        width: 240px;
        padding: 24px 16px;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .admin-sidebar .logo {
        margin-bottom: 12px;
        display: inline-block;
    }

    .admin-sidebar .nav-link {
        text-decoration: none;
        font-size: 15px;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .admin-sidebar hr {
        border-color: rgba(255, 255, 255, 0.1);
        margin: 8px 0;
    }
</style>

<div class="admin-sidebar">
    <a href="dashboard.php" class="logo">PulseWave Admin</a>

    <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <a href="artists.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'artists.php' ? 'active' : '' ?>">
        <i class="bi bi-mic"></i> Artists
    </a>

    <a href="songs.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'songs.php' ? 'active' : '' ?>">
        <i class="bi bi-music-note-list"></i> Songs
    </a>

    <a href="albums.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'albums.php' ? 'active' : '' ?>">
        <i class="bi bi-collection"></i> Albums
    </a>

    <a href="users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
        <i class="bi bi-people"></i> Users
    </a>

    <hr>

    <a href="logout.php" class="nav-link">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</div>


