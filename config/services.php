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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => '/auth/google/callback',
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => '/auth/github/callback',
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => '/auth/facebook/callback',
    ],

    'twitter-oauth-2' => [
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect' => '/auth/twitter-oauth-2/callback',
    ],

    'linkedin-openid' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => '/auth/linkedin-openid/callback',
    ],

    'bitbucket' => [
        'client_id' => env('BITBUCKET_CLIENT_ID'),
        'client_secret' => env('BITBUCKET_CLIENT_SECRET'),
        'redirect' => '/auth/bitbucket/callback',
    ],

    'gitlab' => [
        'client_id' => env('GITLAB_CLIENT_ID'),
        'client_secret' => env('GITLAB_CLIENT_SECRET'),
        'redirect' => '/auth/gitlab/callback',
    ],

    'youtube' => [
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'redirect' => '/integrations/youtube/callback',
    ],

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_signing_secret' => env('STRIPE_WEBHOOK_SIGNING_SECRET'),
    ],

    'paddle' => [
        'vendor_id' => env('PADDLE_VENDOR_ID'),
        'client_side_token' => env('PADDLE_CLIENT_SIDE_TOKEN'),
        'vendor_auth_code' => env('PADDLE_VENDOR_AUTH_CODE'),
        'public_key' => env('PADDLE_PUBLIC_KEY'),
        'webhook_secret' => env('PADDLE_WEBHOOK_SECRET'),
        'is_sandbox' => env('PADDLE_IS_SANDBOX', false),
    ],

    'lemon-squeezy' => [
        'api_key' => env('LEMON_SQUEEZY_API_KEY'),
        'store_id' => env('LEMON_SQUEEZY_STORE_ID'),
        'signing_secret' => env('LEMON_SQUEEZY_SIGNING_SECRET'),
        'is_test_mode' => env('LEMON_SQUEEZY_IS_TEST_MODE', false),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    'dub' => [
        'main_domain' => env('DUB_MAIN_DOMAIN'),
        'api_key' => env('DUB_API_KEY'),
        'create_link_url' => env('DUB_CREATE_LINK_URL'),
        'update_link_url' => env('DUB_UPDATE_LINK_URL'),
        'create_tag_url' => env('DUB_CREATE_TAG_URL'),
        
        // Rate limiting settings
        'rate_limit_requests' => env('DUB_RATE_LIMIT_REQUESTS', 100), // Requests per window
        'rate_limit_window' => env('DUB_RATE_LIMIT_WINDOW', 60), // Window in minutes
        
        // Retry and timeout settings
        'max_retries' => env('DUB_MAX_RETRIES', 3),
        'retry_delay' => env('DUB_RETRY_DELAY', 2), // Base delay in seconds
        'timeout' => env('DUB_TIMEOUT', 15), // Request timeout in seconds
    ],

    'supadata' => [
        'base_url' => env('SUPADATA_BASE_URL'),
        'api_key' => env('SUPADATA_API_KEY'),
    ],

    'makedotcom' => [
        'webhook_url' => [
            'request_yt_channel_videos_supadata' => env('REQUEST_YT_CHANNEL_VIDEOS_SUPADATA_MAKEDOTCOM'),
            'request_yt_video_details' => env('REQUEST_YT_VIDEO_DETAILS_SUPADATA_MAKEDOTCOM'),
        ],
        'webhook_token' => env('MAKEDOTCOM_WEBHOOK_TOKEN'),
    ],

    'local' => [
        'base_url' => env('TESTING_URL'),
    ],

];
