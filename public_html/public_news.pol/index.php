<?php

define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/apps/app_news');
define('SYSTEM_PATH', BASE_PATH . '/system');

require SYSTEM_PATH . '/core/Autoloader.php';

use UltraLean\Core\Autoloader;
use UltraLean\Core\Router;

$loader = new Autoloader(APP_PATH, SYSTEM_PATH);
$loader->register();

$router = new Router(APP_PATH);
$router->dispatch();
