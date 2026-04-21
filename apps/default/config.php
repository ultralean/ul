<?php

/**
 * What this config file has
 * 1. App configuration
 * 2. Flash messages configuration
 * 3. Security configuration
 * 4. Database configuration
 * 5. Logging configuration
 * 6. I18n configuration
 */

$webroot = getcwd(); // The public directory (public_html, public, htdocs, www, wwwroot, etc.) which contains index.php

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

        'webroot' => $webroot, // Please don't change this

        'uploads_path' => $webroot . '/uploads',

        'timezone' => 'Asia/Karachi', // Set timezone for the application and database

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

    /**
     * Flash messages configuration
     * 
     * Flash messages are used to store messages that are displayed to the user after a redirect.
     * 
     * Recommended:
     * - use_cookies: false → for stateful applications (session-based)
     * - use_cookies: true  → for stateless applications (cookie-based)
     */
    'flash' => [
        'use_cookies' => false, // only enable this if you are using stateless mode (no sessions) e.g. API
        'cookie_key' => '_ul_flash',
        'cookie_secret' => FLASH_SECRET,
    ],

    /**
     * Security configuration
     * 
     * Recommended:
     * - csrf: true  → for stateful applications
     * - csrf: false → for stateless applications
     * 
     * Recommended:
     * - cors: true  → for stateful applications
     * - cors: false → for stateless applications
     * 
     * Recommended:
     * - csp: true  → for stateful applications
     * - csp: false → for stateless applications
     * 
     * Recommended:
     * - rate_limit: true  → for stateful applications
     * - rate_limit: false → for stateless applications
     */

    'security' => [

        /* =========================
     * API
     * ========================= 
     * 
     * API Key is used to authenticate API requests.
     * 
     * Example:
     * 
     * curl -X GET https://example.com/api/users \
     *   -H "Authorization: your-secret-key"
     * 
     * See api_protect() helper function in system/helpers/security.php
     * 
     * */
        'api' => [
            'key' => 'your-secret-key',
        ],

        /* =========================
     * CSRF
     * ========================= */
        'csrf' => [
            'enabled' => true,

            // Routes to skip CSRF (prefix match)
            'except' => [
                '/api',     // skip all API routes
            ],

            // Header for API / AJAX
            'header' => 'X-CSRF-TOKEN',
        ],

        /* =========================
     * CORS
     * ========================= */
        'cors' => [
            'enabled' => true,
            'allow_origin'  => '*',
            'allow_methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'allow_headers' => 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN',
        ],

        /* =========================
     * CSP
     * ========================= */
        'csp' => [
            'enabled' => true,
            'policy' => "default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self' 'unsafe-inline';",
        ],

        /* =========================
     * RATE LIMIT
     * ========================= */
        'rate_limit' => [
            'enabled' => true,
            'max' => 1000,
            'window' => 60, // seconds
        ],
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

        // Global locale settings (used everywhere)
        'default'   => 'en',
        'fallback'  => 'en',
        'supported' => ['en', 'ur', 'ar'],

        // Static translations (files), used for UI texts
        'static' => [
            'enabled' => true,
            'path'    => APP_PATH . '/lang',
        ],

        // Dynamic translations (database), used for dynamic data from database
        // Models must have translation table and columns defined
        'database' => [
            'enabled' => true,
        ],
    ],
];
