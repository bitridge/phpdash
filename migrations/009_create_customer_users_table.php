<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS customer_users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_customer_user (customer_id, user_id)
    )",
    'down' => "DROP TABLE IF EXISTS customer_users"
]; 