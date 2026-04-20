<?php

/**
 * UltraLean Micro-Framework
 * UltraLean is a fast, lightweight, and flexible PHP framework for building web applications.
 * It provides a solid foundation for building web applications with a focus on performance and simplicity.
 */

// Set strict types for better type safety
declare(strict_types=1);

/**
 * Define the request ID, this will be used to identify the request
 */
define('REQUEST_ID', bin2hex(random_bytes(8)));

/**
 * Define the flash secret, this will be used to encrypt the flash messages
 */
define('FLASH_SECRET', bin2hex(random_bytes(32)));

/**
 * Define the application name, this will be used to identify the application
 * For the first or single site / app the value must be 'default'
 * This will be used to load the application configuration, controllers, models etc.
 * If you have multiple applications, you can change this value to load different applications
 */
define('APP', 'default');

/**
 * Define the base path of the application
 */
define('BASE_PATH', dirname(__DIR__));

/**
 * Define the application path
 */
define('APP_PATH', BASE_PATH . '/apps/' . APP);

/**
 * Define the system path
 */
define('SYSTEM_PATH', BASE_PATH . '/system');
define('STORAGE_PATH', APP_PATH . '/storage');

/**
 * Load Composer
 */
$composer_file = BASE_PATH . '/vendor/autoload.php';
if (file_exists($composer_file)) {
    require $composer_file;
} else {
    die('Composer not found.');
}

/**
 * Load config helper
 */

function config(?string $key = null, mixed $default = null): mixed
{
    static $config;
    static $cache = [];

    if ($config === null) {
        $config = require APP_PATH . '/config.php';
    }

    if ($key === null) {
        return $config;
    }

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    // FAST PATH (no dot)
    if (!str_contains($key, '.')) {
        return $cache[$key] = $config[$key] ?? $default;
    }

    $value = $config;

    foreach (explode('.', $key) as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }

    return $cache[$key] = $value;
}


/**
 * Environment setup
 */
$appEnv = config('app.env') ?? 'production';

/**
 * Set PHP error display based on environment
 */
if ($appEnv === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

/**
 * Bootstrap Core System
 */
require SYSTEM_PATH . '/core/Autoloader.php';

// Set timezone from config file.
// Do not change this value here. Update this value in config file.
$timezone = config('app.timezone', 'UTC');
date_default_timezone_set($timezone);

use UltraLean\Core\AutoLoader;
use UltraLean\Core\Database;
use UltraLean\Core\Logger;
use UltraLean\Core\ErrorHandler;
use UltraLean\Core\Router;
use UltraLean\Core\DBManager;
use UltraLean\Core\I18n\Locale;
use UltraLean\Core\Session;

/**
 * Register autoloader
 */
$loader = new AutoLoader();

/**
 * Initialize Logger (your abstraction)
 */
$logger = new Logger();

/**
 * Register Error Handler (central system)
 */
ErrorHandler::register();


/**
 * =========================
 * ⚡ FLASH CONFIG (FAST ACCESS)
 * =========================
 */

define('FLASH_USE_COOKIES', config('flash.use_cookies', false));
define('FLASH_COOKIE_KEY', config('flash.cookie_key', '_ul_flash'));
define('FLASH_COOKIE_SECRET', config('flash.cookie_secret', bin2hex(random_bytes(32))));

/**
 * =========================
 * 📦 LOAD HELPERS
 * =========================
 */

require SYSTEM_PATH . '/helpers/helpers.php';
require SYSTEM_PATH . '/helpers/flash.php';
require SYSTEM_PATH . '/helpers/form.php';
require SYSTEM_PATH . '/helpers/security.php';

/**
 * =========================
 * 🚀 INIT FLASH SYSTEM
 * =========================
 */

// Start session ONLY if not CLI
if (PHP_SAPI !== 'cli') {
    Session::start();

    // Initialize flash lifecycle
    flash_init();
}

// Initialize locale
Locale::init();

/**
 * Initialize CORS
 */
if (config('security.cors.enabled', false)) {

    header('Access-Control-Allow-Origin: ' . config('security.cors.allow_origin'));
    header('Access-Control-Allow-Methods: ' . config('security.cors.allow_methods'));
    header('Access-Control-Allow-Headers: ' . config('security.cors.allow_headers'));

    // Preflight (instant exit)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * Load routes
 */
Router::boot();

/**
 * Dispatch router
 */
Router::dispatch();
