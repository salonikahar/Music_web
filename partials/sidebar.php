<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/premium_check.php';

$userLoggedIn = isset($_SESSION['user_id']);
$path_prefix = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';
$isPremium = false;

// Update premium status in session
if ($userLoggedIn) {
    $isPremium = isPremiumUser($_SESSION['user_id']);
    $_SESSION['is_premium'] = $isPremium ? 1 : 0;
}
?>

<div class="sidebar" id="sidebar">
    <div class="logo-container">
        <a href="/Spotify-clone-new/Spotify-clone/index.php" class="logo app-logo">PulseWave</a>
        <button class="sidebar-toggle d-md-none" onclick="toggleSidebar()">&times;</button>
    </div>

    <a href="/Spotify-clone-new/Spotify-clone/index.php" class="nav-link">
        <i class="bi bi-house"></i> Home
    </a>

    <div class="search-box">
        <i class="bi bi-search"></i>
        <!-- <input type="text" id="searchInput" placeholder="Search songs or artists..." onkeyup="searchSongs(this.value)"> -->
        <input type="text" id="searchInput"
       placeholder="Search songs or artists..."
       onkeyup="searchSongs(this.value)">

    </div>

    <div id="searchResults" class="search-results"></div>


    <a href="<?php echo $path_prefix; ?>pages/library.php" class="nav-link" id="libraryNavLink">
        <i class="bi bi-collection"></i> Your Library
    </a>

    <a href="<?php echo $isPremium ? '#' : $path_prefix . 'premium.php'; ?>" class="nav-link <?php echo $isPremium ? 'premium-active' : ''; ?>">
        <i class="bi bi-star"></i> <?php echo $isPremium ? 'Premium Active' : 'Premium'; ?>
    </a>

    <?php if ($userLoggedIn): ?>
        <a href="<?php echo $path_prefix; ?>logout.php" class="nav-link">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    <?php else: ?>
        <a href="<?php echo $path_prefix; ?>login.php" class="nav-link">
            <i class="bi bi-box-arrow-in-right"></i> Login
        </a>
    <?php endif; ?>

    <?php if ($userLoggedIn): ?>
        <div class="user-section">
            <?php
            // Get user info
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $isPremium = isPremiumUser($_SESSION['user_id']);
            ?>
            <a href="<?php echo $path_prefix; ?>profile.php" class="user-info">
                <div class="user-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                    <div class="user-status">
                        <?php echo $isPremium ? 'Premium' : 'Free Account'; ?>
                    </div>
                </div>
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="overlay d-md-none" id="overlay" onclick="toggleSidebar()"></div>

<script>
(() => {
    const isLoggedIn = <?= $userLoggedIn ? 'true' : 'false' ?>;
    const isPremium = <?= $isPremium ? 'true' : 'false' ?>;
    const libraryLink = document.getElementById('libraryNavLink');
    if (!libraryLink) return;

    libraryLink.addEventListener('click', function (e) {
        if (!isLoggedIn) {
            e.preventDefault();
            if (typeof showNotification === 'function') {
                showNotification('warning', 'Please login first to access Your Library.');
            } else {
                alert('Please login first to access Your Library.');
            }
            return;
        }

        if (!isPremium) {
            e.preventDefault();
            const msg = 'Create playlist is a Premium feature. Please buy Premium first.';
            if (typeof showNotification === 'function') {
                showNotification('warning', msg);
            } else {
                alert(msg);
            }
        }
    });
})();
</script>

