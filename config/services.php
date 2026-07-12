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

    'docuseal' => [
        'api_url' => env('DOCUSEAL_API_URL'),
        'api_key' => env('DOCUSEAL_API_KEY'),
        'webhook_secret' => env('DOCUSEAL_WEBHOOK_SECRET'),
    ],

    'microsoft_graph' => [
        'tenant_id' => env('MICROSOFT_GRAPH_TENANT_ID'),
        'client_id' => env('MICROSOFT_GRAPH_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_GRAPH_CLIENT_SECRET'),
        'calendar_user_email' => env('MICROSOFT_GRAPH_CALENDAR_USER_EMAIL'),
        'recruitment_mailbox' => env('MICROSOFT_GRAPH_RECRUITMENT_MAILBOX'),
    ],

    'mail_graph_intake' => [
        'tenant_id' => env('MAIL_GRAPH_INTAKE_TENANT_ID'),
        'client_id' => env('MAIL_GRAPH_INTAKE_CLIENT_ID'),
        'client_secret' => env('MAIL_GRAPH_INTAKE_CLIENT_SECRET'),
        'mailbox' => env('MAIL_GRAPH_INTAKE_MAILBOX'),
    ],

];
