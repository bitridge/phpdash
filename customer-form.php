<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/customer.php';

// Require login
requireLogin();

$error = '';
$success = '';
$customer = null;

// Check if editing
if (isset($_GET['id'])) {
    $customer = getCustomer($_GET['id']);
    if (!$customer) {
        header('Location: customers.php');
        exit();
    }
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
                $success = 'Customer updated successfully';
                $customer = getCustomer($customer['id']); // Refresh data
            } else {
                $error = 'Failed to update customer';
            }
        } else {
            // Create new customer
            if (createCustomer($data)) {
                header('Location: customers.php');
                exit();
            } else {
                $error = 'Failed to create customer';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $customer ? 'Edit' : 'Add'; ?> Customer - SEO Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">SEO Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="customers.php">Customers</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo $customer ? 'Edit' : 'Add'; ?> Customer</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
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
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 