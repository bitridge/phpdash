<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/customer.php';

// Set page title
$pageTitle = 'Dashboard';

// Get customer count
$conn = getDbConnection();
$result = $conn->query("SELECT COUNT(*) as count FROM customers");
$customerCount = $result ? $result->fetch_assoc()['count'] : 0;

// Get user count (for admins)
$userCount = 0;
if (isAdmin()) {
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $userCount = $result ? $result->fetch_assoc()['count'] : 0;
}

// Include header
include 'templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1>Welcome to SEO Dashboard</h1>
        <p class="lead">Your centralized platform for SEO management and analytics.</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <h5 class="card-title">Total Customers</h5>
                <p class="card-text display-4"><?php echo $customerCount; ?></p>
                <a href="customers.php" class="btn btn-primary">View Customers</a>
            </div>
        </div>
    </div>
    
    <?php if (isAdmin()): ?>
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <h5 class="card-title">Total Users</h5>
                <p class="card-text display-4"><?php echo $userCount; ?></p>
                <a href="users.php" class="btn btn-primary">Manage Users</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <h5 class="card-title">Quick Actions</h5>
                <div class="d-grid gap-2">
                    <a href="projects.php" class="btn btn-info">View SEO Logs</a>
                    <a href="project-form.php" class="btn btn-success">Add New Project</a>
                    <a href="customer-form.php" class="btn btn-primary">Add New Customer</a>
                    <?php if (isAdmin()): ?>
                        <a href="user-form.php" class="btn btn-secondary">Add New User</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?> 