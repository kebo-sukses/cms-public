<?php
// Bootstrap for PHPUnit tests
// Load Composer autoload if available so PHPUnit classes are found by analyzers
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
	require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Provide lightweight PHPUnit stubs for static analysis environments
    require_once __DIR__ . '/phpunit_stubs.php';
require_once __DIR__ . '/../admin/api/_auth.php';

// ensure session available
if (session_status() === PHP_SESSION_NONE) session_start();

?>
