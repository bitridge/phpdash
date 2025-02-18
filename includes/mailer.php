<?php
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private static $instance = null;
    private $settings;
    private $mail;
    
    private function __construct() {
        $this->settings = Settings::getInstance();
        $this->initializeMailer();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializeMailer() {
        $this->mail = new PHPMailer(true);
        
        // Set default charset and encoding
        $this->mail->CharSet = 'UTF-8';
        $this->mail->Encoding = 'base64';
        
        // Set mailer type based on settings
        $mailServerType = $this->settings->get('mail_server_type', 'smtp');
        
        if ($mailServerType === 'smtp') {
            // Server settings for SMTP
            $this->mail->isSMTP();
            $this->mail->Host = $this->settings->get('smtp_host', '');
            $this->mail->Port = (int)$this->settings->get('smtp_port', 587);
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->settings->get('smtp_username', '');
            $this->mail->Password = $this->settings->get('smtp_password', '');
            
            // Set encryption
            $encryption = $this->settings->get('smtp_encryption', 'tls');
            if ($encryption === 'tls') {
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $this->mail->SMTPSecure = '';
                $this->mail->SMTPAutoTLS = false;
            }
        } else {
            // Use PHP's mail() function
            $this->mail->isMail();
        }
        
        // Set default sender
        $this->mail->setFrom(
            $this->settings->get('smtp_from_email', ''),
            $this->settings->get('smtp_from_name', 'SEO Dashboard')
        );
    }
    
    public function send($to, $subject, $body, $isHTML = true, $attachments = []) {
        try {
            // Reset all recipients and attachments
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            
            // Set recipient
            if (is_array($to)) {
                foreach ($to as $address) {
                    $this->mail->addAddress($address);
                }
            } else {
                $this->mail->addAddress($to);
            }
            
            // Set email format
            $this->mail->isHTML($isHTML);
            
            // Set subject and body
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            
            // Add plain text version if HTML
            if ($isHTML) {
                $this->mail->AltBody = strip_tags($body);
            }
            
            // Add attachments
            foreach ($attachments as $attachment) {
                if (isset($attachment['path'])) {
                    $name = $attachment['name'] ?? basename($attachment['path']);
                    $this->mail->addAttachment($attachment['path'], $name);
                }
            }
            
            // Send email
            return $this->mail->send();
        } catch (Exception $e) {
            error_log('Mailer Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function sendTemplate($to, $subject, $template, $data = [], $attachments = []) {
        // Load template
        $templatePath = __DIR__ . '/../templates/emails/' . $template . '.php';
        if (!file_exists($templatePath)) {
            error_log('Email template not found: ' . $template);
            return false;
        }
        
        // Extract data for template
        extract($data);
        
        // Capture template output
        ob_start();
        include $templatePath;
        $body = ob_get_clean();
        
        // Send email
        return $this->send($to, $subject, $body, true, $attachments);
    }
    
    public function testConnection() {
        try {
            if ($this->settings->get('mail_server_type', 'smtp') === 'smtp') {
                return $this->mail->smtpConnect();
            }
            return true; // Local mail server doesn't need connection test
        } catch (Exception $e) {
            error_log('Mail Test Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getLastError() {
        return $this->mail->ErrorInfo;
    }
} 