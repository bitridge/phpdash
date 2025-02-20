<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($report['title']); ?></title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .project-logo {
            max-width: 200px;
            margin-bottom: 20px;
        }
        .project-name {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }
        .report-title {
            font-size: 24px;
            color: #34495e;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .section-content {
            margin-bottom: 15px;
            margin-top: 15px;
        }
        .section-content p {
            margin: 0 0 10px 0;
        }
        .section-content ul, .section-content ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .section-content li {
            margin-bottom: 5px;
        }
        .section-image-container {
            width: 100%;
            margin: 15px 0;
            text-align: center;
            max-height: 400px;
            overflow: hidden;
        }
        .section-image {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
            object-fit: contain;
            max-height: 400px;
        }
        .log-entry {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .log-header {
            margin-bottom: 10px;
        }
        .log-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            background-color: #3498db;
        }
        .log-date {
            color: #666;
            margin-left: 10px;
        }
        .log-image-container {
            width: 100%;
            margin-top: 10px;
            text-align: center;
        }
        .log-image {
            width: 100%;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
            object-fit: contain;
        }
        .log-content {
            max-width: 100%;
        }
        a {
            color: #0066cc;
            text-decoration: underline;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <?php if ($project['logo_path'] && file_exists($project['logo_path'])): ?>
            <?php $logger->log("PDF Template - Processing logo path: " . $project['logo_path'], 'INFO'); ?>
            <img src="<?php echo $project['logo_path']; ?>" alt="Project Logo" class="project-logo">
        <?php endif; ?>
        <div class="project-name"><?php echo htmlspecialchars($project['project_name']); ?></div>
        <div class="report-title"><?php echo htmlspecialchars($report['title']); ?></div>
    </div>

    <!-- Report Description -->
    <?php if (!empty($report['description'])): ?>
        <div class="section">
            <?php echo $report['description']; ?>
        </div>
    <?php endif; ?>

    <!-- Custom Sections -->
    <?php foreach ($report['sections'] as $section): ?>
        <div class="section">
            <h2 class="section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
            
            <?php if (!empty($section['image']) && file_exists($section['image'])): ?>
                <div class="section-image-container">
                    <img src="<?php echo $section['image']; ?>" alt="Section Image" class="section-image">
                </div>
            <?php endif; ?>
            
            <div class="section-content">
                <?php 
                    // Process and output the content
                    $content = html_entity_decode($section['content']);
                    $content = str_replace(['<p><br></p>', '<p></p>'], '', $content); // Remove empty paragraphs
                    echo $content;
                ?>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- SEO Logs -->
    <?php if (!empty($report['logs'])): ?>
        <?php $logger->log("PDF Template - Starting to render SEO logs section. Total logs: " . count($report['logs']), 'INFO'); ?>
        <div class="section">
            <h2 class="section-title">SEO Activity Log</h2>
            <?php foreach ($report['logs'] as $index => $log): ?>
                <?php $logger->log("PDF Template - Rendering log {$index} (ID: {$log['id']}, Date: {$log['log_date']}, Type: {$log['log_type']})", 'DEBUG'); ?>
                <div class="log-entry">
                    <div class="log-header">
                        <span class="log-type" style="background-color: <?php echo getLogTypeColor($log['log_type']); ?>">
                            <?php echo htmlspecialchars($log['log_type']); ?>
                        </span>
                        <span class="log-date">
                            <?php echo date('F j, Y', strtotime($log['log_date'])); ?>
                        </span>
                    </div>
                    <div class="log-content">
                        <?php 
                        $logger->log("PDF Template - Log content length: " . strlen($log['log_details']), 'DEBUG');
                        echo $log['log_details']; 
                        ?>
                    </div>
                    <?php if (!empty($log['image_path'])): ?>
                        <?php $logger->log("PDF Template - Processing log image: " . $log['image_path'], 'DEBUG'); ?>
                        <?php if (file_exists($log['image_path'])): ?>
                            <div class="log-image-container">
                                <img src="<?php echo $log['image_path']; ?>" alt="Log Image" class="log-image">
                            </div>
                            <?php $logger->log("PDF Template - Log image exists and was included", 'INFO'); ?>
                        <?php else: ?>
                            <?php $logger->log("PDF Template - Log image file not found: " . $log['image_path'], 'WARNING'); ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php $logger->log("PDF Template - Finished rendering SEO logs section", 'INFO'); ?>
        </div>
    <?php else: ?>
        <?php $logger->log("PDF Template - No SEO logs to render", 'WARNING'); ?>
    <?php endif; ?>
</body>
</html> 