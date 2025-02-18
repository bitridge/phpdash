<?php
require_once 'config.php';

/**
 * Save a new report to the database
 */
function saveReport($projectId, $title, $description, $pdfPath, $userId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        INSERT INTO reports (project_id, title, description, pdf_path, created_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([$projectId, $title, $description, $pdfPath, $userId]);
}

/**
 * Get all reports for a project
 */
function getProjectReports($projectId, $page = 1, $perPage = 10) {
    $db = getDbConnection();
    $offset = ($page - 1) * $perPage;
    
    // Get total count
    $countStmt = $db->prepare("
        SELECT COUNT(*) FROM reports WHERE project_id = ?
    ");
    $countStmt->execute([$projectId]);
    $total = $countStmt->fetchColumn();
    
    // Get reports with user info
    $stmt = $db->prepare("
        SELECT r.*, u.name as created_by_name 
        FROM reports r
        JOIN users u ON r.created_by = u.id
        WHERE r.project_id = ?
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$projectId, $perPage, $offset]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'reports' => $reports,
        'total' => $total,
        'pages' => ceil($total / $perPage)
    ];
}

/**
 * Get a single report by ID
 */
function getReport($reportId) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT r.*, u.name as created_by_name 
        FROM reports r
        JOIN users u ON r.created_by = u.id
        WHERE r.id = ?
    ");
    
    $stmt->execute([$reportId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Delete a report
 */
function deleteReport($reportId) {
    $db = getDbConnection();
    
    // Get the report to delete the PDF file
    $report = getReport($reportId);
    if ($report && file_exists($report['pdf_path'])) {
        unlink($report['pdf_path']);
    }
    
    $stmt = $db->prepare("DELETE FROM reports WHERE id = ?");
    return $stmt->execute([$reportId]);
} 