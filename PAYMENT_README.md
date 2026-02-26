# Payment Integration Documentation

This document explains the payment system implementation in the Spotify Clone project using Razorpay.

## Overview

The application includes a premium subscription system with Razorpay payment integration. Users can purchase premium subscriptions to access additional features like unlimited downloads and ad-free listening.

## Features

- 💳 Secure payment processing with Razorpay
- 📱 Multiple payment methods (UPI, Cards, Net Banking, Wallets)
- 🔒 PCI DSS compliant
- 📊 Payment tracking and verification
- 🎫 Subscription management
- 📤 Download functionality for premium users

## Prerequisites

- Razorpay account (Test/Live mode)
- API Key and Secret from Razorpay Dashboard
- PHP 7.4+ with cURL extension
- MySQL database

## Configuration

### 1. Razorpay Setup

1. Sign up at [Razorpay](https://razorpay.com)
2. Get your API Key and Secret from Dashboard → Settings → API Keys
3. Configure the keys in `config/razorpay.php`:

```php
<?php
// Razorpay Configuration
$razorpay_config = [
    'key_id' => 'rzp_test_your_key_id',        // Replace with your Key ID
    'key_secret' => 'your_secret_key',         // Replace with your Secret Key
    'currency' => 'INR',
    'test_mode' => true                        // Set to false for production
];
```

### 2. Database Setup

Ensure the following tables exist (automatically created by `install.php`):

- `users` table with `is_premium` and `premium_expires_at` columns
- `payments` table for tracking transactions

## Payment Flow

### 1. User Initiates Payment

- User visits `premium.php` page
- Selects subscription plan
- Clicks "Upgrade to Premium" button

### 2. Order Creation

- `payment/create_order.php` creates Razorpay order
- Returns order details to frontend

### 3. Payment Processing

- Razorpay Checkout opens
- User completes payment
- Webhook or redirect handles success/failure

### 4. Verification & Activation

- `payment/verify_payment.php` verifies payment
- Updates user premium status
- Redirects to success page

## File Structure

```
payment/
├── create_order.php          # Creates Razorpay order
└── verify_payment.php        # Verifies and processes payment

api/
├── payment_success.php       # Handles payment success callback

config/
└── razorpay.php              # Razorpay configuration

razorpay-php-master/          # Razorpay PHP SDK
├── Razorpay.php
└── src/
    ├── Api.php
    ├── Order.php
    └── Payment.php
```

## API Endpoints

### Create Order
```
POST /payment/create_order.php
Body: { "amount": 299, "plan": "monthly" }
Response: Razorpay order details
```

### Verify Payment
```
POST /payment/verify_payment.php
Body: Razorpay payment response
Response: Success/Failure status
```

### Payment Success Callback
```
POST /api/payment_success.php
Body: Webhook data from Razorpay
Response: Acknowledgment
```

## Testing

### Test Cards (Razorpay Test Mode)

| Card Number | Expiry | CVV | Result |
|-------------|--------|-----|--------|
| 4111 1111 1111 1111 | 12/30 | 123 | Success |
| 4000 0000 0000 0002 | 12/30 | 123 | Failure |

### UPI Testing
- Use any valid UPI ID in test mode
- Payment will be simulated

for testing mode upi ids
upi id - test@razorpay
        success@razorpay

## Subscription Plans

| Plan | Amount | Duration | Features |
|------|--------|----------|----------|
| Monthly | ₹299 | 30 days | All premium features |
| Yearly | ₹2999 | 365 days | All premium features + 2 months free |

## Premium Features

- ✅ Unlimited music downloads
- ✅ Ad-free listening
- ✅ High-quality audio
- ✅ Offline listening
- ✅ Priority support

## Troubleshooting

### Common Issues

1. **Payment fails with "Invalid API Key"**
   - Check `config/razorpay.php` configuration
   - Ensure keys are for correct mode (test/live)

2. **Webhook not working**
   - Verify webhook URL in Razorpay dashboard
   - Check server logs for webhook data
   - Ensure `api/payment_success.php` is accessible

3. **Payment verification fails**
   - Check payment signature verification
   - Ensure order ID matches
   - Verify database connection

4. **Subscription not activating**
   - Check `verify_payment.php` logs
   - Ensure user ID is correct
   - Verify database update queries

### Debug Mode

Enable debug logging in `payment/verify_payment.php`:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Security Considerations

- Always verify payment signatures
- Use HTTPS in production
- Store API keys securely (environment variables)
- Validate all input data
- Implement rate limiting on payment endpoints

## Production Deployment

1. Switch to live Razorpay keys
2. Set `test_mode` to `false` in config
3. Configure webhooks for live mode
4. Enable SSL certificate
5. Test with real payment methods
6. Monitor payment logs

## Support

For Razorpay integration issues:
- Check [Razorpay Documentation](https://docs.razorpay.com)
- Review PHP SDK documentation
- Contact Razorpay support

For application-specific issues:
- Check server error logs
- Verify database connectivity
- Test with different browsers/devices
