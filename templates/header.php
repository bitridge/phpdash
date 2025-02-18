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
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
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
                    <?php if (isAdmin()): ?>
                    <!-- Admin Navigation -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'customers' ? 'active' : ''; ?>" 
                           href="customers.php">
                           <i class="bi bi-building me-1"></i>
                           Customers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'projects' ? 'active' : ''; ?>" 
                           href="projects.php">
                           <i class="bi bi-kanban me-1"></i>
                           All Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'seo_logs' ? 'active' : ''; ?>" 
                           href="seo_logs.php">
                           <i class="bi bi-journal-text me-1"></i>
                           All SEO Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>" 
                           href="users.php">
                           <i class="bi bi-people me-1"></i>
                           Users
                        </a>
                    </li>
                    <?php else: ?>
                    <!-- SEO Provider Navigation -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'projects' ? 'active' : ''; ?>" 
                           href="projects.php">
                           <i class="bi bi-kanban me-1"></i>
                           My Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'seo_logs' ? 'active' : ''; ?>" 
                           href="seo_logs.php">
                           <i class="bi bi-journal-text me-1"></i>
                           SEO Logs
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>" 
                           href="settings.php">
                           <i class="bi bi-gear me-1"></i>
                           Settings
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="bi bi-person me-2"></i>My Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">

<style>
.navbar {
    padding: 0.8rem 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.navbar-brand {
    font-weight: 600;
    font-size: 1.3rem;
}

.nav-link {
    padding: 0.5rem 1rem !important;
    font-weight: 500;
    transition: all 0.2s ease;
}

.nav-link:hover {
    background-color: rgba(255,255,255,0.1);
    border-radius: 4px;
}

.nav-link.active {
    background-color: rgba(255,255,255,0.2);
    border-radius: 4px;
}

.navbar-nav .nav-item {
    margin: 0 0.2rem;
}

.bi {
    font-size: 1.1rem;
    vertical-align: -2px;
}
</style>
</body>
</html> 