<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI quota
    |--------------------------------------------------------------------------
    |
    | Successful generations a user may run before being cut off. Both windows
    | apply; the stricter one wins. A per-user override lives on the users
    | table (`ai_monthly_limit`) and admins can raise or lower it from the CMS.
    |
    */

    'ai' => [
        'daily_limit' => (int) env('AI_DAILY_LIMIT', 5),
        'monthly_limit' => (int) env('AI_MONTHLY_LIMIT', 30),

        // Requests per minute per user, guarding against runaway clients.
        'rate_limit_per_minute' => (int) env('AI_RATE_LIMIT_PER_MINUTE', 3),

        // Flagged in the admin CMS as likely abuse.
        'abuse_threshold_per_day' => (int) env('AI_ABUSE_THRESHOLD_PER_DAY', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeded administrator
    |--------------------------------------------------------------------------
    |
    | The account `db:seed` promotes to admin. Change this password immediately
    | after the first login on any real deployment.
    |
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        'name' => env('ADMIN_NAME', 'Administrator'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    */

    'uploads' => [
        'photo' => [
            'max_kb' => 4096,
            'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        ],
        'cv' => [
            'max_kb' => 8192,
            'mimes' => ['pdf', 'doc', 'docx'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Chrome extension
    |--------------------------------------------------------------------------
    |
    | Users paste the generated HTML into this extension to send it from their
    | own Gmail account, which keeps the message on their real address.
    |
    */

    'extension_url' => env(
        'EXTENSION_URL',
        'https://chromewebstore.google.com/detail/insert-and-send-html-with/bcflbfdlpegakpncdgmejelcolhmfkjh'
    ),

];
