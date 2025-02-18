<?php
// Ensure user is logged in
if (!function_exists('requireLogin')) {
    require_once __DIR__ . '/../includes/auth.php';
}
requireLogin();

// Load settings
require_once __DIR__ . '/../includes/settings.php';
$settings = Settings::getInstance();

// Get the current page name for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Set default page title if not set
if (!isset($pageTitle)) {
    $pageTitle = 'Dashboard';
}

// Get application name from settings
$appName = $settings->get('app_name', 'SEO Dashboard');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo htmlspecialchars($appName); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <?php if ($logo = $settings->get('app_logo')): ?>
                    <img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo" height="30" class="me-2">
                <?php endif; ?>
                <?php echo htmlspecialchars($appName); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>" 
                           href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'customers' ? 'active' : ''; ?>" 
                           href="customers.php">Customers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'projects' ? 'active' : ''; ?>" 
                           href="projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'seo_logs' ? 'active' : ''; ?>" 
                           href="seo_logs.php">SEO Logs</a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>" 
                           href="users.php">Users</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>" 
                           href="settings.php">Settings</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4"> 