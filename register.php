<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $mobile_number = trim($_POST['mobile_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate username
    if (empty($username)) {
        $error = "Username is required.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters long.";
    } elseif (strlen($username) > 20) {
        $error = "Username must be less than 20 characters long.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Username can only contain letters, numbers, and underscores.";
    } elseif (empty($email)) {
        $error = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (empty($mobile_number)) {
        $error = "Mobile number is required.";
    } elseif (!preg_match('/^[\d\s\-\+]{10,20}$/', $mobile_number)) {
        $error = "Please enter a valid mobile number (10-20 digits, spaces and hyphens allowed).";
    } elseif (empty($password)) {
        $error = "Password is required.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $error = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
    } elseif (empty($confirm_password)) {
        $error = "Please confirm your password.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);
            $existing_user = $stmt->fetch();

            if ($existing_user) {
                if (strcasecmp($existing_user['username'], $username) === 0) {
                    $error = "This username is already taken. Please choose a different one.";
                } else {
                    $error = "This email address is already registered. Please use a different email or try logging in.";
                }
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, mobile_number, password_hash, created_at) VALUES (?, ?, ?, ?, NOW())");
                if ($stmt->execute([$username, $email, $mobile_number, $password_hash])) {
                    $success = "Registration successful! You can now <a href='login.php'>login</a>.";
                } else {
                    $error = "Something went wrong during registration. Please try again later.";
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = "Username or email already exists. Please use different details.";
            } else {
                error_log("Registration error: " . $e->getMessage());
                $error = "Something went wrong during registration. Please try again later.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PulseWave</title>
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

        .register-container {
            display: flex;
            width: 100%;
            max-width: 1200px;
            min-height: 700px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
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
            overflow-y: auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
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
            margin-bottom: 18px;
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

        .register-btn {
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

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(29, 185, 84, 0.3);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: #535353;
            font-size: 0.95rem;
        }

        .login-link a {
            color: #ff8a2d;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
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

        .password-strength {
            margin-top: 5px;
            font-size: 0.8rem;
            color: #666;
        }

        .password-strength.weak {
            color: #e74c3c;
        }

        .password-strength.medium {
            color: #f39c12;
        }

        .password-strength.strong {
            color: #27ae60;
        }

        @media (max-width: 768px) {
            .register-container {
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

        .register-container {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="left-panel">
        <div class="brand">
            <h1>🎶</h1>
            <h1>Join the Music</h1>
            <p>Create your account and unlock a world of personalized music discovery, unlimited playlists, and seamless streaming.</p>
        </div>
    </div>

    <div class="right-panel">
        <div class="logo">
            <div style="font-weight:700;font-size:1.7rem;letter-spacing:.08em;text-transform:uppercase;color:#0f1324;">PulseWave</div>
        </div>

        <div class="form-title">
            <h2>Create Account</h2>
            <p>Join millions of music lovers worldwide</p>
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
                <?= $success ?>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showNotification('success', 'Registration successful! You can now <a href="login.php">login</a>.');
                });
            </script>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-input" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Choose a unique username" required>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="Enter your email address" required>
            </div>

            <div class="form-group">
                <label for="mobile_number" class="form-label">Mobile Number</label>
                <input type="tel" id="mobile_number" name="mobile_number" class="form-input" value="<?= htmlspecialchars($_POST['mobile_number'] ?? '') ?>" placeholder="Enter your mobile number (e.g., +1234567890)" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Create a strong password" required>
                <div id="passwordStrength" class="password-strength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm your password" required>
            </div>

            <button type="submit" class="register-btn">
                <i class="bi bi-person-plus-fill me-2"></i>
                Create Account
            </button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign in here</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Password strength indicator
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const strengthIndicator = document.getElementById('passwordStrength');

        if (password.length === 0) {
            strengthIndicator.textContent = '';
            strengthIndicator.className = 'password-strength';
            return;
        }

        let strength = 0;
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        switch(strength) {
            case 0:
            case 1:
                strengthIndicator.textContent = 'Weak password';
                strengthIndicator.className = 'password-strength weak';
                break;
            case 2:
            case 3:
                strengthIndicator.textContent = 'Medium strength';
                strengthIndicator.className = 'password-strength medium';
                break;
            case 4:
            case 5:
                strengthIndicator.textContent = 'Strong password';
                strengthIndicator.className = 'password-strength strong';
                break;
        }
    });

    // Form validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (password !== confirmPassword) {
            e.preventDefault();
            showNotification('error', 'Passwords do not match!');
            return false;
        }

        if (password.length < 8) {
            e.preventDefault();
            showNotification('error', 'Password must be at least 8 characters long!');
            return false;
        }
    });
</script>
</body>
</html>


