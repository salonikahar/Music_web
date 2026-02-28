<?php
require_once 'auth_check.php';
require_once '../config/db.php';

/* ================= STATS ================= */
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$recentUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

/* ================= PAGINATION ================= */
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $items_per_page;

/* ================= FETCH USERS ================= */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM users";
$count_params = [];

if (!empty($search)) {
    $count_query .= " WHERE username LIKE :search_username OR email LIKE :search_email";
    $count_params = [
        ':search_username' => "%$search%",
        ':search_email' => "%$search%"
    ];
}

$total_items = $pdo->prepare($count_query);
$total_items->execute($count_params);
$total_items = $total_items->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

// Fetch users with pagination
$query = "SELECT id, username, email, created_at, is_premium, premium_expires_at FROM users";
$params = [];

if (!empty($search)) {
    $query .= " WHERE username LIKE :search_username OR email LIKE :search_email";
    $params = [
        ':search_username' => "%$search%",
        ':search_email' => "%$search%"
    ];
}

$query .= " ORDER BY id ASC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
if (!empty($params)) {
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= PREMIUM CHECK FUNCTION ================= */
function isUserPremium($user) {
    if ($user['is_premium'] == 1) {
        if ($user['premium_expires_at'] === null || strtotime($user['premium_expires_at']) > time()) {
            return true;
        }
    }
    return false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-theme.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <link rel="stylesheet" href="../assets/css/admin-pagination.css">
    <script src="../assets/js/notification.js"></script>
    <style>
        body { background: #121212; color: white; margin: 0; padding: 0; }
        .main-content { margin-left: 240px; padding: 24px; }
        .stat-card { background: #181818; border-radius: 12px; padding: 20px; text-align: center; border: 1px solid #333; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #1DB954; }
        .stat-label { font-size: 0.9rem; color: #b3b3b3; }
        .search-box { background: #181818; border: 1px solid #333; border-radius: 25px; padding: 8px 16px; color: white; }
        .search-box::placeholder { color: #888; }
        .table { background: #181818; border-radius: 8px; overflow: hidden; }
        .table thead th { background: #282828; border-bottom: 1px solid #333; font-weight: 600; }
        .table tbody tr { border-bottom: 1px solid #333; }
        .table tbody tr:hover { background: rgba(255,255,255,0.05); }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #1DB954; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; color: white; }
        .btn-outline-primary { border-color: #1DB954; color: #1DB954; }
        .btn-outline-primary:hover { background: #1DB954; color: white; }
    </style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Manage Users</h2>
        
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="stat-card">
                <div class="stat-number"><?= number_format($userCount) ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card">
                <div class="stat-number"><?= number_format($recentUsers) ?></div>
                <div class="stat-label">New Users (30 days)</div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="mb-4">
        <form method="GET" class="d-flex">
            <input type="text" name="search" class="form-control search-box me-2" placeholder="Search users by username or email..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-search"></i>
            </button>
            <?php if (!empty($search)): ?>
                <a href="users.php" class="btn btn-outline-secondary ms-2">
                    <i class="bi bi-x-circle"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Users Table -->
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th width="60">ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Premium</th>
                    <th>Registered</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bi bi-people-fill fs-1 d-block mb-2"></i>
                            No users found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <span class="badge bg-secondary">#<?= htmlspecialchars($user['id']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-3">
                                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($user['username']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($user['email']) ?>
                                </a>
                            </td>
                            <td>
                                <?php if (isUserPremium($user)): ?>
                                    <span class="badge bg-success"><i class="bi bi-star-fill"></i> Premium Member</span>
                                <?php else: ?>
                                    <span class="text-secondary">Free</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-light fw-bold">
                                    <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($users)): ?>
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-center mt-4">
            <nav aria-label="Users pagination">
                <ul class="pagination admin-pagination mb-0">
                    <!-- Previous -->
                    <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    <!-- Page numbers -->
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next -->
                    <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search) ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>

        <div class="admin-pagination-info">
            Showing <?= count($users) ?> of <?= $total_items ?> users
            (Page <?= $current_page ?> of <?= $total_pages ?>)
        </div>
    <?php endif; ?>
</div>

<script>
function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        // Implement delete functionality
        showNotification('info', 'Delete functionality not implemented yet');
    }
}
</script>

</body>
</html>


