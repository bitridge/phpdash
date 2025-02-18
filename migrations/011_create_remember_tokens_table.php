<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS remember_tokens (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        expiry DATETIME NOT NULL,
        used TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_token (user_id, token),
        INDEX idx_expiry (expiry)
    )",
    'down' => "DROP TABLE IF EXISTS remember_tokens"
]; 