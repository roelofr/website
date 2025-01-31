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

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "rackspace"
    |
    */

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ],

        'scaleway' => [
            'driver' => 's3',
            'key' => env('SCALEWAY_ACCESS_KEY_ID'),
            'secret' => env('SCALEWAY_SECRET_ACCESS_KEY'),
            'region' => env('SCALEWAY_DEFAULT_REGION'),
            'endpoint' => env('SCALEWAY_ENDPOINT'),
            'bucket' => env('SCALEWAY_BUCKET'),
            'url' => env('SCALEWAY_URL'),
        ],

        'glacier' => [
            'driver' => 's3',
            'key' => env('GLACIER_ACCESS_KEY_ID'),
            'secret' => env('GLACIER_SECRET_ACCESS_KEY'),
            'region' => env('GLACIER_DEFAULT_REGION'),
            'endpoint' => env('GLACIER_ENDPOINT'),
            'bucket' => env('GLACIER_BUCKET'),
            'url' => env('GLACIER_URL'),
        ],
    ],
];
