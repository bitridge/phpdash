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
} 