<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/project.php';
require_once 'includes/seo_log.php';

// Require login
requireLogin();

// Get project ID
$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if user has access to this project
if (!canAccessProject($projectId, $_SESSION['user_id'])) {
    header('Location: projects.php');
    exit();
}

$project = getProject($projectId);
if (!$project) {
    header('Location: projects.php');
    exit();
}

// Handle log deletion
if (isset($_POST['delete_log'])) {
    $logId = (int)$_POST['delete_log'];
    $log = getSeoLog($logId);
    
    // Check if user has permission to delete this log
    if ($log && (isAdmin() || $log['created_by'] === $_SESSION['user_id'])) {
        $result = deleteSeoLog($logId);
        if (!$result['success']) {
            $error = $result['message'];
        }
    }
}

// Get current page for logs
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$logsResult = getSeoLogs($projectId, $page);

// Set page title
$pageTitle = $project['project_name'];

// Include header
include 'templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><?php echo htmlspecialchars($project['project_name']); ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="projects.php">Projects</a></li>
                <?php if (isAdmin()): ?>
                <li class="breadcrumb-item">
                    <a href="customer-details.php?id=<?php echo $project['customer_id']; ?>">
                        <?php echo htmlspecialchars($project['customer_name']); ?>
                    </a>
                </li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($project['project_name']); ?>
                </li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="seo_log_form.php?project_id=<?php echo $project['id']; ?>" 
           class="btn btn-success me-2">Add SEO Log</a>
        <a href="generate_report.php?project_id=<?php echo $project['id']; ?>" 
           class="btn btn-info me-2">Generate Report</a>
        <?php if (isAdmin()): ?>
        <a href="project-form.php?id=<?php echo $project['id']; ?>" 
           class="btn btn-primary">Edit Project</a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Project Details Card -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <?php if ($project['logo_path']): ?>
                    <div class="text-center mb-3">
                        <img src="<?php echo htmlspecialchars($project['logo_path']); ?>" 
                             alt="Project Logo" class="img-fluid" style="max-height: 150px;">
                    </div>
                <?php endif; ?>
                
                <h5 class="card-title">Project Details</h5>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Customer</dt>
                    <dd class="col-sm-8">
                        <?php if (isAdmin()): ?>
                        <a href="customer-details.php?id=<?php echo $project['customer_id']; ?>">
                            <?php echo htmlspecialchars($project['customer_name']); ?>
                        </a>
                        <?php else: ?>
                        <?php echo htmlspecialchars($project['customer_name']); ?>
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-4">Company</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($project['company_name']); ?></dd>
                    
                    <dt class="col-sm-4">Website</dt>
                    <dd class="col-sm-8">
                        <a href="<?php echo htmlspecialchars($project['project_url']); ?>" target="_blank">
                            <?php echo htmlspecialchars($project['project_url']); ?>
                        </a>
                    </dd>
                    
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?php 
                            echo $project['status'] === 'active' ? 'success' : 
                                 ($project['status'] === 'paused' ? 'warning' : 'secondary'); 
                        ?>">
                            <?php echo htmlspecialchars(ucfirst($project['status'])); ?>
                        </span>
                    </dd>
                    
                    <?php if ($project['project_details']): ?>
                    <dt class="col-12">Details</dt>
                    <dd class="col-12">
                        <div class="mt-2">
                            <?php echo $project['project_details']; ?>
                        </div>
                    </dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
    
    <!-- SEO Logs Card -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">SEO Activity Log</h5>
                <a href="seo_log_form.php?project_id=<?php echo $project['id']; ?>" 
                   class="btn btn-sm btn-success">Add Log</a>
            </div>
            <div class="card-body">
                <?php if (!empty($logsResult['logs'])): ?>
                    <?php foreach ($logsResult['logs'] as $log): ?>
                        <div class="log-entry mb-4 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-<?php echo getLogTypeClass($log['log_type']); ?> me-2">
                                        <?php echo htmlspecialchars($log['log_type']); ?>
                                    </span>
                                    <span class="text-muted">
                                        <?php echo date('F j, Y', strtotime($log['log_date'])); ?>
                                    </span>
                                </div>
                                <div class="btn-group">
                                    <a href="seo_log_form.php?id=<?php echo $log['id']; ?>" 
                                       class="btn btn-sm btn-primary me-1">Edit</a>
                                    <?php if (isAdmin()): ?>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this log?');">
                                        <input type="hidden" name="delete_log" value="<?php echo $log['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="log-content">
                                <?php echo $log['log_details']; ?>
                            </div>
                            <?php if ($log['image_path']): ?>
                                <div class="mt-3">
                                    <img src="<?php echo htmlspecialchars($log['image_path']); ?>" 
                                         alt="Log Image" class="img-fluid rounded">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($logsResult['pages'] > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $logsResult['pages']; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?id=<?php echo $project['id']; ?>&page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-center mb-0">No SEO logs found for this project.</p>
                    <div class="text-center mt-3">
                        <a href="seo_log_form.php?project_id=<?php echo $project['id']; ?>" 
                           class="btn btn-success">Add First Log</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?> 