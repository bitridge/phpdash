<?php
require_once __DIR__ . '/db.php';

function getProjects($page = 1, $perPage = 10, $customerId = null) {
    $conn = getDbConnection();
    
    // Calculate offset
    $offset = ($page - 1) * $perPage;
    
    // Base query
    $baseQuery = "FROM projects p 
                  LEFT JOIN customers c ON p.customer_id = c.id 
                  LEFT JOIN users u ON p.created_by = u.id";
    
    // Add customer filter if specified
    $whereClause = $customerId ? " WHERE p.customer_id = " . (int)$customerId : "";
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
    $countResult = $conn->query($countQuery);
    $totalCount = $countResult->fetch_assoc()['total'];
    
    // Get projects for current page
    $query = "SELECT p.*, 
              c.name as customer_name, 
              c.company_name,
              u.name as created_by_name 
              " . $baseQuery . $whereClause . "
              ORDER BY p.created_at DESC 
              LIMIT $offset, $perPage";
              
    $result = $conn->query($query);
    
    $projects = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
    }
    
    return [
        'projects' => $projects,
        'total' => $totalCount,
        'pages' => ceil($totalCount / $perPage)
    ];
}

function getProject($id) {
    $conn = getDbConnection();
    $id = (int)$id;
    
    $query = "SELECT p.*, 
              c.name as customer_name, 
              c.company_name,
              u.name as created_by_name 
              FROM projects p 
              LEFT JOIN customers c ON p.customer_id = c.id 
              LEFT JOIN users u ON p.created_by = u.id 
              WHERE p.id = $id";
              
    $result = $conn->query($query);
    
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}

function createProject($data) {
    $conn = getDbConnection();
    
    $customer_id = (int)$data['customer_id'];
    $project_name = $conn->real_escape_string($data['project_name']);
    $project_url = $conn->real_escape_string($data['project_url'] ?? '');
    $project_details = $conn->real_escape_string($data['project_details'] ?? '');
    $status = $conn->real_escape_string($data['status'] ?? 'active');
    $logo_path = isset($data['logo_path']) ? $conn->real_escape_string($data['logo_path']) : null;
    $created_by = (int)$_SESSION['user_id'];
    
    $query = "INSERT INTO projects (
                customer_id, project_name, project_url, project_details, 
                status, logo_path, created_by
              ) VALUES (
                $customer_id, '$project_name', '$project_url', '$project_details', 
                '$status', " . ($logo_path ? "'$logo_path'" : "NULL") . ", $created_by
              )";
              
    if ($conn->query($query)) {
        return ['success' => true, 'id' => $conn->insert_id];
    }
    
    return ['success' => false, 'message' => 'Failed to create project'];
}

function updateProject($id, $data) {
    $conn = getDbConnection();
    
    $id = (int)$id;
    $customer_id = (int)$data['customer_id'];
    $project_name = $conn->real_escape_string($data['project_name']);
    $project_url = $conn->real_escape_string($data['project_url'] ?? '');
    $project_details = $conn->real_escape_string($data['project_details'] ?? '');
    $status = $conn->real_escape_string($data['status'] ?? 'active');
    
    $query = "UPDATE projects SET 
              customer_id = $customer_id,
              project_name = '$project_name',
              project_url = '$project_url',
              project_details = '$project_details',
              status = '$status'";
              
    if (isset($data['logo_path'])) {
        $logo_path = $conn->real_escape_string($data['logo_path']);
        $query .= ", logo_path = '$logo_path'";
    }
    
    $query .= " WHERE id = $id";
    
    if ($conn->query($query)) {
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Failed to update project'];
}

function deleteProject($id) {
    $conn = getDbConnection();
    $id = (int)$id;
    
    // Get the logo path before deleting
    $query = "SELECT logo_path FROM projects WHERE id = $id";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $project = $result->fetch_assoc();
        if ($project['logo_path'] && file_exists($project['logo_path'])) {
            unlink($project['logo_path']);
        }
    }
    
    if ($conn->query("DELETE FROM projects WHERE id = $id")) {
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Failed to delete project'];
}

function uploadProjectLogo($file) {
    $targetDir = __DIR__ . '/../uploads/projects/';
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = uniqid() . '_' . basename($file['name']);
    $targetPath = $targetDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/projects/' . $fileName;
    }
    
    return false;
}

function getCustomerProjects($customerId) {
    return getProjects(1, PHP_INT_MAX, $customerId);
}

function getProjectsByProvider($userId, $page = 1, $perPage = 10) {
    $conn = getDbConnection();
    
    // Calculate offset
    $offset = ($page - 1) * $perPage;
    
    // Get total count
    $countQuery = "SELECT COUNT(DISTINCT p.id) as total 
                   FROM projects p 
                   JOIN customer_users cu ON p.customer_id = cu.customer_id 
                   WHERE cu.user_id = $userId";
                   
    $countResult = $conn->query($countQuery);
    $totalCount = $countResult->fetch_assoc()['total'];
    
    // Get projects
    $query = "SELECT DISTINCT p.*, c.name as customer_name, c.company_name, u.name as created_by_name 
              FROM projects p 
              JOIN customers c ON p.customer_id = c.id 
              LEFT JOIN users u ON p.created_by = u.id 
              JOIN customer_users cu ON c.id = cu.customer_id 
              WHERE cu.user_id = $userId 
              ORDER BY p.created_at DESC 
              LIMIT $offset, $perPage";
              
    $result = $conn->query($query);
    
    $projects = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
    }
    
    return [
        'projects' => $projects,
        'total' => $totalCount,
        'pages' => ceil($totalCount / $perPage)
    ];
}

// Add this function to check if a user has access to a project
function canAccessProject($projectId, $userId) {
    $conn = getDbConnection();
    
    // If user is admin, they have access to all projects
    if (isAdmin()) {
        return true;
    }
    
    $projectId = (int)$projectId;
    $userId = (int)$userId;
    
    // Check if the user is assigned to the project's customer
    $query = "SELECT 1 FROM projects p 
              JOIN customer_users cu ON p.customer_id = cu.customer_id 
              WHERE p.id = $projectId AND cu.user_id = $userId";
              
    $result = $conn->query($query);
    return $result && $result->num_rows > 0;
} 