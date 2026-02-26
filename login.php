<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Check for password reset success message
if (isset($_GET['password_reset']) && $_GET['password_reset'] === 'success') {
    $success = "Password reset successful! You can now login with your new password.";
}

// Check for session expiry message
if (isset($_GET['reset_expired']) && $_GET['reset_expired'] === '1') {
    $error = "Password reset session expired. Please try again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validate input fields
    if (empty($username)) {
        $error = "Please enter your username or email address.";
    } elseif (empty($password)) {
        $error = "Please enter your password.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Check if input is email or username
        $is_email = filter_var($username, FILTER_VALIDATE_EMAIL);

        if ($is_email) {
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
        } else {
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
        }

        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            if ($is_email) {
                $error = "No account found with this email address. Please check your email or register for a new account.";
            } else {
                $error = "No account found with this username. Please check your username or register for a new account.";
            }
        } elseif (!password_verify($password, $user['password_hash'])) {
            $error = "Incorrect password. Please try again or reset your password.";
        } else {
            // Successful login
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: index.php');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PulseWave</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/notification.css">
    <script src="assets/js/notification.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #121212 0%, #1a1a1a 50%, #121212 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(255, 138, 45, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(53, 195, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 138, 45, 0.08) 0%, transparent 50%);
            pointer-events: none;
        }

        .login-container {
            display: flex;
            width: 100%;
            max-width: 1200px;
            min-height: 600px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            /* border-radius: 20px; */
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .left-panel {
            flex: 1;
            background: linear-gradient(135deg, #ff8a2d 0%, #ffb067 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            color: white;
            position: relative;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="music" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="2" fill="rgba(255,255,255,0.1)"/><path d="M5 15 Q10 10 15 15" stroke="rgba(255,255,255,0.1)" stroke-width="1" fill="none"/></pattern></defs><rect width="100" height="100" fill="url(%23music)"/></svg>');
            opacity: 0.3;
        }

        .brand {
            text-align: center;
            z-index: 1;
            position: relative;
        }

        .brand h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .brand p {
            font-size: 1.2rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .right-panel {
            flex: 1;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo img {
            width: 180px;
            filter: brightness(0);
        }

        .form-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-title h2 {
            font-size: 2rem;
            font-weight: 600;
            color: #191414;
            margin-bottom: 10px;
        }

        .form-title p {
            color: #535353;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #191414;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-input:focus {
            outline: none;
            border-color: #ff8a2d;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 138, 45, 0.12);
        }

        .form-input::placeholder {
            color: #b3b3b3;
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ff8a2d 0%, #ffb067 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(29, 185, 84, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
            color: #b3b3b3;
            font-size: 0.9rem;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e0e0e0;
        }

        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            z-index: 1;
        }

        .register-link {
            text-align: center;
            margin-top: 30px;
            color: #535353;
            font-size: 0.95rem;
        }

        .register-link a {
            color: #ff8a2d;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #ffb067;
            text-decoration: underline;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                margin: 20px;
                min-height: auto;
            }

            .left-panel {
                padding: 40px 30px;
                min-height: 300px;
            }

            .right-panel {
                padding: 40px 30px;
            }

            .brand h1 {
                font-size: 2rem;
            }

            .form-title h2 {
                font-size: 1.5rem;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-container {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="left-panel">
        <div class="brand">
            <h1>🎵</h1>
            <h1>Welcome Back</h1>
            <p>Build mixes, discover artists, and keep every vibe in one place.</p>
        </div>
    </div>

    <div class="right-panel">
        <div class="logo">
            <div style="font-weight:700;font-size:1.7rem;letter-spacing:.08em;text-transform:uppercase;color:#0f1324;">PulseWave</div>
        </div>

        <div class="form-title">
            <h2>Sign In</h2>
            <p>Enter your credentials to access your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showNotification('error', '<?= addslashes(htmlspecialchars($error)) ?>');
                });
            </script>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($success) ?>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showNotification('success', '<?= addslashes(htmlspecialchars($success)) ?>');
                });
            </script>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username" class="form-label">Username or Email</label>
                <input type="text" id="username" name="username" class="form-input" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Enter your username or email" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                <div style="text-align: right; margin-top: 10px;">
                    <a href="forgot-password.php" style="color: #ff8a2d; text-decoration: none; font-size: 0.9rem; font-weight: 500;">
                        <i class="bi bi-key-fill me-1"></i>Forgot Password?
                    </a>
                </div>
            </div>

            <button type="submit" class="login-btn">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Sign In
            </button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="register.php">Create one here</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


