<?php
class Migration {
    private $conn;
    private $migrationsTable = 'migrations';
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->createMigrationsTable();
    }
    
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->conn->query($sql);
    }
    
    public function getExecutedMigrations() {
        $sql = "SELECT migration FROM {$this->migrationsTable}";
        $result = $this->conn->query($sql);
        
        $migrations = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $migrations[] = $row['migration'];
            }
        }
        
        return $migrations;
    }
    
    public function recordMigration($migrationName, $batch) {
        $migrationName = $this->conn->real_escape_string($migrationName);
        $batch = (int)$batch;
        
        $sql = "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES ('$migrationName', $batch)";
        return $this->conn->query($sql);
    }
    
    public function getLastBatchNumber() {
        $sql = "SELECT MAX(batch) as last_batch FROM {$this->migrationsTable}";
        $result = $this->conn->query($sql);
        
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['last_batch'];
        }
        
        return 0;
    }
    
    public function removeMigration($migrationName) {
        $migrationName = $this->conn->real_escape_string($migrationName);
        $sql = "DELETE FROM {$this->migrationsTable} WHERE migration = '$migrationName'";
        return $this->conn->query($sql);
    }
} 