<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/customer.php';
require_once 'includes/project.php';
require_once 'includes/seo_log.php';

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

<div class="row mb-4">
    <div class="col-12">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
        <p class="lead">Here's an overview of your SEO management activities.</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Customers</h5>
                <h2 class="display-4"><?php echo $stats['customers']; ?></h2>
                <p class="card-text">Total registered customers</p>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="customers.php" class="text-white text-decoration-none">View all customers →</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Projects</h5>
                <h2 class="display-4"><?php echo $stats['projects']; ?></h2>
                <p class="card-text">Active SEO projects</p>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="projects.php" class="text-white text-decoration-none">View all projects →</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <h5 class="card-title">SEO Logs</h5>
                <h2 class="display-4"><?php echo $stats['seo_logs']; ?></h2>
                <p class="card-text">Total activity logs</p>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="seo_logs.php" class="text-white text-decoration-none">View all logs →</a>
            </div>
        </div>
    </div>
    
    <?php if (isAdmin()): ?>
    <div class="col-md-3">
        <div class="card bg-secondary text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Users</h5>
                <h2 class="display-4"><?php echo $stats['users']; ?></h2>
                <p class="card-text">Registered team members</p>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="users.php" class="text-white text-decoration-none">Manage users →</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="customer-form.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add New Customer
                    </a>
                    <a href="project-form.php" class="btn btn-success">
                        <i class="bi bi-plus-circle me-2"></i>Create New Project
                    </a>
                    <a href="seo_log_form.php" class="btn btn-info">
                        <i class="bi bi-plus-circle me-2"></i>Add SEO Log
                    </a>
                    <?php if (isAdmin()): ?>
                        <a href="user-form.php" class="btn btn-secondary">
                            <i class="bi bi-plus-circle me-2"></i>Add New User
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent SEO Logs</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($recentLogs as $log): ?>
                        <a href="project-details.php?id=<?php echo $log['project_id']; ?>#seo-logs" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($log['project_name']); ?></h6>
                                <small class="text-muted">
                                    <?php echo date('M j, Y', strtotime($log['created_at'])); ?>
                                </small>
                            </div>
                            <p class="mb-1">
                                <span class="badge bg-<?php echo getLogTypeClass($log['log_type']); ?>">
                                    <?php echo htmlspecialchars($log['log_type']); ?>
                                </span>
                                <?php echo htmlspecialchars(substr(strip_tags($log['log_details']), 0, 100)) . '...'; ?>
                            </p>
                            <small><?php echo htmlspecialchars($log['customer_name']); ?></small>
                        </a>
                    <?php endforeach; ?>
                    
                    <?php if (empty($recentLogs)): ?>
                        <div class="list-group-item">
                            <p class="mb-0 text-muted">No recent SEO logs found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="seo_logs.php" class="btn btn-sm btn-primary">View All Logs</a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Projects -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Projects</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentProjects as $project): ?>
                                <tr>
                                    <td>
                                        <?php if ($project['logo_path']): ?>
                                            <img src="<?php echo htmlspecialchars($project['logo_path']); ?>" 
                                                 alt="Logo" class="me-2" style="width: 25px; height: 25px; object-fit: contain;">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($project['project_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($project['customer_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $project['status'] === 'active' ? 'success' : 
                                                 ($project['status'] === 'paused' ? 'warning' : 'secondary'); 
                                        ?>">
                                            <?php echo htmlspecialchars(ucfirst($project['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($project['created_at'])); ?></td>
                                    <td>
                                        <a href="project-details.php?id=<?php echo $project['id']; ?>" 
                                           class="btn btn-sm btn-info">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($recentProjects)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No recent projects found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="projects.php" class="btn btn-sm btn-primary">View All Projects</a>
            </div>
        </div>
    </div>
</div>

<!-- Add Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

<?php include 'templates/footer.php'; ?> 