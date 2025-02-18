<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/settings.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require admin access
requireAdmin();

// Initialize response array
$response = [
    'success' => false,
    'message' => 'Failed to test connection'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mail = new PHPMailer(true);
        
        // Common settings
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Set mailer type based on settings
        if ($_POST['mail_server_type'] === 'smtp') {
            $mail->isSMTP();
            $mail->Host = $_POST['smtp_host'];
            $mail->Port = (int)$_POST['smtp_port'];
            $mail->SMTPAuth = true;
            $mail->Username = $_POST['smtp_username'];
            $mail->Password = !empty($_POST['smtp_password']) ? 
                $_POST['smtp_password'] : 
                Settings::getInstance()->get('smtp_password', '');
                
            // Set encryption
            if ($_POST['smtp_encryption'] === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($_POST['smtp_encryption'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }
        } else {
            $mail->isMail(); // Use PHP's mail() function
        }
        
        // Set from address
        $mail->setFrom(
            $_POST['smtp_from_email'],
            $_POST['smtp_from_name']
        );
        
        // If test email data is provided, send a test email
        if (isset($_POST['test_email'])) {
            $mail->addAddress($_POST['test_email']);
            $mail->isHTML(true);
            $mail->Subject = $_POST['test_subject'];
            $mail->Body = $_POST['test_message'];
            $mail->AltBody = strip_tags($_POST['test_message']);
            
            if ($mail->send()) {
                $response = [
                    'success' => true,
                    'message' => 'Test email sent successfully! Please check your inbox.'
                ];
            } else {
                $response['message'] = 'Failed to send test email: ' . $mail->ErrorInfo;
            }
        }
        // Otherwise, just test the connection
        else {
            if ($_POST['mail_server_type'] === 'smtp') {
                if ($mail->smtpConnect()) {
                    $response = [
                        'success' => true,
                        'message' => 'SMTP connection successful! Your email settings are working.'
                    ];
                    $mail->smtpClose();
                }
            } else {
                $response = [
                    'success' => true,
                    'message' => 'Local mail server is ready to use.'
                ];
            }
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response); 