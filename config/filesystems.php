<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'scaleway' => [
            'driver' => 's3',
            'key' => env('SCALEWAY_ACCESS_KEY_ID'),
            'secret' => env('SCALEWAY_SECRET_ACCESS_KEY'),
            'region' => env('SCALEWAY_DEFAULT_REGION'),
            'bucket' => env('SCALEWAY_BUCKET'),
            'url' => env('SCALEWAY_URL'),
            'endpoint' => env('SCALEWAY_ENDPOINT'),
            'use_path_style_endpoint' => env('SCALEWAY_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        'glacier' => [
            'driver' => 's3',
            'key' => env('GLACIER_ACCESS_KEY_ID'),
            'secret' => env('GLACIER_SECRET_ACCESS_KEY'),
            'region' => env('GLACIER_DEFAULT_REGION'),
            'bucket' => env('GLACIER_BUCKET'),
            'url' => env('GLACIER_URL'),
            'endpoint' => env('GLACIER_ENDPOINT'),
            'use_path_style_endpoint' => env('GLACIER_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
