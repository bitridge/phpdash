<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/project.php';
require_once 'includes/seo_log.php';
require_once 'includes/report.php';
require_once 'vendor/autoload.php';

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
        $sectionData = [
            'title' => $section['title'],
            'content' => $section['content'],
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

        $report['sections'][] = $sectionData;
    }
}

// Process selected logs
if (isset($_POST['selected_logs'])) {
    foreach ($_POST['selected_logs'] as $logId) {
        $log = getSeoLog($logId);
        if ($log) {
            $report['logs'][] = $log;
        }
    }
}

// Initialize DomPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);

// Get absolute path for images
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
           "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/';

// Convert relative paths to absolute for images
if ($project['logo_path']) {
    $project['logo_path'] = $baseUrl . $project['logo_path'];
}

foreach ($report['sections'] as &$section) {
    if ($section['image']) {
        $section['image'] = $baseUrl . $section['image'];
    }
}

foreach ($report['logs'] as &$log) {
    if ($log['image_path']) {
        $log['image_path'] = $baseUrl . $log['image_path'];
    }
}

// Helper function for log type colors
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

// Load and render template
ob_start();
include 'pdf_template.php';
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Generate filename
$filename = sanitizeFilename($project['project_name'] . ' - ' . $report['title']) . '.pdf';

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
    $targetDir = __DIR__ . '/uploads/reports/';
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = uniqid() . '_' . basename($file['name']);
    $targetPath = $targetDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/reports/' . $fileName;
    }
    
    return false;
} 