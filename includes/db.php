<?php
require_once __DIR__ . '/../config.php';

function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("Database connection failed. Please check your database credentials.");
            }
            
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection failed. Please check your database credentials.");
        }
    }
    
    return $conn;
} 