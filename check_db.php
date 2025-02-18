<?php
require_once 'config.php';
require_once 'includes/db.php';

$conn = getDbConnection();

// Check users table
$result = $conn->query("SHOW TABLES LIKE 'users'");
echo "Users table exists: " . ($result->num_rows > 0 ? "Yes" : "No") . "\n";

// Check admin user
$result = $conn->query("SELECT * FROM users WHERE email = 'admin@example.com'");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "Admin user exists: Yes\n";
    echo "Admin details:\n";
    echo "- Name: " . $user['name'] . "\n";
    echo "- Email: " . $user['email'] . "\n";
    echo "- Role: " . $user['role'] . "\n";
    echo "- Password hash length: " . strlen($user['password']) . "\n";
} else {
    echo "Admin user exists: No\n";
}

// Check if we can verify the admin password
$adminPassword = 'admin123';
$result = $conn->query("SELECT password FROM users WHERE email = 'admin@example.com'");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "\nPassword verification test:\n";
    echo "- Stored hash: " . $user['password'] . "\n";
    echo "- Verification result: " . (password_verify($adminPassword, $user['password']) ? "Success" : "Failed") . "\n";
} 