<?php
require_once __DIR__ . '/db.php';

function getSeoLogs($projectId, $page = 1, $perPage = 10) {
    $conn = getDbConnection();
    
    // Calculate offset
    $offset = ($page - 1) * $perPage;
    
    // Base query
    $baseQuery = "FROM seo_logs s 
                  LEFT JOIN users u ON s.created_by = u.id 
                  WHERE s.project_id = " . (int)$projectId;
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery;
    $countResult = $conn->query($countQuery);
    $totalCount = $countResult->fetch_assoc()['total'];
    
    // Get logs for current page
    $query = "SELECT s.*, 
              u.name as created_by_name 
              " . $baseQuery . "
              ORDER BY s.log_date DESC, s.created_at DESC 
              LIMIT $offset, $perPage";
              
    $result = $conn->query($query);
    
    $logs = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    
    return [
        'logs' => $logs,
        'total' => $totalCount,
        'pages' => ceil($totalCount / $perPage)
    ];
}

function getAllSeoLogs($page = 1, $perPage = 10) {
    $conn = getDbConnection();
    
    // Calculate offset
    $offset = ($page - 1) * $perPage;
    
    // Base query
    $baseQuery = "FROM seo_logs s 
                  LEFT JOIN users u ON s.created_by = u.id 
                  LEFT JOIN projects p ON s.project_id = p.id
                  LEFT JOIN customers c ON p.customer_id = c.id";
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery;
    $countResult = $conn->query($countQuery);
    $totalCount = $countResult->fetch_assoc()['total'];
    
    // Get logs for current page
    $query = "SELECT s.*, 
              u.name as created_by_name,
              p.project_name,
              c.name as customer_name
              " . $baseQuery . "
              ORDER BY s.log_date DESC, s.created_at DESC 
              LIMIT $offset, $perPage";
              
    $result = $conn->query($query);
    
    $logs = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    
    return [
        'logs' => $logs,
        'total' => $totalCount,
        'pages' => ceil($totalCount / $perPage)
    ];
}

function getSeoLog($id) {
    $conn = getDbConnection();
    $id = (int)$id;
    
    $query = "SELECT s.*, 
              u.name as created_by_name 
              FROM seo_logs s 
              LEFT JOIN users u ON s.created_by = u.id 
              WHERE s.id = $id";
              
    $result = $conn->query($query);
    
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}

function createSeoLog($data) {
    $conn = getDbConnection();
    
    $project_id = (int)$data['project_id'];
    $log_details = $conn->real_escape_string($data['log_details']);
    $log_date = $conn->real_escape_string($data['log_date'] ?? date('Y-m-d'));
    $log_type = $conn->real_escape_string($data['log_type']);
    $image_path = isset($data['image_path']) ? $conn->real_escape_string($data['image_path']) : null;
    $created_by = (int)$_SESSION['user_id'];
    
    $query = "INSERT INTO seo_logs (
                project_id, log_details, log_date, log_type, 
                image_path, created_by
              ) VALUES (
                $project_id, '$log_details', '$log_date', '$log_type',
                " . ($image_path ? "'$image_path'" : "NULL") . ", $created_by
              )";
              
    if ($conn->query($query)) {
        return ['success' => true, 'id' => $conn->insert_id];
    }
    
    return ['success' => false, 'message' => 'Failed to create SEO log'];
}

function updateSeoLog($id, $data) {
    $conn = getDbConnection();
    
    $id = (int)$id;
    $log_details = $conn->real_escape_string($data['log_details']);
    $log_date = $conn->real_escape_string($data['log_date']);
    $log_type = $conn->real_escape_string($data['log_type']);
    
    $query = "UPDATE seo_logs SET 
              log_details = '$log_details',
              log_date = '$log_date',
              log_type = '$log_type'";
              
    if (isset($data['image_path'])) {
        $image_path = $conn->real_escape_string($data['image_path']);
        $query .= ", image_path = '$image_path'";
    }
    
    $query .= " WHERE id = $id";
    
    if ($conn->query($query)) {
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Failed to update SEO log'];
}

function deleteSeoLog($id) {
    $conn = getDbConnection();
    $id = (int)$id;
    
    // Get the image path before deleting
    $query = "SELECT image_path FROM seo_logs WHERE id = $id";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $log = $result->fetch_assoc();
        if ($log['image_path'] && file_exists($log['image_path'])) {
            unlink($log['image_path']);
        }
    }
    
    if ($conn->query("DELETE FROM seo_logs WHERE id = $id")) {
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Failed to delete SEO log'];
}

function uploadSeoLogImage($file) {
    $targetDir = __DIR__ . '/../uploads/seo_logs/';
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = uniqid() . '_' . basename($file['name']);
    $targetPath = $targetDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/seo_logs/' . $fileName;
    }
    
    return false;
}

function getLogTypeOptions() {
    return [
        'Technical' => 'Technical',
        'On-Page SEO' => 'On-Page SEO',
        'Off-Page SEO' => 'Off-Page SEO',
        'Content' => 'Content',
        'Analytics' => 'Analytics',
        'Other' => 'Other'
    ];
}

function getLogTypeClass($type) {
    $classes = [
        'Technical' => 'primary',
        'On-Page SEO' => 'success',
        'Off-Page SEO' => 'info',
        'Content' => 'warning',
        'Analytics' => 'danger',
        'Other' => 'secondary'
    ];
    
    return $classes[$type] ?? 'secondary';
} 