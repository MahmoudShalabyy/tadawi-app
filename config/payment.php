<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for payment processing
    |
    */

    'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'USD'),
    
    'methods' => [
        'cash' => [
            'enabled' => true,
            'name' => 'Cash on Delivery',
            'description' => 'Pay when your order arrives'
        ],
        'paypal' => [
            'enabled' => env('PAYPAL_ENABLED', false),
            'name' => 'PayPal',
            'description' => 'Pay securely with PayPal',
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'sandbox' => env('PAYPAL_SANDBOX', true),
            'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
            'return_url' => env('PAYPAL_RETURN_URL', env('APP_URL') . '/checkout/paypal/return'),
            'cancel_url' => env('PAYPAL_CANCEL_URL', env('APP_URL') . '/checkout/paypal/cancel')
        ],
       
    ],

    'timeout' => env('PAYMENT_TIMEOUT', 300), // 5 minutes in seconds
    
    'retry_attempts' => env('PAYMENT_RETRY_ATTEMPTS', 3),
    
    'webhook_verification' => env('PAYMENT_WEBHOOK_VERIFICATION', true),
];

