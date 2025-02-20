<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/ErrorLogger.php';

// Require admin access
requireAdmin();

$logger = ErrorLogger::getInstance();
$logPath = $logger->getLogPath();
$recentLogs = $logger->getRecentLogs(1000); // Get last 1000 lines

// Handle clear logs action
if (isset($_POST['clear_logs']) && $_POST['clear_logs'] === 'yes') {
    $logger->clearLog();
    header('Location: view_logs.php');
    exit();
}

// Set page title
$pageTitle = 'Error Logs';

// Include header
include 'templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Error Logs</h1>
    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to clear all logs?');">
        <input type="hidden" name="clear_logs" value="yes">
        <button type="submit" class="btn btn-danger">Clear Logs</button>
    </form>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($recentLogs)): ?>
            <p class="text-center text-muted">No logs found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Context</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($recentLogs) as $log): ?>
                            <?php
                            // Parse log line
                            if (preg_match('/\[(.*?)\] \[(.*?)\] (.*?)(?:\| Context: (.*))?$/', $log, $matches)) {
                                $timestamp = $matches[1];
                                $type = $matches[2];
                                $message = $matches[3];
                                $context = isset($matches[4]) ? json_decode($matches[4], true) : null;
                            }
                            ?>
                            <tr>
                                <td class="text-nowrap"><?php echo htmlspecialchars($timestamp); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($type) {
                                            'ERROR', 'FATAL', 'EXCEPTION' => 'danger',
                                            'WARNING' => 'warning',
                                            'NOTICE' => 'info',
                                            'DEBUG' => 'secondary',
                                            default => 'primary'
                                        };
                                    ?>">
                                        <?php echo htmlspecialchars($type); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($message); ?></td>
                                <td>
                                    <?php if ($context): ?>
                                        <pre class="mb-0"><code><?php echo htmlspecialchars(json_encode($context, JSON_PRETTY_PRINT)); ?></code></pre>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
pre {
    margin: 0;
    white-space: pre-wrap;
    font-size: 0.875rem;
}
</style>

<?php include 'templates/footer.php'; ?> 