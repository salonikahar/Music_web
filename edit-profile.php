<?php
session_start();
require_once 'config/db.php';
require_once 'config/twilio.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$BASE_URL = '/Spotify-clone-new/Spotify-clone';
$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Get current user data
$stmt = $pdo->prepare("SELECT username, email, mobile_number FROM users WHERE id = ?");
$stmt->execute([$userId]);
$profileUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileUser) {
    die('User not found');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_email') {
        $new_email = strtolower(trim($_POST['email'] ?? ''));

        if (empty($new_email)) {
            $error = "Email is required.";
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif ($new_email === ($profileUser['email'] ?? '')) {
            $error = "This is already your current email address.";
        } else {
            try {
                // Check if email is already taken by another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$new_email, $userId]);
                if ($stmt->fetch()) {
                    $error = "This email address is already registered to another account.";
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                    if ($stmt->execute([$new_email, $userId])) {
                        $profileUser['email'] = $new_email;
                        $success = "Email updated successfully!";
                    }
                }
            } catch (PDOException $e) {
                error_log("Update email error: " . $e->getMessage());
                $error = "An error occurred while updating email. Please try again.";
            }
        }
    }

    elseif ($action === 'update_mobile') {
        $new_mobile = trim($_POST['mobile_number'] ?? '');

        if (empty($new_mobile)) {
            $error = "Mobile number is required.";
        } elseif (!preg_match('/^[\d\s\-\+]{10,20}$/', $new_mobile)) {
            $error = "Please enter a valid mobile number (10-20 digits, spaces and hyphens allowed).";
        } elseif ($new_mobile === ($profileUser['mobile_number'] ?? '')) {
            $error = "This is already your current mobile number.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET mobile_number = ? WHERE id = ?");
                if ($stmt->execute([$new_mobile, $userId])) {
                    $profileUser['mobile_number'] = $new_mobile;
                    $success = "Mobile number updated successfully!";
                }
            } catch (PDOException $e) {
                error_log("Update mobile error: " . $e->getMessage());
                $error = "An error occurred while updating mobile number. Please try again.";
            }
        }
    }

    elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password)) {
            $error = "Please enter your current password.";
        } elseif (empty($new_password)) {
            $error = "Please enter a new password.";
        } elseif (empty($confirm_password)) {
            $error = "Please confirm your new password.";
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user_data = $stmt->fetch();

            if (!$user_data || !password_verify($current_password, $user_data['password_hash'])) {
                $error = "Current password is incorrect.";
            } elseif (strlen($new_password) < 8) {
                $error = "New password must be at least 8 characters long.";
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $new_password)) {
                $error = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
            } elseif ($new_password !== $confirm_password) {
                $error = "Passwords do not match.";
            } else {
                try {
                    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    if ($stmt->execute([$password_hash, $userId])) {
                        $success = "Password changed successfully!";
                    }
                } catch (PDOException $e) {
                    error_log("Change password error: " . $e->getMessage());
                    $error = "An error occurred while changing password. Please try again.";
                }
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
    <title>Edit Profile - PulseWave</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/assets/css/player.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/assets/css/sidebar.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/assets/css/modern.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/assets/css/notification.css">
    <script src="<?= $BASE_URL ?>/assets/js/notification.js"></script>
    <style>
        :root {
            --green: #ff8a2d;
            --green-hover: #ffb067;
            --bg: #121212;
            --bg-secondary: #1a1a1a;
            --panel: #181818;
            --panel-hover: #282828;
            --text: #fff;
            --text-secondary: #b3b3b3;
            --text-muted: #6a6a6a;
            --border: #282828;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background: var(--bg);
            color: var(--text);
        }

        .main-content {
            margin-left: 280px;
            padding-bottom: 200px;
            min-height: 100vh;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 120px 30px 200px 30px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 100px 20px 180px 20px;
            }
        }

        .edit-profile-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .edit-profile-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 40px;
        }

        .edit-profile-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
        }

        .edit-profile-header a {
            margin-left: auto;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
        }

        .edit-profile-header a:hover {
            color: var(--green);
        }

        .edit-section {
            background: linear-gradient(135deg, var(--panel) 0%, transparent 100%);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 24px;
        }

        .edit-section h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .edit-section h3 i {
            color: var(--green);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 14px;
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(255, 138, 45, 0.1);
        }

        .form-group input::placeholder {
            color: var(--text-muted);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-save {
            flex: 1;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--green), var(--green-hover));
            color: #000;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 138, 45, 0.3);
        }

        .btn-cancel {
            flex: 1;
            padding: 12px 24px;
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-cancel:hover {
            border-color: var(--text-secondary);
            background: var(--border);
        }

        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.15);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            color: #51cf66;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .help-text {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 6px;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .password-strength.weak {
            color: #ff6b6b;
        }

        .password-strength.medium {
            color: #ffd93d;
        }

        .password-strength.strong {
            color: #51cf66;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 100px 16px 180px 16px;
            }

            .edit-profile-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .edit-profile-header a {
                margin-left: 0;
            }

            .edit-section {
                padding: 20px;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

    <?php include 'partials/header.php'; ?>
    <?php include 'partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="edit-profile-container">

            <div class="edit-profile-header">
                <h1><i class="bi bi-pencil-fill" style="color: var(--green);"></i>Edit Profile</h1>
                <a href="profile.php" title="Back to Profile">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
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
                    <i class="bi bi-check-circle-fill"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showNotification('success', '<?= addslashes(htmlspecialchars($success)) ?>');
                    });
                </script>
            <?php endif; ?>

            <!-- Email Section -->
            <form method="POST" class="edit-section">
                <h3><i class="bi bi-envelope"></i>Email Address</h3>
                <input type="hidden" name="action" value="update_email">
                <div class="form-group">
                    <label for="email">Current Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($profileUser['email'] ?? '') ?>" required>
                    <div class="help-text">You'll use this email to log in to your account</div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="bi bi-check-lg me-2"></i>Update Email
                    </button>
                </div>
            </form>

            <!-- Mobile Number Section -->
            <form method="POST" class="edit-section">
                <h3><i class="bi bi-telephone"></i>Mobile Number</h3>
                <input type="hidden" name="action" value="update_mobile">
                <div class="form-group">
                    <label for="mobile_number">Mobile Number</label>
                    <input type="tel" id="mobile_number" name="mobile_number" value="<?= htmlspecialchars($profileUser['mobile_number'] ?? '') ?>" placeholder="+1234567890" required>
                    <div class="help-text">Used for password recovery via OTP</div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="bi bi-check-lg me-2"></i>Update Mobile
                    </button>
                </div>
            </form>

            <!-- Password Change Section -->
            <form method="POST" class="edit-section" id="passwordForm">
                <h3><i class="bi bi-key"></i>Change Password</h3>
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" placeholder="Enter your current password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Create a strong password" required>
                    <div id="passwordStrength" class="password-strength"></div>
                    <div class="help-text">Must contain uppercase, lowercase, number, and special character</div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="bi bi-check-lg me-2"></i>Change Password
                    </button>
                </div>
            </form>

        </div>
    </div>

    <?php include 'partials/player.php'; ?>
    <script src="<?= $BASE_URL ?>/assets/js/player.js"></script>
    <script src="<?= $BASE_URL ?>/assets/js/sidebar.js"></script>

    <script>
        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
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
                case 2:
                    strengthIndicator.textContent = 'Weak password';
                    strengthIndicator.className = 'password-strength weak';
                    break;
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
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                showNotification('error', 'Passwords do not match!');
                return false;
            }

            if (newPassword.length < 8) {
                e.preventDefault();
                showNotification('error', 'Password must be at least 8 characters long!');
                return false;
            }
        });
    </script>

</body>
</html>
