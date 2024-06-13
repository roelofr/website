<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services.
    | This file provides a sane default location for this type of information,
    | allowing packages to have a conventional place to find your various
    | credentials.
    |
    */

    // Conscribo API
    'conscribo' => [
        'url' => env('CONSCRIBO_FORCE_URL'),
        'account' => env('CONSCRIBO_ACCOUNT', 'test'),
        'username' => env('CONSCRIBO_USERNAME'),
        'password' => env('CONSCRIBO_PASSWORD'),

        'settings' => [
            'groups_to_download' => Str::of(env('CONSCRIBO_GROUPS_TO_DOWNLOAD', 'lid,a-leden,erelid,oud-lid,begunstiger'))
                ->lower()
                ->explode(',')
                ->map('trim')
                ->all(),
        ],
    ],

    // iZettle API
    'izettle' => [
        'client-id' => env('IZETTLE_CLIENT_ID'),
        'client-assertion' => env('IZETTLE_CLIENT_ASSERTION'),
    ],

    // Google APIs
    'google' => [
        // Set if the Google APIs are enabled
        'enabled' => env('GOOGLE_ENABLED', false),

        // Key file
        'key-file' => env('GOOGLE_AUTH_FILE', storage_path('auth/google.json')),

        // Set user who we use to sign in as
        'subject' => $googleSubject = env('GOOGLE_AUTH_USER', 'domain-admin@gumbo-millennium.nl'),

        // Make sure our Service Account logged in using the auth file is granted
        // domain-wide authority. For more information, read this:
        // https://github.com/googleapis/google-api-php-client/blob/master/docs/oauth-server.md#delegating-domain-wide-authority-to-the-service-account
        'scopes' => [
            \Google\Service\Directory::ADMIN_DIRECTORY_GROUP,
            \Google\Service\Groupssettings::APPS_GROUPS_SETTINGS,
        ],

        // Allowed domains
        'domains' => [
            'gumbo.nu',
            'gumbo-millennium.nl',
            'activiteiten.gumbo-millennium.nl',
            'organen.gumbo-millennium.nl',
        ],

        // Google Wallet configuration
        'wallet' => [
            // Should Google Wallet be enabled to start with
            'enabled' => env('GOOGLE_WALLET_ENABLED', false),

            // JSON key
            'key_file' => env('GOOGLE_WALLET_AUTH_FILE', env('GOOGLE_AUTH_FILE', storage_path('auth/google-wallet.json'))),

            // Google Wallet Issuer ID
            'issuer_id' => env('GOOGLE_WALLET_ISSUER_ID', null),
        ],
    ],

    // Tenor gif search
    'tenor' => [
        /**
         * Tenor API key.
         * @link <https://developers.google.com/tenor/guides/quickstart>
         */
        'api_key' => env('TENOR_API_KEY'),

        /**
         * Storage settings.
         */
        'storage' => [
            'disk' => env('TENOR_DISK', 'public'),
            'path' => env('TENOR_BASE_DIR', 'gifs/storage'),
        ],

        /**
         * Preloaded search settings.
         */
        'terms' => [
            // User messed up
            'wrong' => [
                'term' => 'wrong',
                'limit' => 10,
            ],
            // User wants to sack someone
            'fired' => [
                'term' => 'kicked out',
                'limit' => 10,
            ],
            // User wants to sack themselves
            'fired-self' => [
                'term' => 'suicide',
                'limit' => 10,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature flags
    |--------------------------------------------------------------------------
    |
    | These flags indicate if a certain feature is available for this platform.
    | These features might be disabled by choice or if a certain dependency
    | is not available (which is the case with Laravel Nova)
     */
    'features' => [
        // Only enable Laravel Nova if installed and not disabled by the user
        'enable-nova' => env('FEATURE_DISABLE_NOVA', false) !== true,
    ],
];
