<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
$userLoggedIn = isset($_SESSION['user_id']);
$userPremium = false;

if ($userLoggedIn) {
    $stmt = $pdo->prepare("
        SELECT is_premium, premium_expires_at
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check premium + expiry
    if ($user && $user['is_premium'] == 1) {
        if ($user['premium_expires_at'] === null || strtotime($user['premium_expires_at']) > time()) {
            $userPremium = true;
        }
    }

    // Update session to keep it in sync
    $_SESSION['is_premium'] = $userPremium ? 1 : 0;
}
?>

<link rel="stylesheet" href="<?= $BASE_URL ?>/assets/css/notification.css">
<script src="<?= $BASE_URL ?>/assets/js/notification.js"></script>
<script>
    const IS_LOGGED_IN = <?= $userLoggedIn ? 'true' : 'false' ?>;
    // Ensure BASE_URL is available globally for JS if not already
    if (typeof BASE_URL === 'undefined') {
        const BASE_URL = "<?= $BASE_URL ?>";
    }
</script>

<header class="header">
    <div class="header-left">
        <span class="header-brand">PulseWave</span>
    </div>
    <div class="header-right">
        
        <a href="<?= $BASE_URL ?>/premium.php" class="premium-button">Premium</a>
        <?php if ($userLoggedIn): ?>
            <a href="<?= $BASE_URL ?>/profile.php" class="header-link">
                <i class="bi bi-person-circle"></i>
                <span>Profile</span>
            </a>
            <a href="<?= $BASE_URL ?>/logout.php" class="header-link">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        <?php else: ?>
            <a href="<?= $BASE_URL ?>/login.php" class="header-link">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Login</span>
            </a>
            <a href="<?= $BASE_URL ?>/register.php" class="header-link">
                <i class="bi bi-person-plus-fill"></i>
                <span>Register</span>
            </a>
        <?php endif; ?>
    </div>
</header>
<style>
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 30px;
    background: rgba(12, 16, 29, 0.65);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    position: fixed;
    top: 0;
    z-index: 100;
    left: 280px;
    width: calc(100% - 280px);
}
.header-brand {
    font-size: 13px;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: rgba(244, 248, 255, 0.8);
    font-weight: 700;
}

#player-toggle {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

#player-toggle:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: var(--accent);
    transform: scale(1.05);
}
.header-right .header-link {
    color: var(--text);
    text-decoration: none;
    margin-left: 20px;
    font-weight: 500;
    transition: color 0.2s ease;
}
.header-right .header-link:hover {
    color: var(--accent-soft);
}
.header-right .header-link span {
    margin-left: 5px;
}
.premium-button {
    background: linear-gradient(135deg, var(--accent), var(--accent-soft));
    color: #130d06;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    margin-left: 20px;
    box-shadow: 0 8px 20px rgba(255, 138, 45, 0.28);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.premium-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(255, 138, 45, 0.36);
}
.premium-active {
    background-color: #666;
    cursor: default;
}
.premium-active:hover {
    background-color: #666;
}

@media (max-width: 768px) {
    .header {
        left: 0;
        width: 100%;
        padding: 12px 16px;
    }
    .header-brand {
        display: none;
    }
}
</style>

