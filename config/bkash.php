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
];
