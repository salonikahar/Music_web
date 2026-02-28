<?php
session_start();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$ADMIN_USERNAME = 'admin';
$HASHED_PASSWORD = '$2y$10$m9fjNUuvVSGKleJ0Z2PdIurXsPzT9tZ.QcguslfeCHythql35YGk.'; // admin123
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Username and password are required.";
    } elseif ($username === $ADMIN_USERNAME && password_verify($password, $HASHED_PASSWORD)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Invalid username or password.";
    }
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="../assets/js/notification.js"></script>
    <style>
        .admin-login-page .admin-login-shell {
            min-height: 100vh;
        }

        .admin-login-page .admin-login-sidebar {
            background:
                radial-gradient(400px 230px at 10% 0%, rgba(55, 201, 255, 0.16), transparent 60%),
                linear-gradient(180deg, #0c1225 0%, #0b1020 100%);
            min-height: 100vh;
            color: #f4f8ff;
            padding: 36px 28px;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        .admin-login-page .admin-login-main {
            min-height: 100vh;
            margin-left: 0 !important;
            padding: 40px 28px !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .admin-login-page .login-box {
            width: 100%;
            max-width: 460px;
            margin: 0 auto;
            padding: 34px 30px;
        }

        .brand-logo {
            font-size: 1.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #f4f8ff;
        }

        .logo-mark {
            width: 64px;
            height: 64px;
            margin: 0 auto 14px;
            border-radius: 16px;
            background: linear-gradient(145deg, rgba(255, 138, 45, 0.27), rgba(255, 176, 103, 0.18));
            border: 1px solid rgba(255, 176, 103, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffb067;
            font-size: 1.6rem;
        }

        .logo-text {
            font-size: 1.3rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #f4f8ff;
        }

        .login-title {
            text-align: center;
            margin: 4px 0 4px;
            font-weight: 700;
        }

        .login-subtitle {
            text-align: center;
            color: #b8c2de;
            font-size: 0.95rem;
            margin-bottom: 22px;
        }

        .form-label {
            color: #e8eeff;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap .form-control {
            padding-right: 44px;
            height: 48px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #9faad0;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password:hover {
            color: #f4f8ff;
            background: rgba(255, 255, 255, 0.08);
        }

        .btn-login {
            width: 100%;
            height: 48px;
            border: 0;
            border-radius: 10px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--admin-accent), var(--admin-accent-soft));
            color: #130d06;
            box-shadow: 0 10px 20px rgba(255, 138, 45, 0.25);
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            filter: brightness(1.04);
        }

        .admin-note {
            color: #8f9bc2;
            font-size: 0.85rem;
            margin-top: 12px;
            text-align: center;
        }

        .sidebar-copy {
            color: #bdc8e6;
            max-width: 300px;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .admin-login-page .admin-login-sidebar {
                min-height: auto;
                padding: 20px 18px;
            }

            .admin-login-page .admin-login-main {
                min-height: auto;
                padding: 26px 16px 34px !important;
            }

            .admin-login-page .login-box {
                padding: 26px 20px;
            }
        }
    </style>
</head>
<body class="admin-login-page">

<div class="container-fluid">
    <div class="row admin-login-shell">
        <div class="col-md-4 col-lg-3 admin-login-sidebar d-flex flex-column justify-content-between">
            <div>
                <div class="brand-logo">PulseWave Admin</div>
                <p class="mt-4 sidebar-copy">Manage your songs, albums, artists, and platform data from one secure admin panel.</p>
            </div>
            <div>
                <p>&copy; <?= date('Y') ?> PulseWave</p>
            </div>
        </div>

        <div class="col-md-8 col-lg-9 admin-login-main">
            <div class="login-box">
                <div class="logo-mark">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div class="logo-text text-center">PulseWave</div>
                <h3 class="login-title">Admin Login</h3>
                <p class="login-subtitle">Sign in to continue to the dashboard</p>

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

                <form method="POST" autocomplete="off" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control"
                            value="<?= htmlspecialchars($username) ?>"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-wrap">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                required
                                autocomplete="current-password"
                            >
                            <button class="toggle-password" type="button" id="togglePassword" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-login">Login</button>
                </form>
                <p class="admin-note">Only authorized administrators can access this area.</p>
            </div>
        </div>
    </div>
</div>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            this.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    }
</script>

</body>
</html>
