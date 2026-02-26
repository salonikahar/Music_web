<?php
session_start();
require '../config/db.php';
require '../config/razorpay.php';
require_once '../razorpay-php-master/Razorpay.php';

use Razorpay\Api\Api;

$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

$plan = isset($_GET['plan']) ? $_GET['plan'] : 'individual';

// Set amount based on plan
switch ($plan) {
    case 'family':
        $amount = 14900; // ₹149
        break;
    case 'student':
        $amount = 5900; // ₹59
        break;
    case 'individual':
    default:
        $amount = PREMIUM_AMOUNT; // ₹99
        break;
}

$order = $api->order->create([
    'receipt' => 'premium_' . $plan . '_' . time(),
    'amount'  => $amount,
    'currency'=> CURRENCY
]);

echo json_encode([
    'order_id' => $order['id'],
    'amount'   => $order['amount']
]);
