<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/project.php';
require_once 'includes/seo_log.php';

// Require login
requireLogin();

// Get project ID
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$project = getProject($projectId);

if (!$project) {
    header('Location: projects.php');
    exit();
}

// Get all SEO logs for this project
$logsResult = getSeoLogs($project['id'], 1, PHP_INT_MAX);

// Set page title
$pageTitle = 'Generate Report - ' . $project['project_name'];

// Include header
include 'templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>Generate Report</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="projects.php">Projects</a></li>
                <li class="breadcrumb-item">
                    <a href="project-details.php?id=<?php echo $project['id']; ?>">
                        <?php echo htmlspecialchars($project['project_name']); ?>
                    </a>
                </li>
                <li class="breadcrumb-item active">Generate Report</li>
            </ol>
        </nav>
    </div>
</div>

<form id="reportForm" method="POST" action="generate_pdf.php" enctype="multipart/form-data">
    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
    
    <div class="row">
        <div class="col-md-8">
            <!-- Report Details Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Report Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="report_title" class="form-label">Report Title</label>
                        <input type="text" class="form-control" id="report_title" name="report_title" required
                               placeholder="e.g., Monthly SEO Progress Report - <?php echo date('F Y'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="report_description" class="form-label">Report Description</label>
                        <div id="report_description_editor" style="height: 200px;"></div>
                        <input type="hidden" name="report_description" id="report_description">
                    </div>
                </div>
            </div>

            <!-- Custom Sections -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Custom Sections</h5>
                    <button type="button" class="btn btn-success" id="addSection">
                        <i class="bi bi-plus-circle me-2"></i>Add Section
                    </button>
                </div>
                <div class="card-body">
                    <div id="sections-container">
                        <!-- Sections will be added here dynamically -->
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- SEO Logs Selection Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Include SEO Logs</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="select-all-logs">
                            <label class="form-check-label" for="select-all-logs">
                                <strong>Select All Logs</strong>
                            </label>
                        </div>
                    </div>
                    <div class="seo-logs-list">
                        <?php foreach ($logsResult['logs'] as $log): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input log-checkbox" type="checkbox" 
                                       name="selected_logs[]" value="<?php echo $log['id']; ?>" 
                                       id="log-<?php echo $log['id']; ?>">
                                <label class="form-check-label" for="log-<?php echo $log['id']; ?>">
                                    <span class="badge bg-<?php echo getLogTypeClass($log['log_type']); ?> me-2">
                                        <?php echo htmlspecialchars($log['log_type']); ?>
                                    </span>
                                    <?php echo date('M j, Y', strtotime($log['log_date'])); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Generate Button -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-file-pdf me-2"></i>Generate PDF Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Section Template -->
<template id="section-template">
    <div class="section-item mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <input type="text" class="form-control form-control-lg border-0 bg-transparent w-75" 
                       name="sections[{index}][title]" placeholder="Section Title" required>
                <button type="button" class="btn btn-danger btn-sm remove-section">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="section-editor" style="height: 150px;"></div>
                    <input type="hidden" name="sections[{index}][content]">
                </div>
                <div class="mb-3">
                    <label class="form-label">Section Image</label>
                    <input type="file" class="form-control" name="sections[{index}][image]" accept="image/*">
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Include Quill -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize main description editor
    var reportDescriptionEditor = new Quill('#report_description_editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['clean']
            ]
        }
    });

    // Section management
    let sectionIndex = 0;
    const sectionsContainer = document.getElementById('sections-container');
    const sectionTemplate = document.getElementById('section-template');
    const editors = [];

    // Make sections sortable
    new Sortable(sectionsContainer, {
        animation: 150,
        handle: '.card-header'
    });

    // Add section
    document.getElementById('addSection').addEventListener('click', function() {
        const sectionContent = sectionTemplate.content.cloneNode(true);
        const sectionHtml = sectionContent.querySelector('.section-item');
        
        // Replace placeholder index
        sectionHtml.innerHTML = sectionHtml.innerHTML.replace(/{index}/g, sectionIndex);
        
        sectionsContainer.appendChild(sectionHtml);
        
        // Initialize Quill editor for this section
        const editorContainer = sectionHtml.querySelector('.section-editor');
        const editor = new Quill(editorContainer, {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['clean']
                ]
            }
        });
        editors.push(editor);
        
        sectionIndex++;
    });

    // Remove section
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-section')) {
            e.target.closest('.section-item').remove();
        }
    });

    // Select all logs
    document.getElementById('select-all-logs').addEventListener('change', function(e) {
        document.querySelectorAll('.log-checkbox').forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });

    // Form submission
    document.getElementById('reportForm').addEventListener('submit', function() {
        // Capture main description
        document.getElementById('report_description').value = 
            reportDescriptionEditor.root.innerHTML;
        
        // Capture section contents
        document.querySelectorAll('.section-item').forEach((section, index) => {
            const editor = editors[index];
            const contentInput = section.querySelector('input[name$="[content]"]');
            contentInput.value = editor.root.innerHTML;
        });
    });
});
</script>

<?php include 'templates/footer.php'; ?> 