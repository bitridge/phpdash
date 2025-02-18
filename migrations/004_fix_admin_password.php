<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

return [
    'up' => "UPDATE users SET password = '$hash' WHERE email = 'admin@example.com'",
    'down' => "UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'admin@example.com'"
]; 