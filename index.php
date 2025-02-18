<?php
// Check if application is installed
if (!file_exists('config.php')) {
    if (file_exists('install.php')) {
        header('Location: install.php');
        exit;
    } else {
        die('Application is not installed and installer is missing.');
    }
}

require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/customer.php';
require_once 'includes/project.php';
require_once 'includes/seo_log.php';

// Require login
requireLogin();

// Set page title
$pageTitle = 'Dashboard';

// Get database connection
$conn = getDbConnection();

// Get statistics
$stats = [];

// Customer count
$result = $conn->query("SELECT COUNT(*) as count FROM customers");
$stats['customers'] = $result ? $result->fetch_assoc()['count'] : 0;

// Project count
$result = $conn->query("SELECT COUNT(*) as count FROM projects");
$stats['projects'] = $result ? $result->fetch_assoc()['count'] : 0;

// SEO Logs count
$result = $conn->query("SELECT COUNT(*) as count FROM seo_logs");
$stats['seo_logs'] = $result ? $result->fetch_assoc()['count'] : 0;

// User count (for admins)
if (isAdmin()) {
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $stats['users'] = $result ? $result->fetch_assoc()['count'] : 0;
}

// Recent SEO Logs
$result = $conn->query("
    SELECT sl.*, p.project_name, c.name as customer_name 
    FROM seo_logs sl
    JOIN projects p ON sl.project_id = p.id
    JOIN customers c ON p.customer_id = c.id
    ORDER BY sl.created_at DESC
    LIMIT 5
");
$recentLogs = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentLogs[] = $row;
    }
}

// Recent Projects
$result = $conn->query("
    SELECT p.*, c.name as customer_name 
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 5
");
$recentProjects = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentProjects[] = $row;
    }
}

// Include header
include 'templates/header.php';
?>

<div class="row">
    <?php if (isAdmin()): ?>
    <!-- Admin Dashboard -->
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-building display-4 text-primary mb-2"></i>
                <h5 class="card-title">Customers</h5>
                <p class="card-text">Manage your customers and their projects</p>
                <a href="customers.php" class="btn btn-primary">View Customers</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-kanban display-4 text-success mb-2"></i>
                <h5 class="card-title">Projects</h5>
                <p class="card-text">View and manage all SEO projects</p>
                <a href="projects.php" class="btn btn-success">View Projects</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-journal-text display-4 text-info mb-2"></i>
                <h5 class="card-title">SEO Logs</h5>
                <p class="card-text">Track all SEO activities and progress</p>
                <a href="seo_logs.php" class="btn btn-info">View Logs</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-people display-4 text-warning mb-2"></i>
                <h5 class="card-title">Users</h5>
                <p class="card-text">Manage SEO providers and admins</p>
                <a href="users.php" class="btn btn-warning">Manage Users</a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- SEO Provider Dashboard -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-kanban display-4 text-primary mb-2"></i>
                <h5 class="card-title">My Projects</h5>
                <p class="card-text">View and manage your assigned projects</p>
                <a href="projects.php" class="btn btn-primary">View Projects</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-journal-text display-4 text-success mb-2"></i>
                <h5 class="card-title">SEO Logs</h5>
                <p class="card-text">Track your SEO activities and progress</p>
                <a href="seo_logs.php" class="btn btn-success">View Logs</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?> 