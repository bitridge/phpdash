<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS projects (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_id INT NOT NULL,
        project_name VARCHAR(255) NOT NULL,
        project_url VARCHAR(255),
        project_details TEXT,
        logo_path VARCHAR(255),
        status ENUM('active', 'paused', 'completed') NOT NULL DEFAULT 'active',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )",
    'down' => "DROP TABLE IF EXISTS projects"
]; 