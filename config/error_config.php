<?php
/**
 * Error Configuration
 * Suppresses deprecation warnings from vendor packages (PHP 8.3+ compatibility)
 * Prevents "headers already sent" errors
 */

// Start output buffering if not already started
if (!ob_get_level()) {
    ob_start();
}

// Suppress deprecation warnings from vendor packages
// These are from third-party code and will be fixed by package maintainers
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Enforce display_errors off by default to prevent "headers sent" issues
if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Production mode settings are handled above
// $isDebugMode = defined('DEBUG_MODE') ? constant('DEBUG_MODE') : false;
// if (!$isDebugMode) {
//     ini_set('display_errors', 0);
//     ini_set('log_errors', 1);
// }