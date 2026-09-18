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
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT', env('GOOGLE_CALLBACK_REDIRECTS'))
    ],
    'stripe' => [
        'monthly' => env('STRIPE_PRICE_MONTHLY'),
        'yearly' => env('STRIPE_PRICE_YEARLY'),
    ],
    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),
        'reasoning_effort' => env('GROQ_REASONING_EFFORT', 'low'),
        'url' => env('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions'),
        'daily_limit_free' => (int) env('AI_DAILY_LIMIT_FREE', 3),
        'daily_limit_pro' => (int) env('AI_DAILY_LIMIT_PRO', 15),
    ],

    'cloudflare' => [
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'api_token'  => env('CLOUDFLARE_API_TOKEN'),
        'r2_bucket'  => env('R2_BUCKET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Agente de MetaTrader
    |--------------------------------------------------------------------------
    |
    | Topes por minuto de los endpoints del `.exe`. Un terminal normal sincroniza
    | una vez por minuto, así que 60 le sobra de largo; el tope por IP es más
    | alto para que una oficina con varios terminales detrás del mismo NAT no se
    | frene sola, y es el que acota la fuerza bruta sobre `sync_token`.
    |
    */

    'mt5' => [
        'rate_limit_token' => (int) env('MT5_RATE_LIMIT_TOKEN', 60),
        'rate_limit_ip' => (int) env('MT5_RATE_LIMIT_IP', 120),
    ],



];
