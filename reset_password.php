<?php
require_once 'config.php';
require_once 'includes/password_reset.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Validate token
if (!$token || !validateResetToken($token)) {
    $error = 'Invalid or expired reset link. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        if (resetPassword($token, $password)) {
            $success = 'Password has been reset successfully. You can now login with your new password.';
        } else {
            $error = 'Failed to reset password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SEO Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Reset Password</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                                <?php if (strpos($error, 'Invalid or expired') !== false): ?>
                                    <div class="mt-2">
                                        <a href="forgot_password.php" class="btn btn-outline-danger btn-sm">Request New Reset Link</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success); ?>
                                <div class="mt-2">
                                    <a href="login.php" class="btn btn-success btn-sm">Go to Login</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$error && !$success): ?>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?token=' . urlencode($token)); ?>">
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               required minlength="8">
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
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Reset Password</button>
                                    <a href="login.php" class="btn btn-outline-secondary">Back to Login</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.getElementById('generatePassword')?.addEventListener('click', function() {
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 