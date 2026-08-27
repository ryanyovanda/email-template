<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI abuse guards
    |--------------------------------------------------------------------------
    |
    | How much a user may spend is decided by their credit balance below. These
    | are the separate burst guards that stop a runaway client from emptying a
    | balance in seconds, and the threshold the admin dashboard flags.
    |
    */

    'ai' => [
        // Requests per minute per user, guarding against runaway clients.
        'rate_limit_per_minute' => (int) env('AI_RATE_LIMIT_PER_MINUTE', 3),

        // Flagged in the admin CMS as likely abuse.
        'abuse_threshold_per_day' => (int) env('AI_ABUSE_THRESHOLD_PER_DAY', 20),

    ],

    /*
    |--------------------------------------------------------------------------
    | Credits
    |--------------------------------------------------------------------------
    |
    | Every service that costs real money is paid for in credits. Prices are set
    | from measured usage: an application draft runs about 1,300 DeepSeek tokens
    | and a whole template about 4,000, roughly three times as much.
    |
    | The monthly allowance is a top-up rather than an addition, so an inactive
    | account does not accumulate a balance it never intended to grant.
    |
    */

    'credits' => [
        'prices' => [
            'application_draft' => (int) env('CREDIT_PRICE_APPLICATION_DRAFT', 10),
            'template_design' => (int) env('CREDIT_PRICE_TEMPLATE_DESIGN', 30),
        ],

        'monthly_grant' => (int) env('CREDIT_MONTHLY_GRANT', 300),

        // Paid to the author when an admin publishes their design to the shared
        // library. Above the design price, so a published template is a net win.
        'promotion_reward' => (int) env('CREDIT_PROMOTION_REWARD', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Terms for AI-designed templates
    |--------------------------------------------------------------------------
    |
    | Shown next to the consent checkbox before a user generates a template, and
    | stamped on the template row when they accept.
    |
    */

    'template_terms_version' => '2026-08-19',

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
