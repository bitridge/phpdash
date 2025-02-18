<?php
require_once __DIR__ . '/db.php';

function loginUser($email, $password, $remember = false) {
    // Set session parameters before session_start()
    if ($remember) {
        $lifetime = 30 * 24 * 60 * 60; // 30 days in seconds
        session_set_cookie_params($lifetime);
    }
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
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
            
            // Handle remember me functionality
            if ($remember) {
                // Generate and store remember token
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', time() + $lifetime);
                
                $token_hash = password_hash($token, PASSWORD_DEFAULT);
                $user_id = $user['id'];
                
                // Store token in database
                $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expiry) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $token_hash, $expiry);
                $stmt->execute();
                
                // Set remember cookies
                setcookie('remember_token', $token, time() + $lifetime, '/', '', true, true);
                setcookie('remember_user', $user['id'], time() + $lifetime, '/', '', true, true);
            }
            
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

function checkRememberToken() {
    if (!isset($_COOKIE['remember_token']) || !isset($_COOKIE['remember_user'])) {
        return false;
    }
    
    $conn = getDbConnection();
    $user_id = (int)$_COOKIE['remember_user'];
    $token = $_COOKIE['remember_token'];
    
    // Get the stored token
    $query = "SELECT token FROM remember_tokens 
              WHERE user_id = ? AND expiry > NOW() AND used = 0 
              ORDER BY created_at DESC LIMIT 1";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        if (password_verify($token, $row['token'])) {
            // Get user data
            $query = "SELECT id, email, role, name FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_result = $stmt->get_result();
            
            if ($user_result && $user = $user_result->fetch_assoc()) {
                // Store user data in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                return true;
            }
        }
    }
    
    // If we get here, the token is invalid or expired
    clearRememberToken();
    return false;
}

function clearRememberToken() {
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
        $conn = getDbConnection();
        $user_id = (int)$_COOKIE['remember_user'];
        
        // Mark all tokens for this user as used
        $query = "UPDATE remember_tokens SET used = 1 WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Remove cookies
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('remember_user', '', time() - 3600, '/');
    }
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
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Check remember token if session is not active
    return checkRememberToken();
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
    clearRememberToken();
    session_destroy();
    header('Location: login.php');
    exit();
} 