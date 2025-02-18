<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/project.php';
require_once 'includes/seo_log.php';

// Require login
requireLogin();

// Set page title
$pageTitle = 'SEO Logs';

// Get current page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Get logs based on user role
if (isAdmin()) {
    $result = getAllSeoLogs($page);
} else {
    $result = getSeoLogsByProvider($_SESSION['user_id'], $page);
}

// Include header
include 'templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>SEO Logs</h1>
    <div>
        <a href="projects.php" class="btn btn-outline-primary me-2">View Projects</a>
        <a href="seo_log_form.php" class="btn btn-primary">Add New Log</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Project</th>
                        <th>Added By</th>
                        <th>Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['logs'] as $log): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($log['log_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getLogTypeClass($log['log_type']); ?>">
                                    <?php echo htmlspecialchars($log['log_type']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="project-details.php?id=<?php echo $log['project_id']; ?>#seo-logs">
                                    <?php echo htmlspecialchars($log['project_name']); ?>
                                </a>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($log['created_by_name']); ?>
                                <br>
                                <small class="text-muted">
                                    <?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <div class="log-preview">
                                    <?php
                                    // Strip HTML and limit preview to 100 characters
                                    $preview = strip_tags($log['log_details']);
                                    echo htmlspecialchars(strlen($preview) > 100 ? 
                                        substr($preview, 0, 100) . '...' : 
                                        $preview);
                                    ?>
                                </div>
                                <?php if ($log['image_path']): ?>
                                    <i class="bi bi-image text-muted" title="Has attachment"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="project-details.php?id=<?php echo $log['project_id']; ?>#seo-logs" 
                                       class="btn btn-sm btn-info me-1">View</a>
                                    <?php if (isAdmin() || $log['created_by'] === $_SESSION['user_id']): ?>
                                    <a href="seo_log_form.php?id=<?php echo $log['id']; ?>" 
                                       class="btn btn-sm btn-primary me-1">Edit</a>
                                    <form method="POST" action="project-details.php?id=<?php echo $log['project_id']; ?>" 
                                          class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this log?');">
                                        <input type="hidden" name="delete_log" value="<?php echo $log['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($result['logs'])): ?>
                        <tr>
                            <td colspan="6" class="text-center">No SEO logs found</td>
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
                            <a class="page-link" href="?page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Add Bootstrap Icons for the image icon -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

<style>
.log-preview {
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<?php include 'templates/footer.php'; ?> 