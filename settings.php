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
    }
}

// Get current settings
$appSettings = $settings->getByGroup('general');
$companySettings = $settings->getByGroup('company');

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
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?> 