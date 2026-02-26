<?php
session_start();
require_once 'config/db.php';
require_once 'includes/premium_check.php';

// Prevent browser caching to ensure fresh premium status
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$BASE_URL = '.';
$userLoggedIn = isset($_SESSION['user_id']);
$userPremium = false;

if ($userLoggedIn) {
    // Check and update premium status
    $userPremium = isPremiumUser($_SESSION['user_id']);
    $_SESSION['is_premium'] = $userPremium ? 1 : 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Premium Plans - PulseWave</title>

    <link rel="icon" href="assets/default-playlist.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/assets/css/player.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/assets/css/sidebar.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <script>
        // Debug: Check if script is loading
        console.log('Payment script loaded successfully');

        const plans = {
            individual: { amount: 9900, name: 'Individual Plan' },
            family: { amount: 14900, name: 'Family Plan' },
            student: { amount: 5900, name: 'Student Plan' }
        };

        // Razorpay key from PHP config
        const razorpayKey = 'rzp_test_S7exiLPjO4j3XV'; // Hardcoded for now to avoid PHP issues

        // Debug: Check if function is defined
        console.log('initiatePayment function defined:', typeof initiatePayment);

        function initiatePayment(planType) {
            console.log('Initiating payment for plan:', planType);

            const plan = plans[planType];

            // Ask user to choose payment method
            const paymentChoice = prompt('Choose payment method:\n1. UPI (Recommended)\n2. Cards\n\nEnter 1 or 2:');
            if (paymentChoice !== '1' && paymentChoice !== '2') {
                showNotification('error', 'Please choose a valid payment method (1 or 2).');
                return;
            }

            // Create order first
            console.log('Creating order...');
            fetch('payment/create_order.php?plan=' + planType)
                .then(response => {
                    console.log('Order creation response:', response);
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(orderData => {
                    console.log('Order data received:', orderData);

                    const options = {
                        "key": razorpayKey,
                        "amount": orderData.amount,
                        "currency": "INR",
                        "name": "PulseWave",
                        "description": plan.name,
                        "image": "assets/default-playlist.png",
                        "order_id": orderData.order_id,
                        "handler": function (response) {
                            console.log('Payment response:', response);
                            // Payment success callback
                            verifyPayment(response, planType);
                        },
                        "prefill": {
                            "name": "User Name",
                            "email": "user@example.com"
                        },
                        "theme": {
                            "color": "#ff8a2d"
                        }
                    };

                    // Configure payment methods based on choice
                    if (paymentChoice === '1') {
                        // UPI Only - hide other payment methods
                        options.config = {
                            display: {
                                language: 'en',
                                hide: [
                                    {
                                        method: 'card'
                                    },
                                    {
                                        method: 'netbanking'
                                    },
                                    {
                                        method: 'wallet'
                                    }
                                ]
                            }
                        };
                        console.log('UPI-only payment configured');
                    } else if (paymentChoice === '2') {
                        // Cards Only - hide UPI and other methods
                        options.config = {
                            display: {
                                language: 'en',
                                hide: [
                                    {
                                        method: 'upi'
                                    },
                                    {
                                        method: 'netbanking'
                                    },
                                    {
                                        method: 'wallet'
                                    }
                                ]
                            }
                        };
                        console.log('Card-only payment configured');
                    }

                    console.log('Razorpay key:', razorpayKey);
                    console.log('Opening Razorpay with options:', options);
                    const rzp = new Razorpay(options);
                    rzp.open();
                })
                .catch(error => {
                    console.error('Error creating order:', error);
                    showNotification('error', 'Error creating payment order. Please try again. Error: ' + error.message);
                });
        }

        function verifyPayment(response, planType) {
            console.log('Verifying payment:', response);
            showNotification('info', 'Verifying payment...');

            fetch('payment/verify_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_signature: response.razorpay_signature,
                    plan_type: planType
                })
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Verification response:', data);
                    if (data.success) {
                        showNotification('success', 'Payment successful! Welcome to Premium!');
                        location.reload();
                    } else {
                        showNotification('error', 'Payment verification failed: ' + (data.error || 'Please contact support.'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'An error occurred. Please try again.');
                });
        }

        // Debug: Function available globally
        window.initiatePayment = initiatePayment;
        window.verifyPayment = verifyPayment;
    </script>
    <style>
        .premium-hero {
            background: linear-gradient(135deg, #ff8a2d 0%, #ffb067 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }

        .plan-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .plan-card:hover {
            transform: translateY(-5px);
        }

        .plan-card.premium {
            border: 2px solid #ff8a2d;
            position: relative;
        }

        .plan-card.premium::before {
            content: "Most Popular";
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #ff8a2d;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .feature-list li:last-child {
            border-bottom: none;
        }

        .feature-list li i {
            color: #ff8a2d;
            margin-right: 10px;
        }
    </style>
</head>

<body>

<script>
    const BASE_URL = "<?php echo $BASE_URL; ?>";
</script>

    <?php include 'partials/header.php'; ?>
    <?php include 'partials/sidebar.php'; ?>

    <div class="main-content" id="app-content" style="margin-left: 240px;">

        <!-- Hero Section -->
        <div class="premium-hero">
            <div class="container">
                <h1 class="display-4 fw-bold">Get Premium</h1>
                <p class="lead">Unlock unlimited downloads, ad-free listening, and exclusive features</p>
            </div>
        </div>

        <!-- Plans Section -->
        <div class="container py-5">
            <div class="row justify-content-center">
                <!-- Individual Plan -->
                <div class="col-md-4 mb-4">
                    <div class="card plan-card h-100">
                        <div class="card-body text-center">
                            <h3 class="card-title">Individual</h3>
                            <div class="price mb-3">
                                <span class="display-4 fw-bold">₹99</span>
                                <span class="text-muted">/month</span>
                            </div>
                            <ul class="feature-list text-start">
                                <li><i class="bi bi-check-circle-fill"></i> Unlimited downloads</li>
                                <li><i class="bi bi-check-circle-fill"></i> Ad-free listening</li>
                                <li><i class="bi bi-check-circle-fill"></i> High-quality audio</li>
                                <li><i class="bi bi-check-circle-fill"></i> Offline playback</li>
                                <li><i class="bi bi-check-circle-fill"></i> Cancel anytime</li>
                            </ul>
                            <?php if ($userLoggedIn && $userPremium): ?>
                                <button class="btn btn-success w-100" disabled>Already Premium</button>
                            <?php elseif ($userLoggedIn): ?>
                                <button class="btn btn-success w-100" onclick="initiatePayment('individual')">Get
                                    Premium</button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-success w-100">Sign Up</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Family Plan -->
                <div class="col-md-4 mb-4">
                    <div class="card plan-card premium h-100">
                        <div class="card-body text-center">
                            <h3 class="card-title">Family</h3>
                            <div class="price mb-3">
                                <span class="display-4 fw-bold">₹149</span>
                                <span class="text-muted">/month</span>
                            </div>
                            <p class="text-muted">Up to 6 family members</p>
                            <ul class="feature-list text-start">
                                <li><i class="bi bi-check-circle-fill"></i> Everything in Individual</li>
                                <li><i class="bi bi-check-circle-fill"></i> Up to 6 accounts</li>
                                <li><i class="bi bi-check-circle-fill"></i> Family sharing</li>
                                <li><i class="bi bi-check-circle-fill"></i> Parental controls</li>
                                <li><i class="bi bi-check-circle-fill"></i> Block explicit content</li>
                            </ul>
                            <?php if ($userLoggedIn && $userPremium): ?>
                                <button class="btn btn-success w-100" disabled>Already Premium</button>
                            <?php elseif ($userLoggedIn): ?>
                                <button class="btn btn-success w-100" onclick="initiatePayment('family')">Get
                                    Family</button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-success w-100">Sign Up</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Student Plan -->
                <div class="col-md-4 mb-4">
                    <div class="card plan-card h-100">
                        <div class="card-body text-center">
                            <h3 class="card-title">Student</h3>
                            <div class="price mb-3">
                                <span class="display-4 fw-bold">₹59</span>
                                <span class="text-muted">/month</span>
                            </div>
                            <p class="text-muted">For eligible students</p>
                            <ul class="feature-list text-start">
                                <li><i class="bi bi-check-circle-fill"></i> Everything in Individual</li>
                                <li><i class="bi bi-check-circle-fill"></i> Student discount</li>
                                <li><i class="bi bi-check-circle-fill"></i> Hulu (ad-supported) included</li>
                                <li><i class="bi bi-check-circle-fill"></i> SHOWTIME included</li>
                                <li><i class="bi bi-check-circle-fill"></i> Special student perks</li>
                            </ul>
                            <?php if ($userLoggedIn && $userPremium): ?>
                                <button class="btn btn-success w-100" disabled>Already Premium</button>
                            <?php elseif ($userLoggedIn): ?>
                                <button class="btn btn-success w-100" onclick="initiatePayment('student')">Get
                                    Student</button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-success w-100">Sign Up</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="container py-5">
            <h2 class="text-center mb-4">Frequently Asked Questions</h2>
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            How do I cancel my Premium subscription?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            You can cancel your subscription anytime from your account settings. You'll continue to have
                            access to Premium features until the end of your billing period.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#faq2">
                            Can I download songs for offline listening?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes! With Premium, you can download unlimited songs and listen offline on your mobile and
                            desktop apps.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#faq3">
                            Is there a free trial?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            We offer a 30-day free trial for new Premium subscribers. No commitment required.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/player.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $BASE_URL; ?>/assets/js/player.js"></script>
    <script src="<?php echo $BASE_URL; ?>/assets/js/sidebar.js?v=2"></script>
    <script src="<?php echo $BASE_URL; ?>/assets/js/spa.js"></script>
</body>

</html>



