<?php
session_start();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$ADMIN_USERNAME = 'admin';
$HASHED_PASSWORD = '$2y$10$m9fjNUuvVSGKleJ0Z2PdIurXsPzT9tZ.QcguslfeCHythql35YGk.'; // admin123

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username === $ADMIN_USERNAME && password_verify($password, $HASHED_PASSWORD)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: dashboard.php');
        exit();
    }

    $error = "Invalid username or password.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - PulseWave</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-theme.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <script src="../assets/js/notification.js"></script>
    <style>
        .admin-login-page .sidebar {
            background: linear-gradient(180deg, #0c1225 0%, #0b1020 100%);
            min-height: 100vh;
            color: #f4f8ff;
            padding: 28px;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        .admin-login-page .main-content {
            padding: 80px 40px;
        }

        .admin-login-page .login-box {
            max-width: 420px;
            margin: 0 auto;
            padding: 30px;
        }

        .brand-logo {
            font-size: 1.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #f4f8ff;
        }

        .logo {
            font-size: 1.4rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #f4f8ff;
        }
    </style>
</head>
<body class="admin-login-page">

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4 col-lg-3 sidebar d-flex flex-column justify-content-between">
            <div>
                <div class="brand-logo">PulseWave Admin</div>
                <p class="mt-4">Manage your music library,<br>albums, and users.</p>
            </div>
            <div>
                <p>&copy; <?= date('Y') ?> PulseWave</p>
            </div>
        </div>

        <div class="col-md-8 col-lg-9 main-content">
            <div class="login-box">
                <div class="logo text-center mb-4">PulseWave</div>
                <h3 class="text-center mb-4">Admin Login</h3>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showNotification('error', '<?= addslashes(htmlspecialchars($error)) ?>');
                        });
                    </script>
                <?php endif; ?>

                <?php if (isset($_GET['logged_out']) && $_GET['logged_out'] == 1): ?>
                    <div class="alert alert-info">You have been logged out.</div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
