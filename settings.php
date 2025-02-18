<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/settings.php';

// Require admin access
requireAdmin();

// Initialize settings
$settings = Settings::getInstance();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['app_settings'])) {
        // Handle application settings
        $settings->set('app_name', $_POST['app_name'] ?? 'SEO Dashboard');
        
        // Handle logo upload
        if (isset($_FILES['app_logo']) && $_FILES['app_logo']['size'] > 0) {
            $logoPath = $settings->uploadLogo($_FILES['app_logo']);
            if ($logoPath) {
                $settings->set('app_logo', $logoPath);
            } else {
                $error = 'Failed to upload logo';
            }
        }
        
        $success = 'Application settings updated successfully';
    } elseif (isset($_POST['smtp_settings'])) {
        // Handle SMTP settings
        $smtpFields = [
            'mail_server_type' => 'smtp',
            'smtp_host' => '',
            'smtp_port' => '',
            'smtp_username' => '',
            'smtp_encryption' => 'tls',
            'smtp_from_email' => '',
            'smtp_from_name' => ''
        ];
        
        foreach ($smtpFields as $field => $default) {
            $settings->set($field, $_POST[$field] ?? $default, 'smtp');
        }
        
        // Only update password if provided
        if (!empty($_POST['smtp_password'])) {
            $settings->set('smtp_password', $_POST['smtp_password'], 'smtp');
        }
        
        $success = 'SMTP settings updated successfully';
    } elseif (isset($_POST['company_settings'])) {
        // Handle company settings
        $companyFields = [
            'company_name' => '',
            'company_address' => '',
            'company_email' => '',
            'company_phone' => '',
            'company_url' => '',
            'company_contact_email' => ''
        ];
        
        foreach ($companyFields as $field => $default) {
            $settings->set($field, $_POST[$field] ?? $default, 'company');
        }
        
        $success = 'Company settings updated successfully';
    } elseif (isset($_POST['create_backup'])) {
        // Handle database backup
        $backupPath = $settings->createDatabaseBackup();
        if ($backupPath) {
            // Stream the file to the user
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($backupPath) . '"');
            header('Content-Length: ' . filesize($backupPath));
            readfile($backupPath);
            unlink($backupPath); // Delete the file after download
            exit();
        } else {
            $error = 'Failed to create backup';
        }
    }
}

// Get current settings
$appSettings = $settings->getByGroup('general');
$companySettings = $settings->getByGroup('company');
$smtpSettings = $settings->getByGroup('smtp');

// Set page title
$pageTitle = 'Settings';

// Include header
include 'templates/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1>Settings</h1>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <!-- Settings Tabs -->
        <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="app-tab" data-bs-toggle="tab" href="#app" role="tab">
                    Application Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="company-tab" data-bs-toggle="tab" href="#company" role="tab">
                    Company Details
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="smtp-tab" data-bs-toggle="tab" href="#smtp" role="tab">
                    Email Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="backup-tab" data-bs-toggle="tab" href="#backup" role="tab">
                    Database Backup
                </a>
            </li>
        </ul>
        
        <!-- Tab Content -->
        <div class="tab-content" id="settingsTabContent">
            <!-- Application Settings -->
            <div class="tab-pane fade show active" id="app" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="app_settings" value="1">
                            
                            <div class="mb-3">
                                <label for="app_name" class="form-label">Application Name</label>
                                <input type="text" class="form-control" id="app_name" name="app_name" required
                                       value="<?php echo htmlspecialchars($settings->get('app_name', 'SEO Dashboard')); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="app_logo" class="form-label">Application Logo</label>
                                <?php if ($logo = $settings->get('app_logo')): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo htmlspecialchars($logo); ?>" 
                                             alt="Current Logo" style="max-height: 100px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="app_logo" name="app_logo" accept="image/*">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Application Settings</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Company Details -->
            <div class="tab-pane fade" id="company" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="company_settings" value="1">
                            
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" required
                                       value="<?php echo htmlspecialchars($settings->get('company_name', '')); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="company_address" class="form-label">Address</label>
                                <textarea class="form-control" id="company_address" name="company_address" rows="3"><?php 
                                    echo htmlspecialchars($settings->get('company_address', '')); 
                                ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="company_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="company_email" name="company_email"
                                               value="<?php echo htmlspecialchars($settings->get('company_email', '')); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="company_phone" class="form-label">Phone</label>
                                        <input type="tel" class="form-control" id="company_phone" name="company_phone"
                                               value="<?php echo htmlspecialchars($settings->get('company_phone', '')); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="company_url" class="form-label">Website URL</label>
                                <input type="url" class="form-control" id="company_url" name="company_url"
                                       value="<?php echo htmlspecialchars($settings->get('company_url', '')); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="company_contact_email" class="form-label">Contact Email</label>
                                <input type="email" class="form-control" id="company_contact_email" name="company_contact_email"
                                       value="<?php echo htmlspecialchars($settings->get('company_contact_email', '')); ?>">
                                <small class="text-muted">This email will be used for system notifications and contact forms.</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Company Details</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- SMTP Settings -->
            <div class="tab-pane fade" id="smtp" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" name="smtp_settings">
                            <input type="hidden" name="smtp_settings" value="1">
                            
                            <div class="mb-3">
                                <label for="mail_server_type" class="form-label">Mail Server Type</label>
                                <select class="form-select" id="mail_server_type" name="mail_server_type">
                                    <option value="smtp" <?php echo $settings->get('mail_server_type', 'smtp') === 'smtp' ? 'selected' : ''; ?>>External SMTP Server</option>
                                    <option value="local" <?php echo $settings->get('mail_server_type', 'smtp') === 'local' ? 'selected' : ''; ?>>Local Mail Server</option>
                                </select>
                                <div class="form-text">Choose between external SMTP server or local mail server (PHP mail function)</div>
                            </div>
                            
                            <div id="smtp-settings" class="<?php echo $settings->get('mail_server_type', 'smtp') === 'local' ? 'd-none' : ''; ?>">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="smtp_host" class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                                               value="<?php echo htmlspecialchars($settings->get('smtp_host', '')); ?>"
                                               placeholder="e.g., smtp.gmail.com">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="smtp_port" class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                                               value="<?php echo htmlspecialchars($settings->get('smtp_port', '587')); ?>"
                                               placeholder="587">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="smtp_username" class="form-label">SMTP Username</label>
                                        <input type="text" class="form-control" id="smtp_username" name="smtp_username"
                                               value="<?php echo htmlspecialchars($settings->get('smtp_username', '')); ?>"
                                               placeholder="your-email@example.com">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="smtp_password" class="form-label">SMTP Password</label>
                                        <input type="password" class="form-control" id="smtp_password" name="smtp_password"
                                               placeholder="Leave blank to keep existing password">
                                        <small class="text-muted">For Gmail, use an App Password if 2FA is enabled.</small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="smtp_encryption" class="form-label">Encryption</label>
                                    <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                        <option value="tls" <?php echo $settings->get('smtp_encryption', 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo $settings->get('smtp_encryption', 'tls') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        <option value="none" <?php echo $settings->get('smtp_encryption', 'tls') === 'none' ? 'selected' : ''; ?>>None</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="smtp_from_email" class="form-label">From Email</label>
                                    <input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email"
                                           value="<?php echo htmlspecialchars($settings->get('smtp_from_email', '')); ?>"
                                           placeholder="noreply@yourdomain.com">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="smtp_from_name" class="form-label">From Name</label>
                                    <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name"
                                           value="<?php echo htmlspecialchars($settings->get('smtp_from_name', 'SEO Dashboard')); ?>"
                                           placeholder="SEO Dashboard">
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">Save Email Settings</button>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-info" id="testSmtp">Test Connection</button>
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#testEmailModal">Send Test Email</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Test Email Modal -->
            <div class="modal fade" id="testEmailModal" tabindex="-1" aria-labelledby="testEmailModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="testEmailModalLabel">Send Test Email</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="testEmailForm" onsubmit="return false;">
                                <div class="mb-3">
                                    <label for="test_email" class="form-label">Recipient Email</label>
                                    <input type="email" class="form-control" id="test_email" name="test_email" required
                                           value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="test_subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="test_subject" name="test_subject" 
                                           value="Test Email from SEO Dashboard" required>
                                </div>
                                <div class="mb-3">
                                    <label for="test_message" class="form-label">Message</label>
                                    <textarea class="form-control" id="test_message" name="test_message" rows="3" required>This is a test email from your SEO Dashboard application.</textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="sendTestEmail">Send Test Email</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Backup -->
            <div class="tab-pane fade" id="backup" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Database Backup</h5>
                        <p class="card-text">
                            Create and download a backup of your database. The backup will include all tables and data in a compressed ZIP file.
                        </p>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            The backup file will contain:
                            <ul class="mb-0">
                                <li>Complete database structure</li>
                                <li>All table data</li>
                                <li>Timestamp of backup creation</li>
                            </ul>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="create_backup" value="1">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-download"></i> Create & Download Backup
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add this before the closing </div> of the main container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<script>
function showToast(message, type = 'success') {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    // Create toast content
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // Add toast to container
    toastContainer.appendChild(toast);
    
    // Initialize and show toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 3000
    });
    bsToast.show();
    
    // Remove toast element after it's hidden
    toast.addEventListener('hidden.bs.toast', function () {
        toast.remove();
    });
}

// Test SMTP Connection
document.getElementById('testSmtp')?.addEventListener('click', function() {
    const form = this.closest('form');
    if (!form) {
        showToast('Form not found', 'danger');
        return;
    }
    const formData = new FormData(form);
    
    fetch('test_smtp.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new TypeError("Expected JSON response but got " + contentType);
        }
        return response.json();
    })
    .then(data => {
        showToast(data.message, data.success ? 'success' : 'danger');
        console.log('Debug info:', data.debug);
    })
    .catch(error => {
        showToast('Failed to test connection: ' + error.message, 'danger');
        console.error('Error:', error);
    });
});

// Send Test Email
document.getElementById('sendTestEmail')?.addEventListener('click', function() {
    const smtpForm = document.querySelector('form[name="smtp_settings"]');
    const testEmailForm = document.getElementById('testEmailForm');
    
    if (!smtpForm || !testEmailForm) {
        console.error('SMTP Form:', smtpForm);
        console.error('Test Email Form:', testEmailForm);
        showToast('Required forms not found', 'danger');
        return;
    }
    
    // Validate test email form
    if (!testEmailForm.checkValidity()) {
        testEmailForm.reportValidity();
        return;
    }
    
    // Create a new FormData object
    const formData = new FormData();
    
    // Add SMTP settings
    for (const pair of new FormData(smtpForm).entries()) {
        formData.append(pair[0], pair[1]);
    }
    
    // Add test email data
    formData.append('test_email', testEmailForm.querySelector('[name="test_email"]').value);
    formData.append('test_subject', testEmailForm.querySelector('[name="test_subject"]').value);
    formData.append('test_message', testEmailForm.querySelector('[name="test_message"]').value);
    formData.append('send_test_email', '1');
    
    // Show loading state
    const sendButton = this;
    const originalText = sendButton.innerHTML;
    sendButton.disabled = true;
    sendButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
    
    fetch('test_smtp.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new TypeError("Expected JSON response but got " + contentType);
        }
        return response.json();
    })
    .then(data => {
        showToast(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            const modal = document.getElementById('testEmailModal');
            if (modal) {
                bootstrap.Modal.getInstance(modal)?.hide();
            }
        }
        if (data.debug) {
            console.log('Debug info:', data.debug);
        }
    })
    .catch(error => {
        showToast('Failed to send test email: ' + error.message, 'danger');
        console.error('Error:', error);
    })
    .finally(() => {
        // Reset button state
        sendButton.disabled = false;
        sendButton.innerHTML = originalText;
    });
});

// Toggle SMTP settings visibility
document.querySelector('[name="mail_server_type"]')?.addEventListener('change', function() {
    const smtpSettings = document.getElementById('smtp-settings');
    if (smtpSettings) {
        smtpSettings.style.display = this.value === 'smtp' ? 'block' : 'none';
    }
});

// Initialize tooltips
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(tooltip => {
    new bootstrap.Tooltip(tooltip);
});

// Initialize any existing mail server type selection
const mailServerType = document.querySelector('[name="mail_server_type"]');
if (mailServerType) {
    const smtpSettings = document.getElementById('smtp-settings');
    if (smtpSettings) {
        smtpSettings.style.display = mailServerType.value === 'smtp' ? 'block' : 'none';
    }
}
</script>

<?php include 'templates/footer.php'; ?> 