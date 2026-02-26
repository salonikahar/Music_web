<?php
session_start();
require_once 'config/db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

// Check if user verified OTP
if (!isset($_SESSION['reset_otp_verified']) || $_SESSION['reset_otp_verified'] !== true) {
    header('Location: forgot-password.php');
    exit();
}

// Check if reset token is not too old (15 minutes timeout for security)
if (isset($_SESSION['reset_verified_at'])) {
    $time_diff = time() - $_SESSION['reset_verified_at'];
    if ($time_diff > 900) { // 15 minutes
        // Session expired, clear and redirect
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['reset_identifier']);
        unset($_SESSION['reset_otp_verified']);
        unset($_SESSION['reset_otp_id']);
        unset($_SESSION['masked_phone']);
        unset($_SESSION['reset_verified_at']);
        header('Location: login.php?reset_expired=1');
        exit();
    }
}

$error = '';
$success = '';
$user_id = $_SESSION['reset_user_id'] ?? null;
$identifier = $_SESSION['reset_identifier'] ?? '';

if (!$user_id) {
    header('Location: forgot-password.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate password
    if (empty($password)) {
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
            // Hash the new password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // Update user's password in database
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$password_hash, $user_id]);

            // Mark OTP as used
            if (isset($_SESSION['reset_otp_id'])) {
                $stmt = $pdo->prepare("UPDATE password_reset_otps SET is_used = 1 WHERE id = ?");
                $stmt->execute([$_SESSION['reset_otp_id']]);
            }

            // Clear session variables
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_identifier']);
            unset($_SESSION['reset_otp_verified']);
            unset($_SESSION['reset_otp_id']);
            unset($_SESSION['masked_phone']);
            unset($_SESSION['reset_verified_at']);
            unset($_SESSION['reset_otp_display']);

            // Redirect to login with success message
            header('Location: login.php?password_reset=success');
            exit();
        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            $error = "An error occurred while resetting your password. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - PulseWave</title>
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
            padding: 20px;
        }

        .reset-container {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            padding: 45px 35px;
        }

        .reset-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .reset-header .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .reset-header h2 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #191414;
            margin-bottom: 8px;
        }

        .reset-header p {
            color: #535353;
            font-size: 0.95rem;
            line-height: 1.5;
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

        .reset-btn {
            width: 100%;
            padding: 14px;
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

        .reset-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 138, 45, 0.3);
        }

        .reset-btn:active {
            transform: translateY(0);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        .back-link a {
            color: #ff8a2d;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
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
    </style>
</head>
<body>

<div class="reset-container">
    <div class="reset-header">
        <div class="icon">
            <i class="bi bi-key-fill" style="color: #ff8a2d;"></i>
        </div>
        <h2>Create New Password</h2>
        <p>Set a strong password for your account</p>
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

    <form method="POST" id="resetForm">
        <div class="form-group">
            <label for="password" class="form-label">New Password</label>
            <input type="password" id="password" name="password" class="form-input" placeholder="Create a strong password" required>
            <div id="passwordStrength" class="password-strength"></div>
        </div>

        <div class="form-group">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm your password" required>
        </div>

        <button type="submit" class="reset-btn">
            <i class="bi bi-check-lg me-2"></i>
            Reset Password
        </button>
    </form>

    <div class="back-link">
        <i class="bi bi-arrow-left me-1"></i>
        <a href="login.php">Back to Login</a>
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
    document.getElementById('resetForm').addEventListener('submit', function(e) {
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
