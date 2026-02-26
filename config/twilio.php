<?php
/**
 * Twilio Configuration and SMS Helper
 * This file handles SMS sending via Twilio API
 */

// Development Mode - Set to true to skip actual SMS and use test OTP
define('DEVELOPMENT_MODE', true);

// Twilio Credentials - Replace with your actual credentials from twilio.com
define('TWILIO_ACCOUNT_SID', getenv('TWILIO_ACCOUNT_SID') ?: 'ACe68919a1d7d3f5d5b5e5d5d5d5d5d5d'); // Replace with your SID
define('TWILIO_AUTH_TOKEN', getenv('TWILIO_AUTH_TOKEN') ?: 'your_auth_token_here'); // Replace with your Auth Token
define('TWILIO_PHONE_NUMBER', getenv('TWILIO_PHONE_NUMBER') ?: '+1234567890'); // Replace with your Twilio phone number

/**
 * Send SMS via Twilio API
 *
 * @param string $phone_number The recipient's phone number (E.164 format)
 * @param string $message The SMS message text
 * @return array Result array with success flag and message
 */
function sendSMS($phone_number, $message) {
    // In development mode, just log and return success
    if (DEVELOPMENT_MODE) {
        // Extract OTP from message
        preg_match('/OTP is: (\d{6})/', $message, $matches);
        $otp = $matches[1] ?? 'N/A';

        // Log to file for testing
        $log_file = __DIR__ . '/../logs/otp_test.log';
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        $log_entry = date('Y-m-d H:i:s') . " | Phone: " . substr($phone_number, -4) . " | OTP: " . $otp . "\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);

        return [
            'success' => true,
            'message' => 'SMS sent successfully (Development Mode)',
            'sid' => 'dev_' . time(),
            'otp' => $otp  // Include OTP for testing
        ];
    }

    // Validate phone number format
    if (empty($phone_number) || !preg_match('/^\+?\d{10,15}$/', str_replace(['-', ' ', '(', ')'], '', $phone_number))) {
        return [
            'success' => false,
            'message' => 'Invalid phone number format'
        ];
    }

    // Validate message
    if (empty($message) || strlen($message) > 160) {
        return [
            'success' => false,
            'message' => 'Message must be between 1 and 160 characters'
        ];
    }

    // Twilio API endpoint
    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_ACCOUNT_SID . '/Messages.json';

    // Prepare phone number in E.164 format
    $phone_number = preg_replace('/[^0-9+]/', '', $phone_number);
    if (substr($phone_number, 0, 1) !== '+') {
        // Assume it's US number if no country code
        $phone_number = '+1' . substr($phone_number, -10);
    }

    // Prepare POST data
    $postData = http_build_query([
        'From' => TWILIO_PHONE_NUMBER,
        'To' => $phone_number,
        'Body' => $message
    ]);

    // Make cURL request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Check for cURL errors
    if ($curl_error) {
        error_log("Twilio SMS Error: $curl_error");
        return [
            'success' => false,
            'message' => 'Failed to send SMS. Please try again later.'
        ];
    }

    // Parse response
    $result = json_decode($response, true);

    if ($http_code == 201 && isset($result['sid'])) {
        return [
            'success' => true,
            'message' => 'SMS sent successfully',
            'sid' => $result['sid']
        ];
    } else {
        error_log("Twilio API Error: " . $response);
        return [
            'success' => false,
            'message' => $result['message'] ?? 'Failed to send SMS'
        ];
    }
}

/**
 * Generate a random 6-digit OTP
 *
 * @return string 6-digit OTP
 */
function generateOTP() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Format phone number for display (mask middle digits)
 *
 * @param string $phone_number The phone number to mask
 * @return string Masked phone number
 */
function maskPhoneNumber($phone_number) {
    // Remove non-numeric characters
    $clean = preg_replace('/\D/', '', $phone_number);

    // Show only last 4 digits
    if (strlen($clean) >= 4) {
        return 'XXXX' . substr($clean, -4);
    }
    return '****' . substr($clean, -2);
}

