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

// Get current month SEO logs count
$currentMonth = date('Y-m');
$result = $conn->query("
    SELECT COUNT(*) as count 
    FROM seo_logs 
    WHERE DATE_FORMAT(log_date, '%Y-%m') = '$currentMonth'
");
$stats['current_month_logs'] = $result ? $result->fetch_assoc()['count'] : 0;

// Get SEO log type distribution for current month
$result = $conn->query("
    SELECT log_type, COUNT(*) as count 
    FROM seo_logs 
    WHERE DATE_FORMAT(log_date, '%Y-%m') = '$currentMonth'
    GROUP BY log_type
");
$logTypeStats = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logTypeStats[$row['log_type']] = $row['count'];
    }
}

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

// Recent SEO Logs with more details
$result = $conn->query("
    SELECT sl.*, p.project_name, c.name as customer_name, u.name as user_name,
           c.company_name, p.project_url
    FROM seo_logs sl
    JOIN projects p ON sl.project_id = p.id
    JOIN customers c ON p.customer_id = c.id
    JOIN users u ON sl.created_by = u.id
    WHERE DATE_FORMAT(sl.log_date, '%Y-%m') = '$currentMonth'
    ORDER BY sl.log_date DESC, sl.created_at DESC
    LIMIT 10
");
$recentLogs = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentLogs[] = $row;
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

<!-- After the first row of stat cards, add the new sections -->
<div class="row mt-4">
    <!-- Current Month Statistics -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calendar-check me-2"></i>
                    <?php echo date('F Y'); ?> Statistics
                </h5>
            </div>
            <div class="card-body">
                <h3 class="text-center mb-4">
                    <?php echo $stats['current_month_logs']; ?> 
                    <small class="text-muted">Total Activities</small>
                </h3>
                
                <h6 class="border-bottom pb-2 mb-3">Activity Distribution</h6>
                <?php foreach (getLogTypeOptions() as $type): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><?php echo $type; ?></span>
                        <span class="badge bg-<?php echo getLogTypeClass($type); ?>">
                            <?php echo $logTypeStats[$type] ?? 0; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent SEO Activities -->
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-activity me-2"></i>
                    Recent SEO Activities
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">
                                        <a href="project-details.php?id=<?php echo $log['project_id']; ?>" 
                                           class="text-decoration-none">
                                            <?php echo htmlspecialchars($log['project_name']); ?>
                                        </a>
                                        <span class="badge bg-<?php echo getLogTypeClass($log['log_type']); ?> ms-2">
                                            <?php echo htmlspecialchars($log['log_type']); ?>
                                        </span>
                                    </h6>
                                    <p class="mb-1 text-muted small">
                                        <i class="bi bi-building me-1"></i>
                                        <?php echo htmlspecialchars($log['customer_name']); ?> 
                                        (<?php echo htmlspecialchars($log['company_name']); ?>)
                                    </p>
                                    <p class="mb-1 small">
                                        <?php 
                                        $preview = strip_tags($log['log_details']);
                                        echo htmlspecialchars(strlen($preview) > 100 ? 
                                            substr($preview, 0, 100) . '...' : 
                                            $preview);
                                        ?>
                                    </p>
                                </div>
                                <div class="text-end text-muted small">
                                    <div><?php echo date('M j, Y', strtotime($log['log_date'])); ?></div>
                                    <div>by <?php echo htmlspecialchars($log['user_name']); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($recentLogs)): ?>
                        <div class="list-group-item text-center text-muted py-4">
                            <i class="bi bi-calendar-x display-4"></i>
                            <p class="mt-2">No SEO activities recorded this month</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="seo_logs.php" class="btn btn-sm btn-outline-primary">
                    View All Logs <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?> 