<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/user.php';

// Require login
requireLogin();

$error = '';
$success = '';
$user = getUser($_SESSION['user_id']);

if (!$user) {
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile
        $data = [
            'name' => $_POST['name'],
            'email' => $_POST['email']
        ];
        
        $result = updateUser($user['id'], $data);
        if ($result['success']) {
            $_SESSION['user_name'] = $data['name'];
            $_SESSION['user_email'] = $data['email'];
            $success = 'Profile updated successfully';
            $user = getUser($user['id']); // Refresh user data
        } else {
            $error = $result['message'];
        }
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters long';
        } else {
            $data = [
                'password' => $newPassword
            ];
            
            $result = updateUser($user['id'], $data);
            if ($result['success']) {
                $success = 'Password changed successfully';
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Set page title
$pageTitle = 'My Profile';

// Include header
include 'templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">My Profile</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <!-- Profile Information -->
                <div class="mb-5">
                    <h4>Profile Information</h4>
                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   value="<?php echo htmlspecialchars($user['name']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst($user['role'])); ?>" readonly>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div>
                    <h4>Change Password</h4>
                    <form method="POST" action="" id="passwordForm">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" 
                                       name="new_password" required minlength="8">
                                <button type="button" class="btn btn-outline-secondary" id="generatePassword">
                                    <i class="bi bi-key"></i> Generate
                                </button>
                            </div>
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required minlength="8">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
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
    document.getElementById('new_password').value = password;
    document.getElementById('confirm_password').value = password;
    
    // Show a temporary alert with the generated password
    alert('Generated password: ' + password + '\nPlease save this password securely.');
});
</script>

<?php include 'templates/footer.php'; ?> 