<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/project.php';
require_once 'includes/seo_log.php';

// Require login
requireLogin();

$error = '';
$success = '';
$log = null;
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;

// Check if editing
if (isset($_GET['id'])) {
    $log = getSeoLog($_GET['id']);
    if (!$log) {
        header('Location: projects.php');
        exit();
    }
    $projectId = $log['project_id'];
}

// Get project
$project = $projectId ? getProject($projectId) : null;
if (!$project) {
    header('Location: projects.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'project_id' => $projectId,
        'log_details' => $_POST['log_details'] ?? '',
        'log_date' => $_POST['log_date'] ?? date('Y-m-d'),
        'log_type' => $_POST['log_type'] ?? ''
    ];
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $image_path = uploadSeoLogImage($_FILES['image']);
        if ($image_path) {
            $data['image_path'] = $image_path;
        } else {
            $error = 'Failed to upload image';
        }
    }
    
    if (!$error) {
        if ($log) {
            // Update existing log
            $result = updateSeoLog($log['id'], $data);
            if ($result['success']) {
                $success = 'SEO log updated successfully';
                $log = getSeoLog($log['id']); // Refresh data
            } else {
                $error = $result['message'];
            }
        } else {
            // Create new log
            $result = createSeoLog($data);
            if ($result['success']) {
                header('Location: project-details.php?id=' . $projectId . '#seo-logs');
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Set page title
$pageTitle = ($log ? 'Edit' : 'Add') . ' SEO Log';

// Include header
include 'templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><?php echo $log ? 'Edit' : 'Add'; ?> SEO Log</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="projects.php">Projects</a></li>
                <li class="breadcrumb-item">
                    <a href="project-details.php?id=<?php echo $project['id']; ?>">
                        <?php echo htmlspecialchars($project['project_name']); ?>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo $log ? 'Edit' : 'Add'; ?> Log
                </li>
            </ol>
        </nav>
    </div>
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
                        <label for="log_date" class="form-label">Log Date</label>
                        <input type="date" class="form-control" id="log_date" name="log_date" required
                               value="<?php echo $log ? $log['log_date'] : date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="log_type" class="form-label">Log Type</label>
                        <select class="form-select" id="log_type" name="log_type" required>
                            <option value="">Select Type</option>
                            <?php foreach (getLogTypeOptions() as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                        <?php echo ($log && $log['log_type'] === $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="log_details" class="form-label">Log Details</label>
                        <div id="editor" style="height: 300px;"><?php echo $log ? $log['log_details'] : ''; ?></div>
                        <input type="hidden" name="log_details" id="log_details">
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Screenshot/Image</label>
                        <?php if ($log && $log['image_path']): ?>
                            <div class="mb-2">
                                <img src="<?php echo htmlspecialchars($log['image_path']); ?>" 
                                     alt="Current Image" class="img-fluid" style="max-height: 200px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $log ? 'Update' : 'Create'; ?> Log
                        </button>
                        <a href="project-details.php?id=<?php echo $projectId; ?>#seo-logs" 
                           class="btn btn-secondary">Cancel</a>
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
        var logDetails = document.querySelector('#log_details');
        logDetails.value = quill.root.innerHTML;
    });
});
</script>

<?php include 'templates/footer.php'; ?> 