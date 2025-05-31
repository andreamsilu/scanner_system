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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from_number' => env('TWILIO_FROM_NUMBER'),
    ],

    'sms' => [
        'default_provider' => env('SMS_PROVIDER', 'nextsms'),
        
        'nextsms' => [
            'api_key' => env('NEXTSMS_API_KEY'),
            'sender_id' => env('NEXTSMS_SENDER_ID'),
            'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '255'), // Tanzania's country code
        ],

        'vonage' => [
            'key' => env('VONAGE_KEY'),
            'secret' => env('VONAGE_SECRET'),
            'from' => env('VONAGE_FROM_NUMBER'),
            'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '1'),
        ],

        // Add more providers here as needed
        // 'twilio' => [
        //     'sid' => env('TWILIO_SID'),
        //     'token' => env('TWILIO_AUTH_TOKEN'),
        //     'from' => env('TWILIO_FROM_NUMBER'),
        //     'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '1'),
        // ],
    ],

];
