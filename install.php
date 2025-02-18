<?php
session_start();

// Handle test connection request
if (isset($_POST['test_connection'])) {
    $result = testDatabaseConnection(
        $_POST['db_host'],
        $_POST['db_user'],
        $_POST['db_pass'],
        $_POST['db_name']
    );
    
    if ($result['success']) {
        echo "Connection successful! Database '" . htmlspecialchars($_POST['db_name']) . "' is accessible.";
    } else {
        echo "Connection failed: " . htmlspecialchars($result['message']);
    }
    exit;
}

// Check if config.php exists and installation is already completed
if (file_exists('config.php') && !isset($_GET['force'])) {
    die('Installation has already been completed. Delete config.php first if you want to reinstall.');
}

// Function to check system requirements
function checkRequirements() {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'MySQLi Extension' => extension_loaded('mysqli'),
        'PDO Extension' => extension_loaded('pdo'),
        'GD Extension' => extension_loaded('gd'),
        'Uploads Directory Writable' => is_writable('uploads') || @mkdir('uploads', 0777, true),
        'Config File Writable' => !file_exists('config.php') || is_writable('config.php'),
        'cURL Extension' => extension_loaded('curl'),
        'JSON Extension' => extension_loaded('json'),
        'SSL Support' => extension_loaded('openssl'),
        'Zip Extension' => extension_loaded('zip')
    ];

    // Check directory permissions
    $directories = [
        'uploads/logos',
        'uploads/reports',
        'uploads/reports/images',
        'uploads/settings'
    ];

    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            @mkdir($dir, 0777, true);
        }
        $requirements[$dir . ' Directory Writable'] = is_writable($dir);
    }

    return $requirements;
}

// Function to test database connection
function testDatabaseConnection($host, $user, $pass, $name) {
    try {
        $conn = new mysqli($host, $user, $pass);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Try to create database if it doesn't exist
        $conn->query("CREATE DATABASE IF NOT EXISTS `" . $conn->real_escape_string($name) . "`");
        
        // Try to select the database
        if (!$conn->select_db($name)) {
            throw new Exception("Database selection failed");
        }

        $conn->close();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Function to create config file
function createConfigFile($data) {
    $config = "<?php\n";
    $config .= "// Database configuration\n";
    $config .= "define('DB_HOST', '" . addslashes($data['db_host']) . "');\n";
    $config .= "define('DB_NAME', '" . addslashes($data['db_name']) . "');\n";
    $config .= "define('DB_USER', '" . addslashes($data['db_user']) . "');\n";
    $config .= "define('DB_PASS', '" . addslashes($data['db_pass']) . "');\n\n";
    $config .= "// Session configuration\n";
    $config .= "session_start();\n\n";
    $config .= "// Error reporting (set to 0 in production)\n";
    $config .= "error_reporting(0);\n";
    $config .= "ini_set('display_errors', 0);\n\n";
    $config .= "// Timezone\n";
    $config .= "date_default_timezone_set('" . date_default_timezone_get() . "');\n";

    return file_put_contents('config.php', $config) !== false;
}

// Function to create admin user
function createAdminUser($email, $password, $name) {
    require_once 'config.php';
    require_once 'includes/db.php';
    require_once 'includes/Migration.php';

    $conn = getDbConnection();
    $migration = new Migration($conn);

    // Run all migrations
    $migrationFiles = glob('migrations/*.php');
    sort($migrationFiles);
    
    foreach ($migrationFiles as $file) {
        $migrationData = require $file;
        if (!$conn->query($migrationData['up'])) {
            throw new Exception("Migration failed: " . $conn->error);
        }
    }

    // Create admin user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $email = $conn->real_escape_string($email);
    $name = $conn->real_escape_string($name);
    
    $query = "INSERT INTO users (email, password, name, role) 
              VALUES ('$email', '$hashedPassword', '$name', 'admin')";
              
    if (!$conn->query($query)) {
        throw new Exception("Failed to create admin user: " . $conn->error);
    }

    return true;
}

// Handle form submission
$error = '';
$success = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_db'])) {
        $result = testDatabaseConnection(
            $_POST['db_host'],
            $_POST['db_user'],
            $_POST['db_pass'],
            $_POST['db_name']
        );

        if ($result['success']) {
            $_SESSION['db_config'] = [
                'db_host' => $_POST['db_host'],
                'db_user' => $_POST['db_user'],
                'db_pass' => $_POST['db_pass'],
                'db_name' => $_POST['db_name']
            ];
            header('Location: install.php?step=2');
            exit;
        } else {
            $error = $result['message'];
        }
    } elseif (isset($_POST['create_admin'])) {
        try {
            if (empty($_SESSION['db_config'])) {
                throw new Exception("Database configuration not found");
            }

            // Create config file
            if (!createConfigFile($_SESSION['db_config'])) {
                throw new Exception("Failed to create config file");
            }

            // Create admin user
            createAdminUser(
                $_POST['admin_email'],
                $_POST['admin_password'],
                $_POST['admin_name']
            );

            // Clear session
            session_destroy();

            // Delete install file
            @unlink(__FILE__);

            // Redirect to login
            header('Location: login.php?installed=1');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Check requirements
$requirements = checkRequirements();
$requirementsMet = !in_array(false, $requirements);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Dashboard Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .install-container { max-width: 800px; margin: 2rem auto; }
        .requirement-item { margin-bottom: 0.5rem; }
        .step-indicator { margin-bottom: 2rem; }
        .step { padding: 0.5rem 1rem; background: #e9ecef; border-radius: 4px; margin: 0 0.25rem; }
        .step.active { background: #0d6efd; color: white; }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="card shadow">
            <div class="card-body">
                <h1 class="text-center mb-4">SEO Dashboard Installation</h1>

                <!-- Step Indicator -->
                <div class="d-flex justify-content-center step-indicator">
                    <span class="step <?php echo $step === 1 ? 'active' : ''; ?>">1. Requirements</span>
                    <span class="step <?php echo $step === 2 ? 'active' : ''; ?>">2. Admin Setup</span>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($step === 1): ?>
                    <!-- Step 1: Requirements and Database Setup -->
                    <h3 class="mb-3">System Requirements</h3>
                    
                    <?php foreach ($requirements as $requirement => $met): ?>
                        <div class="requirement-item">
                            <i class="bi bi-<?php echo $met ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i>
                            <?php echo htmlspecialchars($requirement); ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($requirementsMet): ?>
                        <h3 class="mt-4 mb-3">Database Configuration</h3>
                        <form method="POST" action="install.php" id="db-form">
                            <input type="hidden" name="check_db" value="1">
                            
                            <div class="mb-3">
                                <label for="db_host" class="form-label">Database Host</label>
                                <input type="text" class="form-control" id="db_host" name="db_host" 
                                       value="localhost" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="db_name" class="form-label">Database Name</label>
                                <input type="text" class="form-control" id="db_name" name="db_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="db_user" class="form-label">Database User</label>
                                <input type="text" class="form-control" id="db_user" name="db_user" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="db_pass" class="form-label">Database Password</label>
                                <input type="password" class="form-control" id="db_pass" name="db_pass" required>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-secondary" id="test-connection">Test Connection</button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Continue</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-danger mt-4">
                            Please fix the requirements before continuing.
                        </div>
                    <?php endif; ?>

                <?php elseif ($step === 2): ?>
                    <!-- Step 2: Admin User Setup -->
                    <h3 class="mb-3">Create Admin User</h3>
                    <form method="POST" action="install.php?step=2">
                        <input type="hidden" name="create_admin" value="1">
                        
                        <div class="mb-3">
                            <label for="admin_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="admin_name" name="admin_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="admin_password" 
                                   name="admin_password" required minlength="8">
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Complete Installation</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('test-connection').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('db-form'));
            formData.append('test_connection', '1');
            
            // Disable the test button and show loading state
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Testing...';
            
            fetch('install.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                // Re-enable the test button
                this.disabled = false;
                this.innerHTML = 'Test Connection';
                
                // Show the result
                alert(result);
            })
            .catch(error => {
                // Re-enable the test button
                this.disabled = false;
                this.innerHTML = 'Test Connection';
                
                alert('Error testing connection: ' + error.message);
            });
        });
    </script>
</body>
</html> 