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
error_reporting(E_ALL & ~E_DEPRECATED);

// In production, don't display errors (log them instead)
// Default to production mode (hide errors) unless DEBUG_MODE is explicitly set to true
$isDebugMode = defined('DEBUG_MODE') ? constant('DEBUG_MODE') : false;
if (!$isDebugMode) {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}