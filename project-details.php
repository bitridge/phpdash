<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/customer.php';
require_once 'includes/project.php';
require_once 'includes/seo_log.php';

// Require login
requireLogin();

// Get project ID
$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$project = getProject($projectId);

if (!$project) {
    header('Location: projects.php');
    exit();
}

// Handle log deletion
if (isset($_POST['delete_log'])) {
    $result = deleteSeoLog($_POST['delete_log']);
    if (!$result['success']) {
        $error = $result['message'];
    }
}

// Set page title
$pageTitle = $project['project_name'] . ' - Project Details';

// Include header
include 'templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><?php echo htmlspecialchars($project['project_name']); ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="projects.php">Projects</a></li>
                <li class="breadcrumb-item">
                    <a href="customer-details.php?id=<?php echo $project['customer_id']; ?>">
                        <?php echo htmlspecialchars($project['customer_name']); ?>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($project['project_name']); ?>
                </li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="project-form.php?id=<?php echo $project['id']; ?>" class="btn btn-primary">Edit Project</a>
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
                
                <h5 class="card-title">Project Information</h5>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?php 
                            echo $project['status'] === 'active' ? 'success' : 
                                 ($project['status'] === 'paused' ? 'warning' : 'secondary'); 
                        ?>">
                            <?php echo htmlspecialchars(ucfirst($project['status'])); ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4">Customer</dt>
                    <dd class="col-sm-8">
                        <a href="customer-details.php?id=<?php echo $project['customer_id']; ?>">
                            <?php echo htmlspecialchars($project['customer_name']); ?>
                            (<?php echo htmlspecialchars($project['company_name']); ?>)
                        </a>
                    </dd>
                    
                    <?php if ($project['project_url']): ?>
                        <dt class="col-sm-4">URL</dt>
                        <dd class="col-sm-8">
                            <a href="<?php echo htmlspecialchars($project['project_url']); ?>" 
                               target="_blank" class="text-break">
                                <?php echo htmlspecialchars($project['project_url']); ?>
                            </a>
                        </dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-4">Created By</dt>
                    <dd class="col-sm-8">
                        <?php echo htmlspecialchars($project['created_by_name']); ?>
                    </dd>
                    
                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8">
                        <?php echo date('M j, Y', strtotime($project['created_at'])); ?>
                    </dd>
                    
                    <dt class="col-sm-4">Last Updated</dt>
                    <dd class="col-sm-8">
                        <?php echo date('M j, Y', strtotime($project['updated_at'])); ?>
                    </dd>
                </dl>
            </div>
        </div>
        
        <!-- Quick Actions Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="project-form.php?id=<?php echo $project['id']; ?>" 
                       class="btn btn-primary">Edit Project</a>
                    <form method="POST" action="projects.php" 
                          onsubmit="return confirm('Are you sure you want to delete this project?');">
                        <input type="hidden" name="id" value="<?php echo $project['id']; ?>">
                        <button type="submit" name="delete" class="btn btn-danger w-100">Delete Project</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Project Details Content -->
    <div class="col-md-8">
        <!-- Project Details Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Project Details</h5>
            </div>
            <div class="card-body">
                <?php if ($project['project_details']): ?>
                    <div class="ql-snow">
                        <div class="ql-editor">
                            <?php echo $project['project_details']; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No detailed description available.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- SEO Logs Section -->
        <div class="card" id="seo-logs">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">SEO Logs</h5>
                <a href="seo_log_form.php?project_id=<?php echo $project['id']; ?>" 
                   class="btn btn-success btn-lg">
                   <i class="bi bi-plus-circle me-2"></i>Add New Log
                </a>
            </div>
            <div class="card-body">
                <?php
                $page = isset($_GET['log_page']) ? max(1, (int)$_GET['log_page']) : 1;
                $logsResult = getSeoLogs($project['id'], $page);
                
                if (!empty($logsResult['logs'])):
                ?>
                    <div class="timeline">
                        <?php foreach ($logsResult['logs'] as $log): ?>
                            <div class="timeline-item mb-4">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="badge bg-<?php echo getLogTypeClass($log['log_type']); ?> me-2">
                                            <?php echo htmlspecialchars($log['log_type']); ?>
                                        </span>
                                        <strong><?php echo date('F j, Y', strtotime($log['log_date'])); ?></strong>
                                        <small class="text-muted ms-2">
                                            by <?php echo htmlspecialchars($log['created_by_name']); ?>
                                        </small>
                                    </div>
                                    <div class="btn-group">
                                        <a href="seo_log_form.php?id=<?php echo $log['id']; ?>" 
                                           class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this log?');">
                                            <input type="hidden" name="delete_log" value="<?php echo $log['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-body">
                                        <div class="ql-snow">
                                            <div class="ql-editor">
                                                <?php echo $log['log_details']; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($log['image_path']): ?>
                                            <div class="mt-3">
                                                <a href="<?php echo htmlspecialchars($log['image_path']); ?>" 
                                                   target="_blank">
                                                    <img src="<?php echo htmlspecialchars($log['image_path']); ?>" 
                                                         alt="Log Image" class="img-fluid" 
                                                         style="max-height: 200px;">
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($logsResult['pages'] > 1): ?>
                        <nav aria-label="SEO Log navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $logsResult['pages']; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?id=<?php echo $project['id']; ?>&log_page=<?php echo $i; ?>#seo-logs">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <h4 class="text-muted mb-4">No SEO logs found for this project</h4>
                        <a href="seo_log_form.php?project_id=<?php echo $project['id']; ?>" 
                           class="btn btn-success btn-lg">
                           <i class="bi bi-plus-circle me-2"></i>Create First Log
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Include Quill CSS for rendering -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
    .ql-editor {
        padding: 0;
    }
    .ql-snow {
        border: none;
    }
</style>

<?php include 'templates/footer.php'; ?> 