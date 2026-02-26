<?php
session_start();
require_once 'config/db.php';
require_once 'config/razorpay.php';
require_once 'includes/premium_check.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if already premium - redirect to download
if (isPremiumUser($_SESSION['user_id'])) {
    $songId = isset($_GET['song_id']) ? (int)$_GET['song_id'] : 0;
    if ($songId > 0) {
        header('Location: api/download.php?song_id=' . $songId);
    } else {
        header('Location: index.php');
    }
    exit;
}

$userId = $_SESSION['user_id'];
$songId = isset($_GET['song_id']) ? (int)$_GET['song_id'] : 0;

if ($songId <= 0) {
    die('Invalid song ID');
}

// Get song details
$stmt = $pdo->prepare("SELECT title FROM songs WHERE id = ?");
$stmt->execute([$songId]);
$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
    die('Song not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Premium Upgrade - PulseWave</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/notification.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="assets/js/notification.js"></script>
</head>
<body>
    <?php include 'partials/header.php'; ?>
    <?php include 'partials/sidebar.php'; ?>

    <div class="main-content" style="margin-left: 240px; padding: 20px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Upgrade to Premium</h3>
                        </div>
                        <div class="card-body">
                            <p>To download "<strong><?= htmlspecialchars($song['title']) ?></strong>", you need a Premium subscription.</p>
                            <p class="text-muted">Get unlimited downloads and ad-free listening for just ₹99/month.</p>

                            <div class="text-center my-4">
                                <h4>₹99/month</h4>
                                <p class="text-muted">Cancel anytime</p>
                            </div>

                            <button id="rzp-button1" class="btn btn-success btn-lg w-100">
                                <i class="bi bi-credit-card"></i> Pay with Razorpay
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('rzp-button1').onclick = function(e) {
            e.preventDefault();

            // Create order first
            fetch('payment/create_order.php')
                .then(response => response.json())
                .then(orderData => {
                    var options = {
                        "key": "<?= RAZORPAY_KEY_ID ?>",
                        "amount": orderData.amount,
                        "currency": "INR",
                        "name": "PulseWave",
                        "description": "Premium Subscription",
                        "image": "assets/default-playlist.png",
                        "order_id": orderData.order_id,
                        "handler": function (response) {
                            // Payment success callback
                            fetch('payment/verify_payment.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_signature: response.razorpay_signature
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    showNotification('success', 'Payment successful! You are now a Premium member.');
                                    window.location.href = 'api/download.php?song_id=<?= $songId ?>';
                                } else {
                                    showNotification('error', 'Payment verification failed: ' + (data.error || 'Please contact support.'));
                                }
                            });
                        },
                        "prefill": {
                            "name": "User Name",
                            "email": "user@example.com"
                        },
                        "theme": {
                            "color": "#ff8a2d"
                        }
                    };

                    var rzp1 = new Razorpay(options);
                    rzp1.open();
                })
                .catch(error => {
                    console.error('Error creating order:', error);
                    showNotification('error', 'Error creating payment order. Please try again.');
                });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sidebar.js"></script>
</body>
</html>



