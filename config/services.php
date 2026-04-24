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

    'ecpay' => [
        'merchant_id' => env('ECPAY_MERCHANT_ID'),
        'hash_key' => env('ECPAY_HASH_KEY'),
        'hash_iv' => env('ECPAY_HASH_IV'),
        'checkout_action' => env('ECPAY_CHECKOUT_ACTION', 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5'),
        'sdk_url' => env('ECPAY_SDK_URL', 'https://ecpg-stage.ecpay.com.tw/Scripts/sdk-1.0.0.js'),
        'sdk_server_type' => env('ECPAY_SDK_SERVER_TYPE', 'Stage'),
        'ecpg_create_token_url' => env('ECPAY_ECPG_CREATE_TOKEN_URL', 'https://ecpg-stage.ecpay.com.tw/Merchant/GetTokenbyTrade'),
        'ecpg_create_payment_url' => env('ECPAY_ECPG_CREATE_PAYMENT_URL', env('ECPAY_ECPG_CREATE_TRADE_URL', 'https://ecpg-stage.ecpay.com.tw/Merchant/CreatePayment')),
        'allow_redirect_fallback' => (bool) env('ECPAY_ALLOW_REDIRECT_FALLBACK', false),
    ],

    'google_places' => [
        'api_key' => env('GOOGLE_PLACES_API_KEY'),
        'endpoint' => env('GOOGLE_PLACES_ENDPOINT', 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json'),
        'timeout' => (int) env('GOOGLE_PLACES_TIMEOUT', 8),
    ],

    'microsoft_graph' => [
        'enabled' => (bool) env('MS_GRAPH_MAIL_ENABLED', false),
        'auth_mode' => env('MS_GRAPH_AUTH_MODE', 'auto'),
        'tenant_id' => env('MS_GRAPH_TENANT_ID', env('TENANT_ID')),
        'client_id' => env('MS_GRAPH_CLIENT_ID', env('CLIENT_ID')),
        'client_secret' => env('MS_GRAPH_CLIENT_SECRET'),
        'sender' => env('MS_GRAPH_SENDER'),
        'user_scopes' => env('GRAPH_USER_SCOPES', 'User.Read Mail.Send offline_access'),
        'base_url' => env('MS_GRAPH_BASE_URL', 'https://graph.microsoft.com'),
    ],

    'uber_eats' => [
        'api_base_url' => env('UBER_EATS_API_BASE_URL', 'https://api.uber.com'),
        'auth_url' => env('UBER_EATS_AUTH_URL', 'https://auth.uber.com/oauth/v2/token'),
        'scopes' => env('UBER_EATS_SCOPES', 'eats.store eats.order eats.store.orders.read'),
        'timeout' => (int) env('UBER_EATS_TIMEOUT', 15),
    ],

    'foodpanda' => [
        'api_base_url' => env('FOODPANDA_API_BASE_URL', 'https://foodpanda.partner.deliveryhero.io'),
        'auth_url' => env('FOODPANDA_AUTH_URL', 'https://foodpanda.partner.deliveryhero.io/v2/oauth/token'),
        'timeout' => (int) env('FOODPANDA_TIMEOUT', 15),
    ],

];
