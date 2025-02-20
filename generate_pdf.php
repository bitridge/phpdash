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
    $logger->log("Raw POST data: " . json_encode($_POST), 'DEBUG');
    $logger->log("Selected logs from POST: " . json_encode($_POST['selected_logs']), 'DEBUG');
    
    // Get unique log IDs and ensure they're integers
    $selectedLogIds = array_map('intval', array_unique($_POST['selected_logs']));
    $logger->log("Selected log IDs after processing: " . json_encode($selectedLogIds), 'DEBUG');
    
    if (empty($selectedLogIds)) {
        $logger->log("No log IDs selected", 'WARNING');
    } else {
        // Get all logs in a single query using IN clause
        $conn = getDbConnection();
        $idList = implode(',', $selectedLogIds);
        
        // Get all selected logs and order them by date and type
        $query = "SELECT s.*, u.name as created_by_name 
                  FROM seo_logs s 
                  LEFT JOIN users u ON s.created_by = u.id 
                  WHERE s.id IN ($idList)
                  ORDER BY FIELD(s.id, $idList)";  // Preserve the order of selected logs
                  
        $logger->log("Executing SQL Query: " . $query, 'DEBUG');
        $result = $conn->query($query);
        
        if (!$result) {
            $logger->log("SQL Error: " . $conn->error, 'ERROR');
        }
        
        $report['logs'] = [];
        if ($result) {
            $processedIds = []; // Track processed IDs
            while ($log = $result->fetch_assoc()) {
                // Convert log ID to integer for comparison
                $logId = (int)$log['id'];
                
                $logger->log("Retrieved log: " . json_encode($log), 'DEBUG');
                $report['logs'][] = $log;
                $processedIds[] = $logId;
            }
            
            // Check if any logs were missed
            $missedIds = array_diff($selectedLogIds, $processedIds);
            if (!empty($missedIds)) {
                $logger->log("Warning: Some selected logs were not retrieved: " . implode(', ', $missedIds), 'WARNING');
            }
            
            $logger->log("Total logs retrieved: " . count($report['logs']), 'INFO');
            foreach ($report['logs'] as $log) {
                $logger->log("Final log in report - ID: {$log['id']}, Type: {$log['log_type']}, Date: {$log['log_date']}", 'INFO');
            }
        }
    }
}

function optimizeImageForPdf($imagePath) {
    if (!file_exists($imagePath)) {
        return false;
    }
    
    $logger = ErrorLogger::getInstance();
    $logger->log("Optimizing image for PDF: " . $imagePath, 'DEBUG');
    
    // Get image info
    $info = getimagesize($imagePath);
    if (!$info) {
        $logger->log("Failed to get image info: " . $imagePath, 'WARNING');
        return false;
    }
    
    // Only process PNG images
    if ($info[2] !== IMAGETYPE_PNG) {
        return $imagePath;
    }
    
    // Create optimized version in temp directory
    $tempDir = __DIR__ . '/tmp';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    $optimizedPath = $tempDir . '/' . uniqid('pdf_') . '.png';
    
    // Load the original image
    $originalImage = imagecreatefrompng($imagePath);
    if (!$originalImage) {
        $logger->log("Failed to load PNG image: " . $imagePath, 'WARNING');
        return $imagePath;
    }
    
    // Create a new true color image
    $width = imagesx($originalImage);
    $height = imagesy($originalImage);
    $newImage = imagecreatetruecolor($width, $height);
    
    // Preserve transparency
    imagealphablending($newImage, false);
    imagesavealpha($newImage, true);
    
    // Copy and convert the image
    imagecopy($newImage, $originalImage, 0, 0, 0, 0, $width, $height);
    
    // Save the optimized image
    imagepng($newImage, $optimizedPath, 9); // Maximum compression
    
    // Clean up
    imagedestroy($originalImage);
    imagedestroy($newImage);
    
    $logger->log("Image optimized successfully: " . $optimizedPath, 'DEBUG');
    return $optimizedPath;
}

// Before PDF generation, optimize images
foreach ($report['logs'] as &$log) {
    if (!empty($log['image_path'])) {
        // Check if path is already absolute
        if (file_exists($log['image_path'])) {
            $optimizedPath = optimizeImageForPdf($log['image_path']);
            if ($optimizedPath) {
                $log['image_path'] = $optimizedPath;
            }
        } else {
            $absolutePath = realpath(__DIR__ . '/' . $log['image_path']);
            if ($absolutePath) {
                $optimizedPath = optimizeImageForPdf($absolutePath);
                if ($optimizedPath) {
                    $log['image_path'] = $optimizedPath;
                }
            }
        }
    }
}

// Process project logo
if ($project['logo_path']) {
    // Check if path is already absolute
    if (file_exists($project['logo_path'])) {
        $optimizedPath = optimizeImageForPdf($project['logo_path']);
        if ($optimizedPath) {
            $project['logo_path'] = $optimizedPath;
        }
    } else {
        $absolutePath = realpath(__DIR__ . '/' . $project['logo_path']);
        if ($absolutePath) {
            $optimizedPath = optimizeImageForPdf($absolutePath);
            if ($optimizedPath) {
                $project['logo_path'] = $optimizedPath;
            }
        }
    }
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

// Verify logs are still in the report array
$logger->log("Pre-PDF generation - Number of logs: " . count($report['logs']), 'INFO');
$logger->log("Pre-PDF generation - Log IDs: " . json_encode(array_column($report['logs'], 'id')), 'DEBUG');

// Additional logging to verify no duplicates
$uniqueLogIds = array_unique(array_column($report['logs'], 'id'));
if (count($uniqueLogIds) !== count($report['logs'])) {
    $logger->log("Duplicate logs detected before PDF generation", 'WARNING');
    $logger->log("Unique Log IDs: " . json_encode($uniqueLogIds), 'DEBUG');
}

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
    
    // Check if path is already absolute
    if (file_exists($project['logo_path'])) {
        $logger->log("Logo path is already absolute and exists", 'INFO');
    } else {
        // Try to resolve relative path
        $absolutePath = realpath(__DIR__ . '/' . $project['logo_path']);
        if ($absolutePath && file_exists($absolutePath)) {
            $project['logo_path'] = $absolutePath;
            $logger->log("Resolved relative path to: " . $absolutePath, 'INFO');
        } else {
            $logger->log("Failed to resolve logo path", 'WARNING');
        }
    }
    
    $logger->log("Final logo path: " . $project['logo_path'], 'INFO');
}

foreach ($report['sections'] as &$section) {
    if ($section['image']) {
        if (file_exists($section['image'])) {
            // Path is already absolute
            continue;
        }
        $absolutePath = realpath(__DIR__ . '/' . $section['image']);
        if ($absolutePath && file_exists($absolutePath)) {
            $section['image'] = $absolutePath;
        }
    }
}

foreach ($report['logs'] as &$log) {
    if ($log['image_path']) {
        if (file_exists($log['image_path'])) {
            // Path is already absolute
            continue;
        }
        $absolutePath = realpath(__DIR__ . '/' . $log['image_path']);
        if ($absolutePath && file_exists($absolutePath)) {
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

// Clean up temporary files
$tempDir = __DIR__ . '/tmp';
if (file_exists($tempDir)) {
    $files = glob($tempDir . '/pdf_*.png');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

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
