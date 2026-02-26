<?php
// Razorpay Configuration
// For testing, use your Razorpay test credentials from https://dashboard.razorpay.com/
// Test Key ID and Secret can be found in your Razorpay dashboard under "Settings > API Keys"

// Razorpay test credentials
define('RAZORPAY_KEY_ID', 'rzp_test_S7exiLPjO4j3XV'); // Test Key ID
define('RAZORPAY_KEY_SECRET', 'MvvwdbzQTnHfio3T18LiH5Oy'); // Test Key Secret

// Test mode - set to false when using real keys
define('TEST_MODE', true);

// Premium subscription amount (in paise)
define('PREMIUM_AMOUNT', 9900); // ₹99 = 9900 paise

// Currency
define('CURRENCY', 'INR');
?>
