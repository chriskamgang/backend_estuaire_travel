<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'freemopay' => [
        'base_url'   => env('FREEMOPAY_BASE_URL', 'https://api-v2.freemopay.com'),
        'app_key'    => env('FREEMOPAY_APP_KEY', ''),
        'secret_key' => env('FREEMOPAY_SECRET_KEY', ''),
    ],

    'nexah' => [
        'base_url'  => env('NEXAH_BASE_URL', 'https://smsvas.com/bulk/public/index.php/api/v1'),
        'username'  => env('NEXAH_USERNAME', ''),
        'password'  => env('NEXAH_PASSWORD', ''),
        'sender_id' => env('NEXAH_SENDER_ID', 'UES'),
    ],

];
