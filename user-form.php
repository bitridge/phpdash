<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/user.php';

// Require admin access
requireAdmin();

$error = '';
$success = '';
$user = null;

// Check if editing
if (isset($_GET['id'])) {
    $user = getUser($_GET['id']);
    if (!$user) {
        header('Location: users.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'role' => $_POST['role'] ?? 'seo_provider'
    ];
    
    // Add password if provided
    if (!empty($_POST['password'])) {
        if (strlen($_POST['password']) < 8) {
            $error = 'Password must be at least 8 characters long';
        } else if ($_POST['password'] !== $_POST['confirm_password']) {
            $error = 'Passwords do not match';
        } else {
            $data['password'] = $_POST['password'];
        }
    } else if (!$user) {
        // Require password for new users
        $error = 'Password is required';
    }
    
    if (!$error) {
        if ($user) {
            // Update existing user
            $result = updateUser($user['id'], $data);
            if ($result['success']) {
                $success = 'User updated successfully';
                $user = getUser($user['id']); // Refresh data
            } else {
                $error = $result['message'];
            }
        } else {
            // Create new user
            $result = createUser($data);
            if ($result['success']) {
                header('Location: users.php');
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Set page title
$pageTitle = ($user ? 'Edit' : 'Add') . ' User';

// Include header
include 'templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="card-title mb-0"><?php echo $user ? 'Edit' : 'Add'; ?> User</h2>
                    <a href="users.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Users
                    </a>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?php echo $user ? htmlspecialchars($user['name']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo $user ? htmlspecialchars($user['email']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="seo_provider" <?php echo ($user && $user['role'] === 'seo_provider') ? 'selected' : ''; ?>>
                                SEO Provider
                            </option>
                            <option value="admin" <?php echo ($user && $user['role'] === 'admin') ? 'selected' : ''; ?>>
                                Admin
                            </option>
                        </select>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i>
                            SEO Providers can manage their assigned projects and logs. Admins have full system access.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <?php echo $user ? 'New Password (leave blank to keep current)' : 'Password'; ?>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password"
                                   <?php echo $user ? '' : 'required'; ?> minlength="8">
                            <button type="button" class="btn btn-outline-secondary" id="generatePassword">
                                <i class="bi bi-key"></i> Generate
                            </button>
                        </div>
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                               <?php echo $user ? '' : 'required'; ?> minlength="8">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $user ? 'Update' : 'Add'; ?> User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('generatePassword').addEventListener('click', function() {
    // Generate a random password
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-+=";
    let password = "";
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    
    // Set the password in both fields
    document.getElementById('password').value = password;
    document.getElementById('confirm_password').value = password;
    
    // Copy to clipboard
    navigator.clipboard.writeText(password).then(() => {
        // Create and show toast notification
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed bottom-0 end-0 m-3';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-clipboard-check"></i> Password copied to clipboard!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        document.body.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
        bsToast.show();
        
        // Remove toast element after it's hidden
        toast.addEventListener('hidden.bs.toast', function () {
            document.body.removeChild(toast);
        });
    }).catch(err => {
        console.error('Failed to copy password: ', err);
        alert('Generated password: ' + password + '\nPlease copy it manually.');
    });
});
</script>

<!-- Add toast container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<?php include 'templates/footer.php'; ?> 