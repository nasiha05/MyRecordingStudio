<?php
/**
 * config.php
 * Global configuration: database credentials + business rules constants.
 * Edit DB_USER / DB_PASS below if your XAMPP MySQL uses a different login.
 */

// ---- Database connection settings (default XAMPP values) ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'myrecordingstudio');
define('DB_USER', 'root');
define('DB_PASS', '');        // default XAMPP root password is empty

// ---- Business rules (from assignment spec) ----
define('OPEN_HOUR', 10);      // 10:00 AM
define('CLOSE_HOUR', 22);     // 10:00 PM
define('MIN_DURATION', 1);    // 1 hour minimum
define('MAX_DURATION', 12);   // 12 hours maximum

// Use Singapore time for all booking date/time calculations.
date_default_timezone_set('Asia/Singapore');

// Start PHP session (needed on every page)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Base URL (computed automatically - works no matter what folder ----
// ---- name the project sits under in htdocs, e.g. /MyRecordingStudio/ ----
// This looks at the current request path and strips off anything from
// /admin/, /client/, or /auth/ onwards, so it always points at the
// project's own root folder (where index.php lives).
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
if (preg_match('#^(.*)/(admin|client|auth)/#', $scriptName, $m)) {
    $computedBase = $m[1] . '/';
} else {
    $computedBase = rtrim(str_replace('\\', '/', dirname($scriptName)), '/') . '/';
}
define('BASE_URL', $computedBase);
