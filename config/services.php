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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'mnotify' => [
        'api_key'   => env('MNOTIFY_API_KEY'),
        'sender_id' => env('MNOTIFY_SENDER_ID', 'PCG - PMCB'),
    ],

    'pastech_sms' => [
        'api_key'         => env('PASTECH_SMS_API_KEY'),
        'sender_id'       => env('PASTECH_SMS_SENDER_ID', 'SLCECoE'),
        'endpoint'        => env('PASTECH_SMS_ENDPOINT', 'https://sms.pastechsolutions.com/smsapi'),
        'balance_endpoint'=> env('PASTECH_SMS_BALANCE_ENDPOINT', 'https://sms.pastechsolutions.com/api/smsapibalance'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
