<?php
/**
 * bootstrap.php
 * Included at the top of every page. Loads configuration,
 * autoloads all classes, and loads shared helper functions.
 */

require_once __DIR__ . '/../config/config.php';

spl_autoload_register(function ($class) {
    $path = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

require_once __DIR__ . '/functions.php';
