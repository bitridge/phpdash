<?php
require_once 'db.php';

function generateResetToken($email) {
    $conn = getDbConnection();
    $email = $conn->real_escape_string($email);
    
    // Check if user exists
    $query = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows === 1) {
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database
        $query = "INSERT INTO password_resets (email, token, expiry) 
                  VALUES ('$email', '$token', '$expiry')
                  ON DUPLICATE KEY UPDATE token = VALUES(token), expiry = VALUES(expiry)";
                  
        if ($conn->query($query)) {
            return $token;
        }
    }
    
    return false;
}

function validateResetToken($token) {
    $conn = getDbConnection();
    $token = $conn->real_escape_string($token);
    
    $query = "SELECT email FROM password_resets 
              WHERE token = '$token' 
              AND expiry > NOW() 
              AND used = 0";
              
    $result = $conn->query($query);
    
    return ($result && $result->num_rows === 1) ? $result->fetch_assoc()['email'] : false;
}

function resetPassword($token, $newPassword) {
    $conn = getDbConnection();
    $token = $conn->real_escape_string($token);
    
    // Get email from valid token
    $email = validateResetToken($token);
    if (!$email) {
        return false;
    }
    
    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $query = "UPDATE users SET password = '$hashedPassword' WHERE email = '$email'";
    
    if ($conn->query($query)) {
        // Mark token as used
        $conn->query("UPDATE password_resets SET used = 1 WHERE token = '$token'");
        return true;
    }
    
    return false;
}

function sendResetEmail($email, $token) {
    $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . '/reset_password.php?token=' . $token;
    $to = $email;
    $subject = 'Password Reset Request';
    $message = "Hello,\n\n";
    $message .= "You have requested to reset your password. Click the link below to reset it:\n\n";
    $message .= $resetLink . "\n\n";
    $message .= "This link will expire in 1 hour.\n\n";
    $message .= "If you did not request this reset, please ignore this email.\n\n";
    $message .= "Best regards,\nSEO Dashboard Team";
    
    $headers = 'From: noreply@' . $_SERVER['HTTP_HOST'] . "\r\n" .
               'Reply-To: noreply@' . $_SERVER['HTTP_HOST'] . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    return mail($to, $subject, $message, $headers);
} 