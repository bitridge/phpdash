<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/settings.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Ensure we're outputting JSON
header('Content-Type: application/json');

// Error handler to catch any PHP errors and return them as JSON
function handleError($errno, $errstr, $errfile, $errline) {
    $response = [
        'success' => false,
        'message' => 'PHP Error: ' . $errstr,
        'debug' => ["Error in $errfile on line $errline"]
    ];
    echo json_encode($response);
    exit;
}
set_error_handler('handleError');

// Exception handler
function handleException($e) {
    $response = [
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage(),
        'debug' => [$e->getTraceAsString()]
    ];
    echo json_encode($response);
    exit;
}
set_exception_handler('handleException');

try {
    // Require admin access
    requireAdmin();

    // Initialize response array
    $response = [
        'success' => false,
        'message' => 'Failed to test connection',
        'debug' => [] // Array to store debug messages
    ];

    function addDebug(&$response, $message) {
        $response['debug'][] = $message;
        error_log($message);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $mail = new PHPMailer(true);
        
        // Enable debug output
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) use (&$response) {
            addDebug($response, "PHPMailer Debug: $str");
        };
        
        // Common settings
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->XMailer = 'PHPMailer';
        
        // Log settings being used
        addDebug($response, "Mail Server Type: " . ($_POST['mail_server_type'] ?? 'not set'));
        addDebug($response, "SMTP Host: " . ($_POST['smtp_host'] ?? 'not set'));
        addDebug($response, "SMTP Port: " . ($_POST['smtp_port'] ?? 'not set'));
        addDebug($response, "SMTP Username: " . ($_POST['smtp_username'] ?? 'not set'));
        addDebug($response, "SMTP Encryption: " . ($_POST['smtp_encryption'] ?? 'not set'));
        addDebug($response, "From Email: " . ($_POST['smtp_from_email'] ?? 'not set'));
        
        // Set mailer type based on settings
        if ($_POST['mail_server_type'] === 'smtp') {
            $mail->isSMTP();
            $mail->Host = $_POST['smtp_host'];
            $mail->Port = (int)$_POST['smtp_port'];
            $mail->SMTPAuth = true;
            
            // Get password from POST or settings
            $password = !empty($_POST['smtp_password']) ? 
                $_POST['smtp_password'] : 
                Settings::getInstance()->get('smtp_password', '');
            
            $mail->Username = $_POST['smtp_username'];
            $mail->Password = $password;
            
            // Set timeout
            $mail->Timeout = 20; // Timeout in seconds
            
            // Set encryption
            if ($_POST['smtp_encryption'] === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                addDebug($response, "Using TLS encryption");
            } elseif ($_POST['smtp_encryption'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                addDebug($response, "Using SSL encryption");
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
                addDebug($response, "No encryption specified");
            }
            
            // Enable verbose debug output for SMTP
            $mail->SMTPKeepAlive = true;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
        } else {
            $mail->isMail();
            addDebug($response, "Using PHP mail() function");
        }
        
        // Set from address
        $mail->setFrom(
            $_POST['smtp_from_email'],
            $_POST['smtp_from_name']
        );
        
        // If test email data is provided, send a test email
        if (isset($_POST['test_email'])) {
            addDebug($response, "Attempting to send test email to: " . $_POST['test_email']);
            
            $mail->addAddress($_POST['test_email']);
            $mail->isHTML(true);
            $mail->Subject = $_POST['test_subject'];
            $mail->Body = $_POST['test_message'];
            $mail->AltBody = strip_tags($_POST['test_message']);
            
            if ($mail->send()) {
                addDebug($response, "Test email sent successfully");
                $response = [
                    'success' => true,
                    'message' => 'Test email sent successfully! Please check your inbox.',
                    'debug' => $response['debug']
                ];
            } else {
                addDebug($response, "Failed to send test email: " . $mail->ErrorInfo);
                $response['message'] = 'Failed to send test email: ' . $mail->ErrorInfo;
            }
        }
        // Otherwise, just test the connection
        else {
            if ($_POST['mail_server_type'] === 'smtp') {
                addDebug($response, "Testing SMTP connection...");
                if ($mail->smtpConnect()) {
                    addDebug($response, "SMTP connection successful");
                    $response = [
                        'success' => true,
                        'message' => 'SMTP connection successful! Your email settings are working.',
                        'debug' => $response['debug']
                    ];
                    $mail->smtpClose();
                } else {
                    addDebug($response, "SMTP connection failed: " . $mail->ErrorInfo);
                }
            } else {
                addDebug($response, "Local mail server test");
                $response = [
                    'success' => true,
                    'message' => 'Local mail server is ready to use.',
                    'debug' => $response['debug']
                ];
            }
        }
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [$e->getTraceAsString()]
    ];
}

// Ensure no output has been sent before
if (!headers_sent()) {
    header('Content-Type: application/json');
}

// Return JSON response
echo json_encode($response); 