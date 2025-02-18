<?php
require_once __DIR__ . '/db.php';

function loginUser($email, $password) {
    $conn = getDbConnection();
    $email = $conn->real_escape_string($email);
    
    error_log("Attempting login for email: " . $email);
    
    $query = "SELECT id, email, password, role, name FROM users WHERE email = '$email'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        error_log("User found, verifying password");
        
        if (password_verify($password, $user['password'])) {
            error_log("Password verified successfully");
            // Store user data in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            return true;
        } else {
            error_log("Password verification failed");
            error_log("Provided password: " . substr($password, 0, 3) . "***");
            error_log("Stored hash: " . $user['password']);
        }
    } else {
        error_log("No user found with email: " . $email);
    }
    return false;
}

function registerUser($email, $password, $name, $role = 'seo_provider') {
    $conn = getDbConnection();
    
    // Check if email already exists
    $email = $conn->real_escape_string($email);
    $checkQuery = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($checkQuery);
    
    if ($result && $result->num_rows > 0) {
        return false;
    }
    
    // Hash password and insert user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $name = $conn->real_escape_string($name);
    $role = $conn->real_escape_string($role);
    
    $query = "INSERT INTO users (email, password, name, role) VALUES ('$email', '$hashedPassword', '$name', '$role')";
    return $conn->query($query);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
} 