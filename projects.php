<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/project.php';

// Set page title
$pageTitle = isAdmin() ? 'All Projects' : 'My Projects';

// Handle delete action (only for admins)
if (isAdmin() && isset($_POST['delete']) && isset($_POST['id'])) {
    $result = deleteProject($_POST['id']);
    if (!$result['success']) {
        $error = $result['message'];
    }
}

// Get customer ID filter (only for admins)
$customerId = isAdmin() ? (isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null) : null;

// Get current page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Get projects based on user role
if (isAdmin()) {
    $result = getProjects($page, 10, $customerId);
} else {
    $result = getProjectsByProvider($_SESSION['user_id'], $page);
}

// Include header
include 'templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo $pageTitle; ?></h1>
    <?php if (isAdmin()): ?>
    <a href="project-form.php" class="btn btn-primary">Add Project</a>
    <?php endif; ?>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Customer</th>
                        <th>URL</th>
                        <th>Status</th>
                        <?php if (isAdmin()): ?>
                        <th>Created By</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['projects'] as $project): ?>
                        <tr>
                            <td>
                                <?php if ($project['logo_path']): ?>
                                    <img src="<?php echo htmlspecialchars($project['logo_path']); ?>" 
                                         alt="Logo" class="me-2" style="width: 25px; height: 25px; object-fit: contain;">
                                <?php endif; ?>
                                <a href="project-details.php?id=<?php echo $project['id']; ?>" 
                                   class="text-decoration-none">
                                    <?php echo htmlspecialchars($project['project_name']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if (isAdmin()): ?>
                                <a href="customer-details.php?id=<?php echo $project['customer_id']; ?>">
                                    <?php echo htmlspecialchars($project['customer_name']); ?>
                                    (<?php echo htmlspecialchars($project['company_name']); ?>)
                                </a>
                                <?php else: ?>
                                <?php echo htmlspecialchars($project['customer_name']); ?>
                                (<?php echo htmlspecialchars($project['company_name']); ?>)
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($project['project_url']): ?>
                                    <a href="<?php echo htmlspecialchars($project['project_url']); ?>" 
                                       target="_blank"><?php echo htmlspecialchars($project['project_url']); ?></a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $project['status'] === 'active' ? 'success' : 
                                         ($project['status'] === 'paused' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo htmlspecialchars(ucfirst($project['status'])); ?>
                                </span>
                            </td>
                            <?php if (isAdmin()): ?>
                            <td><?php echo htmlspecialchars($project['created_by_name']); ?></td>
                            <?php endif; ?>
                            <td>
                                <div class="btn-group">
                                    <a href="project-details.php?id=<?php echo $project['id']; ?>" 
                                       class="btn btn-sm btn-info me-1">View</a>
                                    <?php if (isAdmin()): ?>
                                    <a href="project-form.php?id=<?php echo $project['id']; ?>" 
                                       class="btn btn-sm btn-primary me-1">Edit</a>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this project?');">
                                        <input type="hidden" name="id" value="<?php echo $project['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                    <?php else: ?>
                                    <a href="seo_log_form.php?project_id=<?php echo $project['id']; ?>" 
                                       class="btn btn-sm btn-success">Add Log</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($result['projects'])): ?>
                        <tr>
                            <td colspan="<?php echo isAdmin() ? '6' : '5'; ?>" class="text-center">
                                <?php echo isAdmin() ? 'No projects found' : 'No projects assigned to you'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($result['pages'] > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php 
                                echo $customerId ? '&customer_id=' . $customerId : ''; 
                            ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php include 'templates/footer.php'; ?> 