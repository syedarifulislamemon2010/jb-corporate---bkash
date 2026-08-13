<?php

return [
    /*
    |--------------------------------------------------------------------------
    | bKash SFTP Configuration
    |--------------------------------------------------------------------------
    */
    'sftp_source_path' => env('BKASH_SFTP_SOURCE_PATH', '/var/www/html/beftn-bach-rtgs/storage/app/public/bkash'),
    'sftp_uploaded_path' => env('BKASH_SFTP_UPLOADED_PATH', '/var/www/html/beftn-bach-rtgs/storage/app/public/bkash_uploaded'),

    /*
    |--------------------------------------------------------------------------
    | SMS Gateway Configuration
    |--------------------------------------------------------------------------
    */
    'sms_enabled' => env('BKASH_SMS_ENABLED', false),
    'sms_api_url' => env('BKASH_SMS_API_URL', ''),
    'sms_api_key' => env('BKASH_SMS_API_KEY', ''),
    'sms_sender_id' => env('BKASH_SMS_SENDER_ID', 'JANATABANK'),

    /*
    |--------------------------------------------------------------------------
    | Email Notification Configuration
    |--------------------------------------------------------------------------
    */
    'email_enabled' => env('BKASH_EMAIL_ENABLED', true),
    'email_from_address' => env('BKASH_EMAIL_FROM', 'noreply@janatabank-bd.com'),
    'email_from_name' => env('BKASH_EMAIL_FROM_NAME', 'Janata Bank Corporate Portal'),

    'whitelisted_debit_accounts' => env('BKASH_WHITELISTED_DEBIT_ACCOUNTS', '0100202707747,0100224107522'),
    'rtgs_min_limit' => (int) env('BKASH_RTGS_MIN_LIMIT', 100000),
    'initial_balances' => [
        '0100202707747' => (float) env('BKASH_TCSA_INITIAL_BALANCE', 542000000.50),
        '0100224107522' => (float) env('BKASH_OPS_INITIAL_BALANCE', 18500000.00),
    ],
];
