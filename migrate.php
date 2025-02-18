<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/Migration.php';

// Get database connection
$conn = getDbConnection();

// Initialize migration
$migration = new Migration($conn);

// Command line arguments
$command = $argv[1] ?? 'up';

if ($command === 'up') {
    // Get all migration files
    $migrationFiles = glob('migrations/*.php');
    sort($migrationFiles); // Sort by filename
    
    // Get already executed migrations
    $executedMigrations = $migration->getExecutedMigrations();
    
    // Get current batch number
    $batch = $migration->getLastBatchNumber() + 1;
    
    // Execute pending migrations
    $count = 0;
    foreach ($migrationFiles as $file) {
        $migrationName = basename($file);
        
        if (!in_array($migrationName, $executedMigrations)) {
            $migrationData = require $file;
            
            if ($conn->query($migrationData['up'])) {
                $migration->recordMigration($migrationName, $batch);
                echo "Migrated: $migrationName\n";
                $count++;
            } else {
                echo "Error migrating $migrationName: " . $conn->error . "\n";
                exit(1);
            }
        }
    }
    
    echo $count ? "$count migrations completed.\n" : "Nothing to migrate.\n";
} elseif ($command === 'down') {
    // Get the last executed migration
    $executedMigrations = $migration->getExecutedMigrations();
    
    if (empty($executedMigrations)) {
        echo "No migrations to rollback.\n";
        exit(0);
    }
    
    $lastMigration = end($executedMigrations);
    $migrationFile = "migrations/$lastMigration";
    
    if (file_exists($migrationFile)) {
        $migrationData = require $migrationFile;
        
        if ($conn->query($migrationData['down'])) {
            $migration->removeMigration($lastMigration);
            echo "Rolled back: $lastMigration\n";
        } else {
            echo "Error rolling back $lastMigration: " . $conn->error . "\n";
            exit(1);
        }
    } else {
        echo "Migration file not found: $lastMigration\n";
        exit(1);
    }
} else {
    echo "Invalid command. Use 'up' or 'down'.\n";
    exit(1);
}

$conn->close(); 