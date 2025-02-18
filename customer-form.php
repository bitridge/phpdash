<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/customer.php';
require_once 'includes/user.php';

// Require login
requireLogin();

$error = '';
$success = '';
$customer = null;

// Get all SEO providers
$seoProviders = getSeoProviders();

// Check if editing
if (isset($_GET['id'])) {
    $customer = getCustomer($_GET['id']);
    if (!$customer) {
        header('Location: customers.php');
        exit();
    }
    // Get assigned SEO providers
    $assignedProviders = getCustomerUsers($customer['id']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['name'] ?? '',
        'company_name' => $_POST['company_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'website_url' => $_POST['website_url'] ?? ''
    ];
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['size'] > 0) {
        $logo_path = uploadCustomerLogo($_FILES['logo']);
        if ($logo_path) {
            $data['logo_path'] = $logo_path;
        } else {
            $error = 'Failed to upload logo';
        }
    }
    
    if (!$error) {
        if ($customer) {
            // Update existing customer
            if (updateCustomer($customer['id'], $data)) {
                // Update SEO provider assignments
                updateCustomerUsers($customer['id'], $_POST['seo_providers'] ?? []);
                $success = 'Customer updated successfully';
                $customer = getCustomer($customer['id']); // Refresh data
                $assignedProviders = getCustomerUsers($customer['id']); // Refresh assignments
            } else {
                $error = 'Failed to update customer';
            }
        } else {
            // Create new customer
            $result = createCustomer($data);
            if ($result['success']) {
                // Add SEO provider assignments
                if (!empty($_POST['seo_providers'])) {
                    updateCustomerUsers($result['id'], $_POST['seo_providers']);
                }
                header('Location: customers.php');
                exit();
            } else {
                $error = 'Failed to create customer';
            }
        }
    }
}

// Set page title
$pageTitle = ($customer ? 'Edit' : 'Add') . ' Customer';

// Include header
include 'templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo $customer ? 'Edit' : 'Add'; ?> Customer</h1>
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
                        <label for="name" class="form-label">Contact Name</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?php echo $customer ? htmlspecialchars($customer['name']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Company Name</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" required
                               value="<?php echo $customer ? htmlspecialchars($customer['company_name']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo $customer ? htmlspecialchars($customer['email']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone"
                               value="<?php echo $customer ? htmlspecialchars($customer['phone']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="website_url" class="form-label">Website URL</label>
                        <input type="url" class="form-control" id="website_url" name="website_url" required
                               value="<?php echo $customer ? htmlspecialchars($customer['website_url']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="logo" class="form-label">Company Logo</label>
                        <?php if ($customer && $customer['logo_path']): ?>
                            <div class="mb-2">
                                <img src="<?php echo htmlspecialchars($customer['logo_path']); ?>" 
                                     alt="Current Logo" style="max-width: 100px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Assign SEO Providers</label>
                        <div class="card">
                            <div class="card-body">
                                <?php foreach ($seoProviders as $provider): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="seo_providers[]" 
                                               value="<?php echo $provider['id']; ?>"
                                               id="provider_<?php echo $provider['id']; ?>"
                                               <?php echo (isset($assignedProviders) && in_array($provider['id'], array_column($assignedProviders, 'user_id'))) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="provider_<?php echo $provider['id']; ?>">
                                            <?php echo htmlspecialchars($provider['name']); ?> 
                                            (<?php echo htmlspecialchars($provider['email']); ?>)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($seoProviders)): ?>
                                    <p class="text-muted mb-0">No SEO providers available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $customer ? 'Update' : 'Add'; ?> Customer
                        </button>
                        <a href="customers.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?> 