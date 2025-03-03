<?php
return [
    'up' => "ALTER TABLE seo_logs MODIFY COLUMN log_details LONGTEXT NOT NULL",
    'down' => "ALTER TABLE seo_logs MODIFY COLUMN log_details TEXT NOT NULL"
]; 