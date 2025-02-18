<?php
require_once __DIR__ . '/db.php';

class Settings {
    private static $instance = null;
    private $settings = [];
    private $conn;

    private function __construct() {
        $this->conn = getDbConnection();
        $this->loadSettings();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadSettings() {
        $query = "SELECT setting_key, setting_value FROM settings";
        $result = $this->conn->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }

    public function get($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    public function set($key, $value, $group = 'general') {
        $key = $this->conn->real_escape_string($key);
        $value = $this->conn->real_escape_string($value);
        $group = $this->conn->real_escape_string($group);

        $query = "INSERT INTO settings (setting_key, setting_value, setting_group) 
                  VALUES ('$key', '$value', '$group')
                  ON DUPLICATE KEY UPDATE 
                  setting_value = VALUES(setting_value),
                  setting_group = VALUES(setting_group)";

        if ($this->conn->query($query)) {
            $this->settings[$key] = $value;
            return true;
        }
        return false;
    }

    public function getByGroup($group) {
        $group = $this->conn->real_escape_string($group);
        $query = "SELECT setting_key, setting_value FROM settings WHERE setting_group = '$group'";
        $result = $this->conn->query($query);
        
        $settings = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        return $settings;
    }

    public function uploadLogo($file) {
        $targetDir = __DIR__ . '/../uploads/settings/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = 'app_logo_' . uniqid() . '_' . basename($file['name']);
        $targetPath = $targetDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Delete old logo if exists
            $oldLogo = $this->get('app_logo');
            if ($oldLogo && file_exists(__DIR__ . '/../' . $oldLogo)) {
                unlink(__DIR__ . '/../' . $oldLogo);
            }
            
            return 'uploads/settings/' . $fileName;
        }
        
        return false;
    }

    public function createDatabaseBackup() {
        $backupDir = __DIR__ . '/../uploads/backups/';
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $tables = [];
        $result = $this->conn->query('SHOW TABLES');
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }

        $backup = "";
        
        // Add SQL header with timestamp
        $backup .= "-- Database Backup\n";
        $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $backup .= "-- Server version: " . $this->conn->server_info . "\n\n";
        
        // Add SET statements for proper handling
        $backup .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $backup .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $backup .= "SET time_zone = '+00:00';\n\n";

        foreach ($tables as $table) {
            // Get create table statement
            $result = $this->conn->query('SHOW CREATE TABLE ' . $table);
            $row = $result->fetch_row();
            $backup .= "\n\n" . $row[1] . ";\n\n";
            
            // Get table data
            $result = $this->conn->query('SELECT * FROM ' . $table);
            while ($row = $result->fetch_assoc()) {
                $backup .= "INSERT INTO `$table` VALUES (";
                $values = [];
                foreach ($row as $value) {
                    $values[] = $value === null ? 'NULL' : "'" . $this->conn->real_escape_string($value) . "'";
                }
                $backup .= implode(', ', $values);
                $backup .= ");\n";
            }
            $backup .= "\n";
        }

        // Add footer
        $backup .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
        
        // Create backup file
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backupPath = $backupDir . $filename;
        file_put_contents($backupPath, $backup);

        // Create ZIP archive
        $zipFilename = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = $backupDir . $zipFilename;
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($backupPath, $filename);
            $zip->close();
            
            // Delete the SQL file, keeping only the ZIP
            unlink($backupPath);
            
            return $zipPath;
        }
        
        return false;
    }
} 