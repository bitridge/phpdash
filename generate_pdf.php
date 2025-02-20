<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/project.php';
require_once 'includes/seo_log.php';
require_once 'includes/report.php';
require_once 'vendor/autoload.php';
require_once 'includes/ErrorLogger.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Require login
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: projects.php');
    exit();
}

// Get project
$projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$project = getProject($projectId);

if (!$project) {
    header('Location: projects.php');
    exit();
}

// Initialize error logger
$logger = ErrorLogger::getInstance();

// Process form data
$report = [
    'title' => $_POST['report_title'],
    'description' => $_POST['report_description'],
    'sections' => [],
    'logs' => []
];

// Process sections
if (isset($_POST['sections'])) {
    foreach ($_POST['sections'] as $index => $section) {
        // Ensure we have both title and content
        if (!empty($section['title']) && isset($section['content'])) {
            $sectionData = [
                'title' => $section['title'],
                'content' => trim($section['content']), // Ensure content is trimmed
                'image' => null
            ];

            // Handle section image
            if (isset($_FILES['sections']['name'][$index]['image']) && 
                $_FILES['sections']['size'][$index]['image'] > 0) {
                
                $file = [
                    'name' => $_FILES['sections']['name'][$index]['image'],
                    'type' => $_FILES['sections']['type'][$index]['image'],
                    'tmp_name' => $_FILES['sections']['tmp_name'][$index]['image'],
                    'error' => $_FILES['sections']['error'][$index]['image'],
                    'size' => $_FILES['sections']['size'][$index]['image']
                ];
                
                $imagePath = uploadReportImage($file);
                if ($imagePath) {
                    $sectionData['image'] = $imagePath;
                }
            }

            // Only add section if it has content
            if (!empty(strip_tags($sectionData['content']))) {
                $report['sections'][] = $sectionData;
            }
        }
    }
}

// Get selected logs
if (isset($_POST['selected_logs']) && is_array($_POST['selected_logs'])) {
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01');
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');
    
    // Get unique log IDs and ensure they're integers
    $selectedLogIds = array_map('intval', array_unique($_POST['selected_logs']));
    
    // Create a temporary array to store logs by ID to prevent duplicates
    $logsById = [];
    
    foreach ($selectedLogIds as $logId) {
        $log = getSeoLog($logId);
        if ($log) {
            $logDate = strtotime($log['log_date']);
            $startDateTime = strtotime($startDate);
            $endDateTime = strtotime($endDate . ' 23:59:59'); // Include the full end date
            
            // Only include logs within the date range and prevent duplicates
            if ($logDate >= $startDateTime && $logDate <= $endDateTime) {
                $logsById[$log['id']] = $log;
            }
        }
    }
    
    // Convert to indexed array and sort by date
    $report['logs'] = array_values($logsById);
    usort($report['logs'], function($a, $b) {
        $dateCompare = strtotime($b['log_date']) - strtotime($a['log_date']);
        if ($dateCompare === 0) {
            // If dates are the same, sort by created_at timestamp
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        }
        return $dateCompare;
    });
}

// Initialize DomPDF with updated options
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');
$options->set('chroot', [
    __DIR__,
    __DIR__ . '/uploads'
]);
$options->setIsRemoteEnabled(true);

$dompdf = new Dompdf($options);

// Get absolute path for images
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
           "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/';

// Store original paths before converting to absolute paths
$originalLogoPath = $project['logo_path'];
$logger->log("Original logo path: " . $originalLogoPath, 'INFO');

// Convert relative paths to absolute for images
if ($project['logo_path']) {
    $logger->log("Processing logo path...", 'INFO');
    
    // Try direct realpath
    $absolutePath = realpath(__DIR__ . '/' . $project['logo_path']);
    $logger->log("Attempting direct realpath: " . __DIR__ . '/' . $project['logo_path'], 'DEBUG');
    
    if ($absolutePath) {
        $project['logo_path'] = $absolutePath;
        $logger->log("Direct realpath successful: " . $absolutePath, 'INFO');
    } else {
        // Try with document root
        $docRootPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($project['logo_path'], '/');
        $absolutePath = realpath($docRootPath);
        $logger->log("Attempting document root path: " . $docRootPath, 'DEBUG');
        
        if ($absolutePath) {
            $project['logo_path'] = $absolutePath;
            $logger->log("Document root path successful: " . $absolutePath, 'INFO');
        } else {
            // If both attempts fail, try with the full server path
            $project['logo_path'] = $docRootPath;
            $logger->log("Using document root path directly: " . $docRootPath, 'INFO');
        }
    }
    
    // Log final path and existence check
    $logger->log("Final logo path: " . $project['logo_path'], 'INFO');
    $logger->log("File exists check: " . (file_exists($project['logo_path']) ? 'true' : 'false'), 'INFO');
}

foreach ($report['sections'] as &$section) {
    if ($section['image']) {
        $absolutePath = realpath(__DIR__ . '/' . $section['image']);
        if ($absolutePath) {
            $section['image'] = $absolutePath;
        }
    }
}

foreach ($report['logs'] as &$log) {
    if ($log['image_path']) {
        $absolutePath = realpath(__DIR__ . '/' . $log['image_path']);
        if ($absolutePath) {
            $log['image_path'] = $absolutePath;
        }
    }
}

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Load and render template
ob_start();
include 'pdf_template.php';
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->render();

// Generate filename
$filename = sanitizeFilename($project['project_name'] . ' - ' . $report['title']) . '.pdf';

// Save the PDF file
$pdfPath = 'uploads/reports/' . $filename;
if (!file_exists('uploads/reports')) {
    mkdir('uploads/reports', 0777, true);
}

file_put_contents($pdfPath, $dompdf->output());

// Save report to database
saveReport(
    $project['id'],
    $report['title'],
    $report['description'],
    $pdfPath,
    $_SESSION['user_id']
);

// Output PDF
$dompdf->stream($filename, ['Attachment' => true]);

// Helper function to sanitize filename
function sanitizeFilename($filename) {
    // Remove any character that isn't a letter, number, dash, underscore, or space
    $filename = preg_replace('/[^\w\-\. ]/', '', $filename);
    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);
    // Remove multiple consecutive underscores
    $filename = preg_replace('/_+/', '_', $filename);
    return $filename;
}

// Helper function to upload report images
function uploadReportImage($file) {
    $targetDir = __DIR__ . '/uploads/reports/images/';
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = uniqid() . '_' . basename($file['name']);
    $targetPath = $targetDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $targetPath; // Return full path for PDF
    }
    
    return false;
}

// Helper function for log type colors (moved from template)
function getLogTypeColor($type) {
    $colors = [
        'Technical' => '#3498db',
        'On-Page SEO' => '#2ecc71',
        'Off-Page SEO' => '#3498db',
        'Content' => '#f1c40f',
        'Analytics' => '#e74c3c',
        'Other' => '#95a5a6'
    ];
    
    return $colors[$type] ?? '#95a5a6';
} 