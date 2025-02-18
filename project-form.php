<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/customer.php';
require_once 'includes/project.php';

// Require login
requireLogin();

$error = '';
$success = '';
$project = null;
$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;

// Check if editing
if (isset($_GET['id'])) {
    $project = getProject($_GET['id']);
    if (!$project) {
        header('Location: projects.php');
        exit();
    }
    $customerId = $project['customer_id'];
}

// Get customer for pre-selected value
$customer = $customerId ? getCustomer($customerId) : null;

// Get all customers for dropdown
$allCustomers = getCustomers(1, PHP_INT_MAX)['customers'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'customer_id' => $_POST['customer_id'] ?? '',
        'project_name' => $_POST['project_name'] ?? '',
        'project_url' => $_POST['project_url'] ?? '',
        'project_details' => $_POST['project_details'] ?? '',
        'status' => $_POST['status'] ?? 'active'
    ];
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['size'] > 0) {
        $logo_path = uploadProjectLogo($_FILES['logo']);
        if ($logo_path) {
            $data['logo_path'] = $logo_path;
        } else {
            $error = 'Failed to upload logo';
        }
    }
    
    if (!$error) {
        if ($project) {
            // Update existing project
            $result = updateProject($project['id'], $data);
            if ($result['success']) {
                $success = 'Project updated successfully';
                $project = getProject($project['id']); // Refresh data
            } else {
                $error = $result['message'];
            }
        } else {
            // Create new project
            $result = createProject($data);
            if ($result['success']) {
                header('Location: customer-details.php?id=' . $data['customer_id']);
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Set page title
$pageTitle = ($project ? 'Edit' : 'Add') . ' Project';

// Include header
include 'templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo $project ? 'Edit' : 'Add'; ?> Project</h1>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Customer</label>
                        <select class="form-select" id="customer_id" name="customer_id" required 
                                <?php echo $customerId ? 'disabled' : ''; ?>>
                            <option value="">Select Customer</option>
                            <?php foreach ($allCustomers as $cust): ?>
                                <option value="<?php echo $cust['id']; ?>" 
                                        <?php echo ($customerId == $cust['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cust['name']); ?> 
                                    (<?php echo htmlspecialchars($cust['company_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($customerId): ?>
                            <input type="hidden" name="customer_id" value="<?php echo $customerId; ?>">
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="project_name" class="form-label">Project Name</label>
                        <input type="text" class="form-control" id="project_name" name="project_name" required
                               value="<?php echo $project ? htmlspecialchars($project['project_name']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="project_url" class="form-label">Project URL</label>
                        <input type="url" class="form-control" id="project_url" name="project_url"
                               value="<?php echo $project ? htmlspecialchars($project['project_url']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" <?php echo ($project && $project['status'] === 'active') ? 'selected' : ''; ?>>
                                Active
                            </option>
                            <option value="paused" <?php echo ($project && $project['status'] === 'paused') ? 'selected' : ''; ?>>
                                Paused
                            </option>
                            <option value="completed" <?php echo ($project && $project['status'] === 'completed') ? 'selected' : ''; ?>>
                                Completed
                            </option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="project_details" class="form-label">Project Details</label>
                        <div id="editor" style="height: 200px;"><?php echo $project ? $project['project_details'] : ''; ?></div>
                        <input type="hidden" name="project_details" id="project_details">
                    </div>
                    
                    <div class="mb-3">
                        <label for="logo" class="form-label">Project Logo</label>
                        <?php if ($project && $project['logo_path']): ?>
                            <div class="mb-2">
                                <img src="<?php echo htmlspecialchars($project['logo_path']); ?>" 
                                     alt="Current Logo" style="max-width: 100px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $project ? 'Update' : 'Create'; ?> Project
                        </button>
                        <?php if ($customerId): ?>
                            <a href="customer-details.php?id=<?php echo $customerId; ?>" class="btn btn-secondary">Cancel</a>
                        <?php else: ?>
                            <a href="projects.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Include Quill -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill editor
    var quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'header': 1 }, { 'header': 2 }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'script': 'sub'}, { 'script': 'super' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'direction': 'rtl' }],
                [{ 'size': ['small', false, 'large', 'huge'] }],
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'font': [] }],
                [{ 'align': [] }],
                ['clean']
            ]
        }
    });
    
    // Handle form submission
    document.querySelector('form').addEventListener('submit', function() {
        var projectDetails = document.querySelector('#project_details');
        projectDetails.value = quill.root.innerHTML;
    });
});
</script>

<?php include 'templates/footer.php'; ?> 