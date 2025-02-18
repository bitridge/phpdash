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
        if (strlen($_POST['password']) < 6) {
            $error = 'Password must be at least 6 characters long';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $user ? 'Edit' : 'Add'; ?> User - SEO Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'templates/navigation.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo $user ? 'Edit' : 'Add'; ?> User</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
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
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <?php echo $user ? 'New Password (leave blank to keep current)' : 'Password'; ?>
                                </label>
                                <input type="password" class="form-control" id="password" name="password"
                                       <?php echo $user ? '' : 'required'; ?>>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                       <?php echo $user ? '' : 'required'; ?>>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $user ? 'Update' : 'Add'; ?> User
                                </button>
                                <a href="users.php" class="btn btn-secondary">Cancel</a>
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