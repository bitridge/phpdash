<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/customer.php';
require_once 'includes/project.php';

// Require login
requireLogin();

// Get customer ID
$customerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$customer = getCustomer($customerId);

if (!$customer) {
    header('Location: customers.php');
    exit();
}

// Get customer's projects
$projectsResult = getCustomerProjects($customerId);

// Set page title
$pageTitle = $customer['name'] . ' - Customer Details';

// Include header
include 'templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo htmlspecialchars($customer['name']); ?></h1>
    <div>
        <a href="project-form.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-success me-2">Add Project</a>
        <a href="customer-form.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary">Edit Customer</a>
    </div>
</div>

<div class="row">
    <!-- Customer Details Card -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <?php if ($customer['logo_path']): ?>
                    <div class="text-center mb-3">
                        <img src="<?php echo htmlspecialchars($customer['logo_path']); ?>" 
                             alt="Company Logo" class="img-fluid" style="max-height: 150px;">
                    </div>
                <?php endif; ?>
                
                <h5 class="card-title">Company Details</h5>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Company</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($customer['company_name']); ?></dd>
                    
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">
                        <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>">
                            <?php echo htmlspecialchars($customer['email']); ?>
                        </a>
                    </dd>
                    
                    <dt class="col-sm-4">Phone</dt>
                    <dd class="col-sm-8">
                        <?php if ($customer['phone']): ?>
                            <a href="tel:<?php echo htmlspecialchars($customer['phone']); ?>">
                                <?php echo htmlspecialchars($customer['phone']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Not provided</span>
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-4">Website</dt>
                    <dd class="col-sm-8">
                        <a href="<?php echo htmlspecialchars($customer['website_url']); ?>" target="_blank">
                            <?php echo htmlspecialchars($customer['website_url']); ?>
                        </a>
                    </dd>
                    
                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8">
                        <?php echo date('M j, Y', strtotime($customer['created_at'])); ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    
    <!-- Projects Card -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Projects</h5>
                <a href="project-form.php?customer_id=<?php echo $customer['id']; ?>" 
                   class="btn btn-sm btn-success">Add Project</a>
            </div>
            <div class="card-body">
                <?php if (!empty($projectsResult['projects'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>URL</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projectsResult['projects'] as $project): ?>
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
                                        <td><?php echo date('M j, Y', strtotime($project['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="project-details.php?id=<?php echo $project['id']; ?>" 
                                                   class="btn btn-sm btn-info me-1">View</a>
                                                <a href="project-form.php?id=<?php echo $project['id']; ?>" 
                                                   class="btn btn-sm btn-primary">Edit</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center mb-0">No projects found for this customer.</p>
                    <div class="text-center mt-3">
                        <a href="project-form.php?customer_id=<?php echo $customer['id']; ?>" 
                           class="btn btn-success">Create First Project</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?> 