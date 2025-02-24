<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Require admin access
requireAdmin();

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['path'])) {
        throw new Exception('No file path provided');
    }

    $filePath = $data['path'];

    // Security check: ensure the path is within the uploads directory
    $uploadsDir = realpath(__DIR__ . '/../uploads');
    $realPath = realpath(__DIR__ . '/../' . $filePath);

    if (!$realPath || strpos($realPath, $uploadsDir) !== 0) {
        throw new Exception('Invalid file path');
    }

    // Get database connection
    $conn = getDbConnection();

    // Check if file is used as logo in settings
    $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_value = ?");
    $stmt->bind_param('s', $filePath);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        throw new Exception('This file is currently used as the application logo');
    }

    // Delete file from filesystem
    if (file_exists($realPath)) {
        if (!unlink($realPath)) {
            throw new Exception('Failed to delete file from filesystem');
        }
    }

    // Delete from media table if exists
    $stmt = $conn->prepare("DELETE FROM media WHERE file_path = ?");
    $stmt->bind_param('s', $filePath);
    $stmt->execute();

    // Delete from seo_logs table if exists (only the image reference)
    $stmt = $conn->prepare("UPDATE seo_logs SET image_path = NULL WHERE image_path = ?");
    $stmt->bind_param('s', $filePath);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'File deleted successfully',
        'path' => $filePath
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 