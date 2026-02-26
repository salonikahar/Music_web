<?php
session_start();
require '../config/db.php';
require '../config/razorpay.php';
require_once '../razorpay-php-master/Razorpay.php';
require_once '../includes/premium_check.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$data = json_decode(file_get_contents("php://input"), true);

// Real Razorpay payment verification only
$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

try {
    // Verify payment signature
    $api->utility->verifyPaymentSignature([
        'razorpay_order_id'   => $data['razorpay_order_id'],
        'razorpay_payment_id' => $data['razorpay_payment_id'],
        'razorpay_signature'  => $data['razorpay_signature'],
    ]);

    // Verify order exists and belongs to user
    $order = $api->order->fetch($data['razorpay_order_id']);
    if ($order['status'] !== 'paid') {
        throw new Exception('Order not paid');
    }
} catch (SignatureVerificationError $e) {
    echo json_encode(['success' => false, 'error' => 'Signature verification failed']);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

// Verify user session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

try {
    // Get plan type and set appropriate duration (all plans get 30 days for now)
    $planType = isset($data['plan_type']) ? $data['plan_type'] : 'individual';

    // Log the payment attempt
    error_log("Premium activation attempt for user ID: " . $_SESSION['user_id'] . ", plan: " . $planType);

    // All plans are 30 days
    $activationResult = activatePremium($_SESSION['user_id'], 30);

    if ($activationResult) {
        error_log("Premium activated successfully for user ID: " . $_SESSION['user_id']);
        echo json_encode(['success' => true, 'message' => 'Premium activated successfully']);
    } else {
        error_log("Premium activation failed for user ID: " . $_SESSION['user_id']);
        echo json_encode(['success' => false, 'error' => 'Failed to activate premium']);
    }

} catch (Exception $e) {
    error_log("Premium activation error for user ID: " . $_SESSION['user_id'] . ": " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
