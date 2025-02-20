<?php
// Prevent any direct HTML error output
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set JSON content type header early
header('Content-Type: application/json');

try {
    require_once '../config.php';
    require_once '../includes/auth.php';
    require_once '../includes/mailer.php';
    require_once '../includes/ErrorLogger.php';

    // Require login
    requireLogin();

    // Initialize response array
    $response = [
        'success' => false,
        'message' => '',
        'debug' => []
    ];

    // Get test email parameters
    $testEmail = $_POST['test_email'] ?? '';
    $testSubject = $_POST['test_subject'] ?? '';
    $testMessage = $_POST['test_message'] ?? '';

    if (empty($testEmail) || empty($testSubject) || empty($testMessage)) {
        throw new Exception('Missing required test email parameters');
    }

    // Validate email settings
    if (empty($_POST['smtp_host']) || empty($_POST['smtp_port']) || empty($_POST['smtp_from_email'])) {
        throw new Exception('Please configure and save email settings first');
    }

    // Get settings instance
    $settings = Settings::getInstance();
    
    // Store original settings
    $originalSettings = [
        'mail_server_type' => $settings->get('mail_server_type'),
        'smtp_host' => $settings->get('smtp_host'),
        'smtp_port' => $settings->get('smtp_port'),
        'smtp_username' => $settings->get('smtp_username'),
        'smtp_encryption' => $settings->get('smtp_encryption'),
        'smtp_from_email' => $settings->get('smtp_from_email'),
        'smtp_from_name' => $settings->get('smtp_from_name')
    ];

    // Temporarily update settings for testing
    $settings->set('mail_server_type', $_POST['mail_server_type'] ?? 'smtp', 'smtp');
    $settings->set('smtp_host', $_POST['smtp_host'] ?? '', 'smtp');
    $settings->set('smtp_port', $_POST['smtp_port'] ?? '587', 'smtp');
    $settings->set('smtp_username', $_POST['smtp_username'] ?? '', 'smtp');
    $settings->set('smtp_encryption', $_POST['smtp_encryption'] ?? 'tls', 'smtp');
    $settings->set('smtp_from_email', $_POST['smtp_from_email'] ?? '', 'smtp');
    $settings->set('smtp_from_name', $_POST['smtp_from_name'] ?? 'SEO Dashboard', 'smtp');
    
    // Update password only if provided
    if (!empty($_POST['smtp_password'])) {
        $settings->set('smtp_password', $_POST['smtp_password'], 'smtp');
    }

    // Get mailer instance (it will use the updated settings)
    $mailer = Mailer::getInstance();

    // Log connection attempt
    $logger = ErrorLogger::getInstance();
    $logger->log("Testing email connection with settings: " . json_encode([
        'host' => $_POST['smtp_host'],
        'port' => $_POST['smtp_port'],
        'encryption' => $_POST['smtp_encryption'],
        'from_email' => $_POST['smtp_from_email'],
        'from_name' => $_POST['smtp_from_name']
    ]), 'DEBUG');

    // Test connection
    if (!$mailer->testConnection()) {
        throw new Exception('Failed to connect to mail server: ' . $mailer->getLastError());
    }

    // Send test email
    $result = $mailer->send($testEmail, $testSubject, $testMessage);
    
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Test email sent successfully!';
    } else {
        throw new Exception('Failed to send test email: ' . $mailer->getLastError());
    }

    // Add debug information
    $response['debug'] = [
        'connection' => [
            'host' => $_POST['smtp_host'],
            'port' => $_POST['smtp_port'],
            'encryption' => $_POST['smtp_encryption'],
            'username' => $_POST['smtp_username'],
            'auth_method' => 'SMTP',
        ],
        'email' => [
            'from' => $_POST['smtp_from_email'],
            'from_name' => $_POST['smtp_from_name'],
            'to' => $testEmail,
            'subject' => $testSubject,
        ],
        'server_response' => $mailer->getLastError() ?: 'OK',
        'timestamp' => date('Y-m-d H:i:s')
    ];

} catch (Throwable $e) {
    // Catch all errors and exceptions
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'type' => get_class($e),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    // Log the error if logger is available
    if (isset($logger)) {
        $logger->log("Email test failed: " . $e->getMessage(), 'ERROR');
    }
} finally {
    // Restore original settings
    if (isset($originalSettings)) {
        foreach ($originalSettings as $key => $value) {
            $settings->set($key, $value, 'smtp');
        }
    }
}

// Ensure clean output buffer
while (ob_get_level()) ob_end_clean();

// Send JSON response
echo json_encode($response, JSON_PRETTY_PRINT); 