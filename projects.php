<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/project.php';

// Set page title
$pageTitle = 'Projects';

// Handle delete action
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $result = deleteProject($_POST['id']);
    if (!$result['success']) {
        $error = $result['message'];
    }
}

// Get customer ID filter
$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;

// Get current page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$result = getProjects($page, 10, $customerId);

// Include header
include 'templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Projects</h1>
    <a href="project-form.php" class="btn btn-primary">Add Project</a>
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
                        <th>Created By</th>
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
                                <a href="customers.php?id=<?php echo $project['customer_id']; ?>">
                                    <?php echo htmlspecialchars($project['customer_name']); ?>
                                    (<?php echo htmlspecialchars($project['company_name']); ?>)
                                </a>
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
                            <td><?php echo htmlspecialchars($project['created_by_name']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="project-details.php?id=<?php echo $project['id']; ?>" 
                                       class="btn btn-sm btn-info me-1">View</a>
                                    <a href="project-form.php?id=<?php echo $project['id']; ?>" 
                                       class="btn btn-sm btn-primary me-1">Edit</a>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this project?');">
                                        <input type="hidden" name="id" value="<?php echo $project['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($result['projects'])): ?>
                        <tr>
                            <td colspan="6" class="text-center">No projects found</td>
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