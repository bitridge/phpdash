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
        
        // Handle timezone settings
        $settings->set('timezone', $_POST['timezone'] ?? 'UTC');
        $settings->set('date_format', $_POST['date_format'] ?? 'Y-m-d');
        $settings->set('time_format', $_POST['time_format'] ?? 'H:i:s');
        
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
        <!-- Nav tabs -->
        <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                    <i class="bi bi-gear me-1"></i> General Settings
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                    <i class="bi bi-envelope me-1"></i> Email Settings
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="timezone-tab" data-bs-toggle="tab" data-bs-target="#timezone" type="button" role="tab">
                    <i class="bi bi-clock me-1"></i> Timezone & Date
                </button>
            </li>
        </ul>
        
        <!-- Tab content -->
        <div class="tab-content">
            <!-- General Settings Tab -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">General Settings</h5>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="settings_group" value="general">
                            
                            <div class="mb-3">
                                <label for="app_name" class="form-label">Application Name</label>
                                <input type="text" class="form-control" id="app_name" name="app_name" 
                                       value="<?php echo htmlspecialchars($settings->get('app_name', 'SEO Dashboard')); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="app_logo" class="form-label">Application Logo</label>
                                <?php if ($logo = $settings->get('app_logo')): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo htmlspecialchars($logo); ?>" alt="Current Logo" style="max-height: 50px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="app_logo" name="app_logo" accept="image/*">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save General Settings</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Email Settings Tab -->
            <div class="tab-pane fade" id="email" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Email Settings</h5>
                        <form method="POST">
                            <input type="hidden" name="settings_group" value="email">
                            
                            <div class="mb-3">
                                <label for="mail_server_type" class="form-label">Mail Server Type</label>
                                <select class="form-select" id="mail_server_type" name="mail_server_type">
                                    <option value="smtp" <?php echo $settings->get('mail_server_type') === 'smtp' ? 'selected' : ''; ?>>SMTP Server</option>
                                    <option value="local" <?php echo $settings->get('mail_server_type') === 'local' ? 'selected' : ''; ?>>Local Mail Server</option>
                                </select>
                            </div>
                            
                            <div id="smtp_settings">
                                <div class="mb-3">
                                    <label for="smtp_host" class="form-label">SMTP Host</label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                           value="<?php echo htmlspecialchars($settings->get('smtp_host', '')); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="smtp_port" class="form-label">SMTP Port</label>
                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                           value="<?php echo htmlspecialchars($settings->get('smtp_port', '587')); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="smtp_username" class="form-label">SMTP Username</label>
                                    <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                                           value="<?php echo htmlspecialchars($settings->get('smtp_username', '')); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="smtp_password" class="form-label">SMTP Password</label>
                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                           placeholder="Leave blank to keep current password">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="smtp_encryption" class="form-label">Encryption</label>
                                    <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                        <option value="tls" <?php echo $settings->get('smtp_encryption') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo $settings->get('smtp_encryption') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        <option value="none" <?php echo $settings->get('smtp_encryption') === 'none' ? 'selected' : ''; ?>>None</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="smtp_from_email" class="form-label">From Email Address</label>
                                <input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email" 
                                       value="<?php echo htmlspecialchars($settings->get('smtp_from_email', '')); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="smtp_from_name" class="form-label">From Name</label>
                                <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name" 
                                       value="<?php echo htmlspecialchars($settings->get('smtp_from_name', 'SEO Dashboard')); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Email Settings</button>
                            
                            <button type="button" class="btn btn-info ms-2" id="testEmailBtn">
                                Test Email Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Timezone & Date Settings Tab -->
            <div class="tab-pane fade" id="timezone" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Timezone & Date Settings</h5>
                        <form method="POST">
                            <input type="hidden" name="settings_group" value="timezone">
                            
                            <div class="mb-3">
                                <label for="timezone" class="form-label">Timezone</label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <?php
                                    $current_timezone = $settings->get('timezone', 'UTC');
                                    $timezones = DateTimeZone::listIdentifiers();
                                    foreach ($timezones as $tz) {
                                        $selected = ($tz === $current_timezone) ? 'selected' : '';
                                        echo "<option value=\"$tz\" $selected>$tz</option>";
                                    }
                                    ?>
                                </select>
                                <div class="form-text">Select your preferred timezone for date and time display.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="date_format" class="form-label">Date Format</label>
                                <select class="form-select" id="date_format" name="date_format">
                                    <?php
                                    $current_format = $settings->get('date_format', 'Y-m-d');
                                    $date_formats = [
                                        'Y-m-d' => date('Y-m-d'),
                                        'd/m/Y' => date('d/m/Y'),
                                        'm/d/Y' => date('m/d/Y'),
                                        'F j, Y' => date('F j, Y'),
                                        'j F Y' => date('j F Y'),
                                        'd-m-Y' => date('d-m-Y'),
                                    ];
                                    foreach ($date_formats as $format => $example) {
                                        $selected = ($format === $current_format) ? 'selected' : '';
                                        echo "<option value=\"$format\" $selected>$example</option>";
                                    }
                                    ?>
                                </select>
                                <div class="form-text">Choose how dates should be displayed throughout the application.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="time_format" class="form-label">Time Format</label>
                                <select class="form-select" id="time_format" name="time_format">
                                    <?php
                                    $current_time_format = $settings->get('time_format', 'H:i:s');
                                    $time_formats = [
                                        'H:i:s' => date('H:i:s'),
                                        'H:i' => date('H:i'),
                                        'h:i:s A' => date('h:i:s A'),
                                        'h:i A' => date('h:i A'),
                                    ];
                                    foreach ($time_formats as $format => $example) {
                                        $selected = ($format === $current_time_format) ? 'selected' : '';
                                        echo "<option value=\"$format\" $selected>$example</option>";
                                    }
                                    ?>
                                </select>
                                <div class="form-text">Choose how times should be displayed throughout the application.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="show_timezone" name="show_timezone" 
                                           <?php echo $settings->get('show_timezone', true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="show_timezone">
                                        Show timezone indicator with timestamps
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Timezone Settings</button>
                        </form>
                        
                        <div class="mt-4">
                            <h6>Current Date/Time Preview:</h6>
                            <div class="alert alert-info">
                                <?php echo $settings->getCurrentDateTime(); ?>
                            </div>
                        </div>
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

// Toggle SMTP settings based on mail server type
document.getElementById('mail_server_type')?.addEventListener('change', function() {
    const smtpSettings = document.getElementById('smtp_settings');
    smtpSettings.style.display = this.value === 'smtp' ? 'block' : 'none';
});

// Initialize SMTP settings visibility
document.addEventListener('DOMContentLoaded', function() {
    const mailServerType = document.getElementById('mail_server_type');
    const smtpSettings = document.getElementById('smtp_settings');
    if (mailServerType && smtpSettings) {
        smtpSettings.style.display = mailServerType.value === 'smtp' ? 'block' : 'none';
    }
});

// Test Email Settings
document.getElementById('testEmailBtn')?.addEventListener('click', function() {
    const testEmail = prompt('Enter email address to send test message:');
    if (testEmail) {
        const formData = new FormData(this.closest('form'));
        formData.append('test_email', testEmail);
        formData.append('test_subject', 'Test Email from SEO Dashboard');
        formData.append('test_message', 'This is a test email from your SEO Dashboard application.');
        
        fetch('test_smtp.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            console.log('Debug info:', data.debug);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to test email settings. Check console for details.');
        });
    }
});

// Preview date/time format changes
function updateDateTimePreview() {
    const timezone = document.getElementById('timezone').value;
    const dateFormat = document.getElementById('date_format').value;
    const timeFormat = document.getElementById('time_format').value;
    
    fetch('ajax/get_current_time.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `timezone=${encodeURIComponent(timezone)}&date_format=${encodeURIComponent(dateFormat)}&time_format=${encodeURIComponent(timeFormat)}`
    })
    .then(response => response.text())
    .then(datetime => {
        document.querySelector('.alert-info').textContent = datetime;
    });
}

// Add event listeners for format changes
document.getElementById('timezone')?.addEventListener('change', updateDateTimePreview);
document.getElementById('date_format')?.addEventListener('change', updateDateTimePreview);
document.getElementById('time_format')?.addEventListener('change', updateDateTimePreview);
</script>

<?php include 'templates/footer.php'; ?> 