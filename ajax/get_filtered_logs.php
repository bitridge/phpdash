<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/seo_log.php';

// Require login
requireLogin();

// Set JSON content type
header('Content-Type: application/json');

// Validate input parameters
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Validate dates
if (!$projectId || !$startDate || !$endDate) {
    echo json_encode([
        'error' => 'Missing required parameters'
    ]);
    exit;
}

try {
    // Get filtered logs
    $logs = getSeoLogsByDateRange($projectId, $startDate, $endDate);
    
    // Return logs as JSON
    echo json_encode($logs);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Failed to fetch logs: ' . $e->getMessage()
    ]);
} 