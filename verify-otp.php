<?php
session_start();
require_once 'config/db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

// Check if user came from forgot password page
if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_identifier'])) {
    header('Location: forgot-password.php');
    exit();
}

$error = '';
$success = '';
$user_id = $_SESSION['reset_user_id'];
$identifier = $_SESSION['reset_identifier'];
$masked_phone = $_SESSION['masked_phone'] ?? 'your registered mobile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');

    if (empty($otp)) {
        $error = "Please enter the OTP code.";
    } elseif (!preg_match('/^\d{6}$/', $otp)) {
        $error = "OTP must be exactly 6 digits.";
    } else {
        try {
            // Check if OTP is valid for this user
            $stmt = $pdo->prepare("SELECT id, expires_at FROM password_reset_otps WHERE user_id = ? AND otp_code = ? AND is_used = 0 ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$user_id, $otp]);
            $otp_record = $stmt->fetch();

            if (!$otp_record) {
                $error = "Invalid OTP code. Please try again.";
            } else {
                // Check if OTP has expired
                $now = new DateTime();
                $expiry = new DateTime($otp_record['expires_at']);

                if ($now > $expiry) {
                    $error = "OTP has expired. Please request a new one.";
                } else {
                    // Mark OTP as verified
                    $stmt = $pdo->prepare("UPDATE password_reset_otps SET verified_at = NOW() WHERE id = ?");
                    $stmt->execute([$otp_record['id']]);

                    // Create reset token in session
                    $_SESSION['reset_otp_verified'] = true;
                    $_SESSION['reset_otp_id'] = $otp_record['id'];
                    $_SESSION['reset_verified_at'] = time();

                    // Redirect to password reset page
                    header('Location: reset-password.php');
                    exit();
                }
            }
        } catch (PDOException $e) {
            error_log("OTP verification error: " . $e->getMessage());
            $error = "An error occurred. Please try again later.";
        }
    }
}

// Get remaining time for OTP (approximate)
$stmt = $pdo->prepare("SELECT expires_at FROM password_reset_otps WHERE user_id = ? AND is_used = 0 ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$latest_otp = $stmt->fetch();
$time_remaining = '';

if ($latest_otp) {
    $expires = strtotime($latest_otp['expires_at']);
    $now = time();
    $remaining = $expires - $now;

    if ($remaining > 0) {
        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;
        $time_remaining = sprintf("%d:%02d", $minutes, $seconds);
    } else {
        $time_remaining = "Expired";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - PulseWave</title>
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

        .verify-container {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            padding: 45px 35px;
        }

        .verify-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .verify-header .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .verify-header h2 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #191414;
            margin-bottom: 8px;
        }

        .verify-header p {
            color: #535353;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .otp-info {
            background: #f0f8ff;
            border: 1px solid #b6deff;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            color: #1976d2;
        }

        .otp-info strong {
            display: block;
            margin-bottom: 4px;
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
            font-size: 1.2rem;
            letter-spacing: 0.15em;
            transition: all 0.3s ease;
            background: #fafafa;
            text-align: center;
        }

        .form-input:focus {
            outline: none;
            border-color: #ff8a2d;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 138, 45, 0.12);
        }

        .form-input::placeholder {
            color: #b3b3b3;
            letter-spacing: normal;
        }

        .verify-btn {
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

        .verify-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 138, 45, 0.3);
        }

        .verify-btn:active {
            transform: translateY(0);
        }

        .resend-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        .resend-link a {
            color: #ff8a2d;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .resend-link a:hover {
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

        .timer {
            display: inline-block;
            background: #ffe8cc;
            color: #cc6600;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.85rem;
            margin-left: 8px;
        }
    </style>
</head>
<body>

<div class="verify-container">
    <div class="verify-header">
        <div class="icon">
            <i class="bi bi-shield-check" style="color: #ff8a2d;"></i>
        </div>
        <h2>Verify OTP</h2>
        <p>Enter the 6-digit code sent to your mobile</p>
    </div>

    <div class="otp-info">
        <strong>OTP Sent To:</strong>
        <?= htmlspecialchars($masked_phone) ?>
        <?php if ($time_remaining): ?>
            <div style="margin-top: 8px; font-size: 0.85rem;">
                Time remaining: <span class="timer"><?= htmlspecialchars($time_remaining) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['reset_otp_display'])): ?>
        <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 15px; margin-bottom: 20px; text-align: center;">
            <p style="margin: 0 0 10px 0; color: #856404; font-weight: 600; font-size: 0.9rem;">
                <i class="bi bi-info-circle-fill me-2"></i>Development Mode - Test OTP:
            </p>
            <p style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #ff8a2d; font-family: monospace; letter-spacing: 5px;">
                <?= htmlspecialchars($_SESSION['reset_otp_display']) ?>
            </p>
        </div>
    <?php endif; ?>

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

    <form method="POST">
        <div class="form-group">
            <label for="otp" class="form-label">Enter OTP Code</label>
            <input type="text" id="otp" name="otp" class="form-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus value="<?= htmlspecialchars($_POST['otp'] ?? '') ?>">
        </div>

        <button type="submit" class="verify-btn">
            <i class="bi bi-check-lg me-2"></i>
            Verify OTP
        </button>
    </form>

    <div class="resend-link">
        Didn't receive the code?<br>
        <a href="forgot-password.php">
            <i class="bi bi-arrow-repeat me-1"></i>
            Request New OTP
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Auto-format OTP input to only accept digits
document.getElementById('otp').addEventListener('keypress', function(e) {
    if (!/[0-9]/.test(e.key)) {
        e.preventDefault();
    }
});
</script>

</body>
</html>
