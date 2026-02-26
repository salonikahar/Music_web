<?php
session_start();
require_once 'config/db.php';
require_once 'config/twilio.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';
$info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');

    if (empty($identifier)) {
        $error = "Please enter your username or email address.";
    } else {
        try {
            // Check if input is email or username
            $is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);

            if ($is_email) {
                $stmt = $pdo->prepare("SELECT id, username, email, mobile_number FROM users WHERE email = ?");
            } else {
                $stmt = $pdo->prepare("SELECT id, username, email, mobile_number FROM users WHERE username = ?");
            }

            $stmt->execute([$identifier]);
            $user = $stmt->fetch();

            if (!$user) {
                if ($is_email) {
                    $error = "No account found with this email address.";
                } else {
                    $error = "No account found with this username.";
                }
            } elseif (empty($user['mobile_number'])) {
                $error = "Your account doesn't have a mobile number registered. Please contact support.";
            } else {
                // Check rate limiting - max 3 attempts per hour
                $stmt = $pdo->prepare("SELECT COUNT(*) as attempt_count FROM password_reset_attempts WHERE user_id = ? AND attempt_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                $stmt->execute([$user['id']]);
                $result = $stmt->fetch();

                if ($result['attempt_count'] >= 3) {
                    $error = "Too many OTP requests. Please try again after 1 hour.";
                } else {
                    // Generate OTP
                    $otp = generateOTP();
                    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

                    // Store OTP in database
                    $stmt = $pdo->prepare("INSERT INTO password_reset_otps (user_id, otp_code, mobile_number, expires_at, is_used) VALUES (?, ?, ?, ?, 0)");
                    $stmt->execute([$user['id'], $otp, $user['mobile_number'], $expires_at]);

                    // Record the attempt for rate limiting
                    $stmt = $pdo->prepare("INSERT INTO password_reset_attempts (user_id, attempt_at) VALUES (?, NOW())");
                    $stmt->execute([$user['id']]);

                    // Send SMS via Twilio
                    $message = "Your PulseWave password reset OTP is: " . $otp . ". This code expires in 5 minutes. Do not share this code with anyone.";
                    $sms_result = sendSMS($user['mobile_number'], $message);

                    if ($sms_result['success']) {
                        // Store user info in session for verification page
                        $_SESSION['reset_user_id'] = $user['id'];
                        $_SESSION['reset_identifier'] = $user['username'];
                        $_SESSION['masked_phone'] = maskPhoneNumber($user['mobile_number']);

                        // In development mode, store OTP for display
                        if (DEVELOPMENT_MODE && isset($sms_result['otp'])) {
                            $_SESSION['reset_otp_display'] = $sms_result['otp'];
                        }

                        // Redirect to OTP verification page
                        header('Location: verify-otp.php');
                        exit();
                    } else {
                        error_log("SMS sending failed: " . $sms_result['message']);
                        $error = "Failed to send OTP. Please try again later.";
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Forgot password error: " . $e->getMessage());
            $error = "An error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PulseWave</title>
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

        .submit-btn {
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

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 138, 45, 0.3);
        }

        .submit-btn:active {
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

        .alert-info {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }
    </style>
</head>
<body>

<div class="reset-container">
    <div class="reset-header">
        <div class="icon">
            <i class="bi bi-lock-fill" style="color: #ff8a2d;"></i>
        </div>
        <h2>Reset Password</h2>
        <p>Enter your username or email address to receive an OTP code.</p>
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

    <?php if ($info): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-2"></i>
            <?= htmlspecialchars($info) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="identifier" class="form-label">Username or Email</label>
            <input type="text" id="identifier" name="identifier" class="form-input" placeholder="Enter your username or email" required autofocus value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>">
        </div>

        <button type="submit" class="submit-btn">
            <i class="bi bi-send me-2"></i>
            Send OTP to Mobile
        </button>
    </form>

    <div class="back-link">
        <i class="bi bi-arrow-left me-1"></i>
        <a href="login.php">Back to Login</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
