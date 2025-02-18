<?php
require_once __DIR__ . '/db.php';

function createCustomer($data) {
    $conn = getDbConnection();
    
    $name = $conn->real_escape_string($data['name']);
    $company_name = $conn->real_escape_string($data['company_name']);
    $email = $conn->real_escape_string($data['email']);
    $phone = $conn->real_escape_string($data['phone']);
    $website_url = $conn->real_escape_string($data['website_url']);
    $logo_path = isset($data['logo_path']) ? $conn->real_escape_string($data['logo_path']) : null;
    $created_by = (int)$_SESSION['user_id'];
    
    $query = "INSERT INTO customers (name, company_name, email, phone, website_url, logo_path, created_by) 
              VALUES ('$name', '$company_name', '$email', '$phone', '$website_url', " . 
              ($logo_path ? "'$logo_path'" : "NULL") . ", $created_by)";
              
    return $conn->query($query);
}

function updateCustomer($id, $data) {
    $conn = getDbConnection();
    
    $id = (int)$id;
    $name = $conn->real_escape_string($data['name']);
    $company_name = $conn->real_escape_string($data['company_name']);
    $email = $conn->real_escape_string($data['email']);
    $phone = $conn->real_escape_string($data['phone']);
    $website_url = $conn->real_escape_string($data['website_url']);
    
    $query = "UPDATE customers SET 
              name = '$name',
              company_name = '$company_name',
              email = '$email',
              phone = '$phone',
              website_url = '$website_url'";
              
    if (isset($data['logo_path'])) {
        $logo_path = $conn->real_escape_string($data['logo_path']);
        $query .= ", logo_path = '$logo_path'";
    }
    
    $query .= " WHERE id = $id";
    
    return $conn->query($query);
}

function deleteCustomer($id) {
    $conn = getDbConnection();
    $id = (int)$id;
    
    // Get the logo path before deleting
    $query = "SELECT logo_path FROM customers WHERE id = $id";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $customer = $result->fetch_assoc();
        if ($customer['logo_path'] && file_exists($customer['logo_path'])) {
            unlink($customer['logo_path']);
        }
    }
    
    $query = "DELETE FROM customers WHERE id = $id";
    return $conn->query($query);
}

function getCustomer($id) {
    $conn = getDbConnection();
    $id = (int)$id;
    
    $query = "SELECT * FROM customers WHERE id = $id";
    $result = $conn->query($query);
    
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}

function getCustomers($page = 1, $perPage = 10) {
    $conn = getDbConnection();
    
    // Calculate offset
    $offset = ($page - 1) * $perPage;
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM customers";
    $countResult = $conn->query($countQuery);
    $totalCount = $countResult->fetch_assoc()['total'];
    
    // Get customers for current page
    $query = "SELECT c.*, u.name as created_by_name 
              FROM customers c 
              LEFT JOIN users u ON c.created_by = u.id 
              ORDER BY c.created_at DESC 
              LIMIT $offset, $perPage";
              
    $result = $conn->query($query);
    
    $customers = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
    }
    
    return [
        'customers' => $customers,
        'total' => $totalCount,
        'pages' => ceil($totalCount / $perPage)
    ];
}

function uploadCustomerLogo($file) {
    $targetDir = __DIR__ . '/../uploads/logos/';
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = uniqid() . '_' . basename($file['name']);
    $targetPath = $targetDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/logos/' . $fileName;
    }
    
    return false;
} 