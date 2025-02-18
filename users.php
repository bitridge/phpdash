<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/user.php';

// Require admin access
requireAdmin();

// Set page title
$pageTitle = 'Users';

// Handle delete action
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $result = deleteUser($_POST['id']);
    if (!$result['success']) {
        $error = $result['message'];
    }
}

// Get current page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$result = getUsers($page);

// Include header
include 'templates/header.php';

// Helper function to format role display
function formatRole($role) {
    return $role === 'admin' ? 'Admin' : 'SEO Provider';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Users</h1>
    <a href="user-form.php" class="btn btn-primary">Add User</a>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['users'] as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                    <?php echo formatRole($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="user-form.php?id=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm btn-primary">Edit</a>
                                   
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" action="" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($result['users'])): ?>
                        <tr>
                            <td colspan="5" class="text-center">No users found</td>
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