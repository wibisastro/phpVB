<?php

/**
 * PHPUnit Bootstrap
 *
 * Loads Composer autoloader and sets up test environment.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Define constants used throughout the application
if (!defined('STAGE')) {
    define('STAGE', 'test');
}
