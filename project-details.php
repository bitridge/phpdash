<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/customer.php';
require_once 'includes/project.php';

// Require login
requireLogin();

// Get project ID
$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$project = getProject($projectId);

if (!$project) {
    header('Location: projects.php');
    exit();
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
        <div class="card">
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