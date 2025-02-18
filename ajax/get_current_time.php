<?php
require_once '../config.php';
require_once '../includes/settings.php';

// Get settings instance
$settings = Settings::getInstance();

// Output current date/time
echo $settings->getCurrentDateTime(); 