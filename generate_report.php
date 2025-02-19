<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/project.php';
require_once 'includes/seo_log.php';

// Require login
requireLogin();

// Get project ID and date range parameters
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$project = getProject($projectId);

if (!$project) {
    header('Location: projects.php');
    exit();
}

// Get logs within the specified date range
$logs = getSeoLogsByDateRange($project['id'], $startDate, $endDate);

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
                               value="<?php echo 'Monthly SEO Progress Report - ' . date('F Y', strtotime($startDate)) . (date('F Y', strtotime($startDate)) != date('F Y', strtotime($endDate)) ? ' to ' . date('F Y', strtotime($endDate)) : ''); ?>">
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
                    <!-- Date Filter -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo htmlspecialchars($startDate); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary w-100" id="filterButton">
                            <i class="bi bi-funnel me-2"></i>Filter Logs
                        </button>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="select-all-logs">
                            <label class="form-check-label" for="select-all-logs">
                                <strong>Select All Logs</strong>
                            </label>
                        </div>
                    </div>
                    
                    <div class="seo-logs-list">
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
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
                        <?php else: ?>
                            <p class="text-muted">No logs found in the selected date range</p>
                        <?php endif; ?>
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
                    <div class="section-editor" data-index="{index}" style="height: 150px;"></div>
                    <input type="hidden" name="sections[{index}][content]" class="section-content-input">
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
    const editors = new Map();

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
        
        editors.set(sectionIndex, editor);
        sectionIndex++;
    });

    // Remove section
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-section')) {
            const sectionItem = e.target.closest('.section-item');
            const editorContainer = sectionItem.querySelector('.section-editor');
            const index = editorContainer.dataset.index;
            editors.delete(parseInt(index));
            sectionItem.remove();
        }
    });

    // Select all logs
    document.getElementById('select-all-logs').addEventListener('change', function(e) {
        document.querySelectorAll('.log-checkbox').forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });

    // Form submission
    document.getElementById('reportForm').addEventListener('submit', function(e) {
        // Capture main description
        document.getElementById('report_description').value = 
            reportDescriptionEditor.root.innerHTML;
        
        // Capture section contents
        document.querySelectorAll('.section-item').forEach(section => {
            const editorContainer = section.querySelector('.section-editor');
            const index = editorContainer.dataset.index;
            const editor = editors.get(parseInt(index));
            const contentInput = section.querySelector('.section-content-input');
            
            if (editor && contentInput) {
                contentInput.value = editor.root.innerHTML;
            }
        });
    });

    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const reportTitleInput = document.getElementById('report_title');
    const selectAllCheckbox = document.getElementById('select-all-logs');
    const logCheckboxes = document.querySelectorAll('.log-checkbox');
    const logsListContainer = document.querySelector('.seo-logs-list');
    
    // Function to update report title based on date range
    function updateReportTitle() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        const startMonth = startDate.toLocaleString('default', { month: 'long', year: 'numeric' });
        const endMonth = endDate.toLocaleString('default', { month: 'long', year: 'numeric' });
        
        let title = 'Monthly SEO Progress Report - ' + startMonth;
        if (startMonth !== endMonth) {
            title += ' to ' + endMonth;
        }
        
        // Only update if the title hasn't been manually edited
        if (!reportTitleInput.dataset.edited) {
            reportTitleInput.value = title;
        }
    }

    // Track if title has been manually edited
    reportTitleInput.addEventListener('input', function() {
        reportTitleInput.dataset.edited = 'true';
    });

    // Update title when dates change
    startDateInput.addEventListener('change', updateReportTitle);
    endDateInput.addEventListener('change', updateReportTitle);
    
    // Handle date filter changes
    async function handleDateChange() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const projectId = <?php echo $project['id']; ?>;
        
        if (!startDate || !endDate) {
            alert('Please select both start and end dates');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            alert('Start date cannot be later than end date');
            return;
        }

        // Update report title
        updateReportTitle();

        try {
            // Show loading state
            logsListContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

            // Fetch filtered logs
            const response = await fetch(`ajax/get_filtered_logs.php?project_id=${projectId}&start_date=${startDate}&end_date=${endDate}`);
            const logs = await response.json();

            // Update logs list
            if (logs.length > 0) {
                logsListContainer.innerHTML = logs.map(log => `
                    <div class="form-check mb-2">
                        <input class="form-check-input log-checkbox" type="checkbox" 
                               name="selected_logs[]" value="${log.id}" 
                               id="log-${log.id}">
                        <label class="form-check-label" for="log-${log.id}">
                            <span class="badge bg-${getLogTypeClass(log.log_type)} me-2">
                                ${log.log_type}
                            </span>
                            ${formatDate(log.log_date)}
                        </label>
                    </div>
                `).join('');

                // Reinitialize checkbox handlers
                initializeCheckboxHandlers();
            } else {
                logsListContainer.innerHTML = '<p class="text-muted">No logs found in the selected date range</p>';
            }
        } catch (error) {
            console.error('Error fetching logs:', error);
            logsListContainer.innerHTML = '<div class="alert alert-danger">Error loading logs. Please try again.</div>';
        }
    }

    // Helper function to format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    // Helper function to get log type class
    function getLogTypeClass(type) {
        const classes = {
            'Technical': 'primary',
            'On-Page SEO': 'success',
            'Off-Page SEO': 'info',
            'Content': 'warning',
            'Analytics': 'secondary',
            'Other': 'dark'
        };
        return classes[type] || 'primary';
    }

    // Initialize checkbox handlers
    function initializeCheckboxHandlers() {
        const logCheckboxes = document.querySelectorAll('.log-checkbox');
        
        // Handle select all checkbox
        selectAllCheckbox.addEventListener('change', function() {
            logCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Update select all checkbox state when individual checkboxes change
        logCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(logCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(logCheckboxes).some(cb => cb.checked);
                
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            });
        });
    }
    
    // Add click handler for filter button
    document.getElementById('filterButton').addEventListener('click', handleDateChange);
    
    // Initialize checkbox handlers on page load
    initializeCheckboxHandlers();
});
</script>

<?php include 'templates/footer.php'; ?> 