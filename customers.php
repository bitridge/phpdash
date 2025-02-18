<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/customer.php';

// Set page title
$pageTitle = 'Customers';

// Handle delete action
if (isset($_POST['delete']) && isset($_POST['id'])) {
    deleteCustomer($_POST['id']);
    header('Location: customers.php');
    exit();
}

// Get current page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$result = getCustomers($page);

// Include header
include 'templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Customers</h1>
    <a href="customer-form.php" class="btn btn-primary">Add Customer</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Website</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['customers'] as $customer): ?>
                        <tr>
                            <td>
                                <?php if ($customer['logo_path']): ?>
                                    <img src="<?php echo htmlspecialchars($customer['logo_path']); ?>" 
                                         alt="Logo" class="me-2" style="width: 30px; height: 30px; object-fit: contain;">
                                <?php endif; ?>
                                <a href="customer-details.php?id=<?php echo $customer['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($customer['name']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($customer['company_name']); ?></td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>">
                                    <?php echo htmlspecialchars($customer['email']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($customer['phone']): ?>
                                    <a href="tel:<?php echo htmlspecialchars($customer['phone']); ?>">
                                        <?php echo htmlspecialchars($customer['phone']); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($customer['website_url']): ?>
                                    <a href="<?php echo htmlspecialchars($customer['website_url']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($customer['website_url']); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($customer['created_by_name']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="customer-details.php?id=<?php echo $customer['id']; ?>" 
                                       class="btn btn-sm btn-info me-1">View</a>
                                    <a href="customer-form.php?id=<?php echo $customer['id']; ?>" 
                                       class="btn btn-sm btn-primary me-1">Edit</a>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                        <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($result['customers'])): ?>
                        <tr>
                            <td colspan="6" class="text-center">No customers found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($result['pages'] > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php include 'templates/footer.php'; ?> 