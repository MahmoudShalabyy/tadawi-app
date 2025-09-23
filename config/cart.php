<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cart Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for shopping cart functionality
    |
    */

    'max_quantity_per_medicine' => env('CART_MAX_QUANTITY_PER_MEDICINE', 2),
    'session_expiry_hours' => env('CART_SESSION_EXPIRY_HOURS', 24),
    'auto_cleanup_enabled' => env('CART_AUTO_CLEANUP_ENABLED', true),
];
