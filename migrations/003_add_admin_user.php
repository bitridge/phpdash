<?php
return [
    'up' => "INSERT INTO users (email, password, role, name) VALUES 
        ('admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin User')",
    'down' => "DELETE FROM users WHERE email = 'admin@example.com'"
]; 