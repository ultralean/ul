<?php

$webroot = getcwd();
$timezone = 'Asia/Karachi'; // Set timezone for the application and database

return [

    'app' => [
        'env' => 'development', // development | production

        /**
         * Full site path of website / app with trailing slash
         * Used for generating absolute URLs
         * Examples: 
         *      https://example.com/
         *      https://example.com/site1/
         *      https://example.com/site2/
         *      https://app.example.com/
         *      https://app.example.com/site1/
         *      https://app.example.com/site2/
         * 
         */
        'base_url' => 'https://pol.loc/', // with trailing slash

        'webroot' => $webroot,

        'uploads_path' => $webroot . '/uploads',

        'timezone' => $timezone,

        /**
         * Check if the view file exists before rendering.
         * 
         * When enabled, this will throw an exception if the view file is not found.
         * 
         * When disabled, the view file will be rendered without checking if it exists.
         * 
         * Recommended:
         * - true  → for development
         * - false → for production
         */
        'view_check_files' => false,

        /**
         * Force all responses to be returned as JSON.
         *
         * When enabled, this overrides automatic response detection
         * (e.g., Accept header, AJAX requests, or `/api/*` routes)
         * and ensures all responses are in JSON format.
         *
         * When disabled, the response format is determined dynamically
         * based on:
         * - Accept header (application/json)
         * - AJAX requests (X-Requested-With)
         * - URL patterns (e.g., `/api/*`)
         *
         * Recommended:
         * - true  → for API-only or headless applications
         * - false → for mixed applications (HTML + API)
         */
        'force_json' => false,
    ],

    'flash' => [
        'use_cookies' => false, // only enable this if you are using stateless mode
        'cookie_key' => '_flash',
        'cookie_secret' => 'change_this_secret',
    ],


    'database' => [
        'default' => 'default',

        // setting the below settings to true will only have effect if the logging is enabled in the "logging" section
        'log_queries' => false,        // log all queries
        'slow_query_ms' => 0,          // log only queries slower than X ms (0 = disabled)

        'connections' => [

            'default' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'database' => 'mrklic',
                'username' => 'root',
                'password' => 'root',
                'port' => 3306,
                'charset' => 'utf8mb4',
                'timezone' => $timezone,
                'persistent' => false,
                'retry_attempts' => 2,
                'retry_delay_ms' => 100,
                'options' => [
                    PDO::ATTR_TIMEOUT => 5,
                ],
            ],

            'pgsql1' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'database' => 'reports',
                'username' => 'postgres',
                'password' => '',
                'port' => 5432,
                'charset' => 'UTF8',
                'timezone' => $timezone,
                'persistent' => false,
                'retry_attempts' => 2,
                'retry_delay_ms' => 100,
                'options' => [
                    PDO::ATTR_TIMEOUT => 5,
                ],
            ],

            'sqlite1' => [
                'driver' => 'sqlite',
                'database' => STORAGE_PATH . '/db.sqlite',
                'options' => [],
            ],
        ],
    ],

    'logging' => [
        'enabled' => true, // true | false, setting this to false will disable all logging even in development
        'level' => 'error', // debug | info | notice | warning | error | critical | alert | emergency
        /**
         * Enable logging in development environment and set log level for development environment
         * Default: false
         */
        'enabled_in_development' => true,
        'level_in_development' => 'debug', // debug | info | notice | warning | error | critical | alert | emergency
        'rotate' => true,
        'max_files' => 120, // 120 days to keep logs
    ],

    'i18n' => [

        'enabled' => true,

        'default' => 'en',
        'fallback' => 'en',

        'supported' => ['en', 'ur', 'ar'],

        'path' => APP_PATH . '/lang',

        'resolver' => [
            'session' => true,
        ],

        'database' => [
            'enabled' => true,
            'strategy' => 'join',
            'fallback_in_php' => true,
        ],
    ],
];
