<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'phpdash');

// Application configuration
define('SITE_URL', 'http://localhost/seo_dashboard');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
session_start(); 