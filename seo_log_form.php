<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/project.php';
require_once 'includes/seo_log.php';

// Require login
requireLogin();

// Initialize variables
$log = null;
$project = null;
$error = null;

// Check if editing existing log
if (isset($_GET['id'])) {
    $logId = (int)$_GET['id'];
    $log = getSeoLog($logId);
    
    // Check if log exists and user has permission to edit it
    if (!$log || (!isAdmin() && $log['created_by'] !== $_SESSION['user_id'])) {
        header('Location: projects.php');
        exit();
    }
    
    $project = getProject($log['project_id']);
} 
// Check if adding new log to specific project
elseif (isset($_GET['project_id'])) {
    $projectId = (int)$_GET['project_id'];
    $project = getProject($projectId);
    
    // Check if project exists and user has access
    if (!$project || (!isAdmin() && !canAccessProject($projectId, $_SESSION['user_id']))) {
        header('Location: projects.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logData = [
        'project_id' => $_POST['project_id'] ?? null,
        'log_type' => $_POST['log_type'] ?? null,
        'log_date' => $_POST['log_date'] ?? date('Y-m-d'),
        'log_details' => $_POST['log_details'] ?? null,
        'created_by' => $_SESSION['user_id']
    ];
    
    // Check if user has access to the project
    if (!isAdmin() && !canAccessProject($logData['project_id'], $_SESSION['user_id'])) {
        header('Location: projects.php');
        exit();
    }
    
    // Handle image upload
    if (isset($_FILES['log_image']) && $_FILES['log_image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = uploadSeoLogImage($_FILES['log_image']);
        if ($imagePath) {
            $logData['image_path'] = $imagePath;
        }
    }
    
    if ($log) {
        // Editing existing log - check permissions
        if (!isAdmin() && $log['created_by'] !== $_SESSION['user_id']) {
            header('Location: projects.php');
            exit();
        }
        $result = updateSeoLog($log['id'], $logData);
    } else {
        // Creating new log
        $result = createSeoLog($logData);
    }
    
    if ($result['success']) {
        header('Location: project-details.php?id=' . $logData['project_id'] . '#seo-logs');
        exit();
    } else {
        $error = $result['message'];
    }
}

// Get available projects for dropdown
if (isAdmin()) {
    $projects = getProjects(1, PHP_INT_MAX)['projects'];
} else {
    $projects = getProjectsByProvider($_SESSION['user_id'], 1, PHP_INT_MAX)['projects'];
}

// Set page title
$pageTitle = $log ? 'Edit SEO Log' : 'Add SEO Log';

// Include header
include 'templates/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1><?php echo $pageTitle; ?></h1>
            <?php if ($project): ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="projects.php">Projects</a></li>
                    <li class="breadcrumb-item">
                        <a href="project-details.php?id=<?php echo $project['id']; ?>">
                            <?php echo htmlspecialchars($project['project_name']); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo $log ? 'Edit Log' : 'Add Log'; ?>
                    </li>
                </ol>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <!-- Project Selection -->
                <div class="mb-3">
                    <label for="project_id" class="form-label">Project</label>
                    <?php if ($project): ?>
                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                        <input type="text" class="form-control" 
                               value="<?php echo htmlspecialchars($project['project_name']); ?>" 
                               disabled>
                    <?php else: ?>
                        <select name="project_id" id="project_id" class="form-select" required>
                            <option value="">Select Project</option>
                            <?php foreach ($projects as $proj): ?>
                                <option value="<?php echo $proj['id']; ?>" 
                                        <?php echo ($log && $log['project_id'] == $proj['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($proj['project_name']); ?> 
                                    (<?php echo htmlspecialchars($proj['customer_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>

                <!-- Log Type -->
                <div class="mb-3">
                    <label for="log_type" class="form-label">Log Type</label>
                    <select name="log_type" id="log_type" class="form-select" required>
                        <option value="">Select Type</option>
                        <?php foreach (SEO_LOG_TYPES as $type): ?>
                            <option value="<?php echo $type; ?>" 
                                    <?php echo ($log && $log['log_type'] === $type) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Log Date -->
                <div class="mb-3">
                    <label for="log_date" class="form-label">Log Date</label>
                    <input type="date" name="log_date" id="log_date" class="form-control" 
                           value="<?php echo $log ? $log['log_date'] : date('Y-m-d'); ?>" required>
                </div>

                <!-- Log Details -->
                <div class="mb-3">
                    <label for="log_details" class="form-label">Details</label>
                    <div id="editor" style="height: 200px;"><?php 
                        echo $log ? $log['log_details'] : ''; 
                    ?></div>
                    <input type="hidden" name="log_details" id="log_details">
                </div>

                <!-- Image Upload -->
                <div class="mb-3">
                    <label for="log_image" class="form-label">
                        Attach Image <?php echo $log && $log['image_path'] ? '(will replace existing)' : ''; ?>
                    </label>
                    <input type="file" name="log_image" id="log_image" class="form-control" 
                           accept="image/*">
                    <?php if ($log && $log['image_path']): ?>
                        <div class="mt-2">
                            <img src="<?php echo htmlspecialchars($log['image_path']); ?>" 
                                 alt="Current Image" class="img-thumbnail" style="max-height: 200px;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?php 
                        echo $project ? 
                            "project-details.php?id={$project['id']}#seo-logs" : 
                            "projects.php"; 
                    ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $log ? 'Update Log' : 'Add Log'; ?>
                    </button>
                </div>
            </form>
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