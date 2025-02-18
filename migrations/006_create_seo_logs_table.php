<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS seo_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        project_id INT NOT NULL,
        log_details TEXT NOT NULL,
        log_date DATE DEFAULT (CURRENT_DATE),
        log_type ENUM('Technical', 'On-Page SEO', 'Off-Page SEO', 'Content', 'Analytics', 'Other') NOT NULL,
        image_path VARCHAR(255),
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )",
    'down' => "DROP TABLE IF EXISTS seo_logs"
]; 