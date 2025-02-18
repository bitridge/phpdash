<?php
require_once __DIR__ . '/db.php';

function getUsers($page = 1, $perPage = 10) {
    $conn = getDbConnection();
    
    // Calculate offset
    $offset = ($page - 1) * $perPage;
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM users";
    $countResult = $conn->query($countQuery);
    $totalCount = $countResult->fetch_assoc()['total'];
    
    // Get users for current page
    $query = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT $offset, $perPage";
    $result = $conn->query($query);
    
    $users = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    return [
        'users' => $users,
        'total' => $totalCount,
        'pages' => ceil($totalCount / $perPage)
    ];
}

function getUser($id) {
    $conn = getDbConnection();
    $id = (int)$id;
    
    $query = "SELECT id, name, email, role FROM users WHERE id = $id";
    $result = $conn->query($query);
    
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}

function updateUser($id, $data) {
    $conn = getDbConnection();
    
    $id = (int)$id;
    $name = $conn->real_escape_string($data['name']);
    $email = $conn->real_escape_string($data['email']);
    $role = $conn->real_escape_string($data['role']);
    
    // Check if email exists for other users
    $checkQuery = "SELECT id FROM users WHERE email = '$email' AND id != $id";
    $result = $conn->query($checkQuery);
    if ($result && $result->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    $query = "UPDATE users SET name = '$name', email = '$email', role = '$role'";
    
    // Update password if provided
    if (!empty($data['password'])) {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $query .= ", password = '$hashedPassword'";
    }
    
    $query .= " WHERE id = $id";
    
    if ($conn->query($query)) {
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Update failed'];
}

function deleteUser($id) {
    $conn = getDbConnection();
    $id = (int)$id;
    
    // Don't allow deleting the last admin
    $adminQuery = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'";
    $result = $conn->query($adminQuery);
    $adminCount = $result->fetch_assoc()['admin_count'];
    
    $userQuery = "SELECT role FROM users WHERE id = $id";
    $result = $conn->query($userQuery);
    $user = $result->fetch_assoc();
    
    if ($adminCount <= 1 && $user['role'] === 'admin') {
        return ['success' => false, 'message' => 'Cannot delete the last admin user'];
    }
    
    // Check if user has any customers
    $checkQuery = "SELECT COUNT(*) as count FROM customers WHERE created_by = $id";
    $result = $conn->query($checkQuery);
    if ($result && $result->fetch_assoc()['count'] > 0) {
        return ['success' => false, 'message' => 'Cannot delete user with associated customers'];
    }
    
    if ($conn->query("DELETE FROM users WHERE id = $id")) {
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Delete failed'];
}

function createUser($data) {
    $conn = getDbConnection();
    
    $name = $conn->real_escape_string($data['name']);
    $email = $conn->real_escape_string($data['email']);
    $role = $conn->real_escape_string($data['role']);
    $password = $data['password'];
    
    // Check if email exists
    $checkQuery = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($checkQuery);
    if ($result && $result->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $query = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashedPassword', '$role')";
    
    if ($conn->query($query)) {
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'Creation failed'];
} 