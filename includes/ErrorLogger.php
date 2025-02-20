<?php
require_once __DIR__ . '/settings.php';

class ErrorLogger {
    private static $instance = null;
    private $logFile;
    private $settings;
    private $debugMode;
    private $logLevel;
    private $logRetention;
    private $displayErrors;
    
    private const LOG_LEVELS = [
        'DEBUG' => 4,
        'INFO' => 3,
        'WARNING' => 2,
        'ERROR' => 1,
        'FATAL' => 0
    ];
    
    private function __construct() {
        $this->settings = Settings::getInstance();
        $this->logFile = __DIR__ . '/../logs/error.log';
        $this->initializeSettings();
        
        // Create logs directory if it doesn't exist
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0777, true);
        }
        
        // Set up error handling
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleFatalError']);
        
        // Apply display errors setting
        ini_set('display_errors', $this->displayErrors ? '1' : '0');
        ini_set('display_startup_errors', $this->displayErrors ? '1' : '0');
        
        // Clean old logs based on retention policy
        $this->cleanOldLogs();
    }
    
    private function initializeSettings() {
        $this->debugMode = $this->settings->get('debug_mode', '0') === '1';
        $this->logLevel = $this->settings->get('log_level', 'ERROR');
        $this->logRetention = (int)$this->settings->get('log_retention', 30);
        $this->displayErrors = $this->settings->get('display_errors', '0') === '1';
    }
    
    private function cleanOldLogs() {
        if (!file_exists($this->logFile)) {
            return;
        }
        
        $retentionDays = max(1, $this->logRetention);
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        
        // Check file size
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        $currentSize = filesize($this->logFile);
        
        if ($currentSize > $maxFileSize) {
            // Rotate the log file
            $backupFile = $this->logFile . '.' . date('Y-m-d-His');
            rename($this->logFile, $backupFile);
            touch($this->logFile);
            chmod($this->logFile, 0644);
            
            // Remove old backup files
            $this->removeOldBackups();
            return;
        }
        
        // Process existing logs
        $logs = file($this->logFile);
        $newLogs = [];
        
        foreach ($logs as $log) {
            if (preg_match('/\[(.*?)\]/', $log, $matches)) {
                $logTime = strtotime($matches[1]);
                if ($logTime > $cutoffTime) {
                    $newLogs[] = $log;
                }
            }
        }
        
        if (count($newLogs) < count($logs)) {
            file_put_contents($this->logFile, implode('', $newLogs));
        }
    }
    
    private function removeOldBackups() {
        $pattern = $this->logFile . '.*';
        $backupFiles = glob($pattern);
        
        if (!$backupFiles) {
            return;
        }
        
        // Sort files by modification time
        usort($backupFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Keep only last 5 backup files
        $maxBackups = 5;
        foreach (array_slice($backupFiles, $maxBackups) as $file) {
            unlink($file);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function log($message, $type = 'INFO', $context = []) {
        // Check if we should log this message based on log level
        if (!$this->shouldLog($type)) {
            return;
        }
        
        // Ensure message is a string
        $message = is_string($message) ? $message : print_r($message, true);
        
        // Format context
        $contextStr = empty($context) ? '' : ' | Context: ' . $this->formatContext($context);
        
        // Format timestamp with microseconds
        $timestamp = $this->getFormattedTimestamp();
        
        // Build log message
        $logMessage = sprintf(
            "[%s] [%-7s] %s%s%s",
            $timestamp,
            strtoupper($type),
            $message,
            $contextStr,
            PHP_EOL
        );
        
        error_log($logMessage, 3, $this->logFile);
        
        // If in debug mode and display errors is enabled, also output to error_log
        if ($this->debugMode && $this->displayErrors) {
            error_log($logMessage);
        }
    }
    
    private function formatContext($context) {
        if (empty($context)) {
            return '';
        }
        
        // Handle special cases
        if (is_string($context)) {
            return $context;
        }
        
        // Format arrays and objects
        return json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    
    private function getFormattedTimestamp() {
        $now = new DateTime();
        return $now->format('Y-m-d H:i:s.u');
    }
    
    private function shouldLog($type) {
        $type = strtoupper($type);
        $currentLevel = self::LOG_LEVELS[$this->logLevel] ?? 1;
        $messageLevel = self::LOG_LEVELS[$type] ?? 1;
        
        return $messageLevel <= $currentLevel;
    }
    
    public function handleError($errno, $errstr, $errfile, $errline) {
        $type = $this->getErrorType($errno);
        
        $context = [
            'file' => $errfile,
            'line' => $errline,
            'error_code' => $errno,
            'backtrace' => $this->getBacktrace()
        ];
        
        $this->log($errstr, $type, $context);
        
        // Don't execute PHP's internal error handler if display_errors is off
        return !$this->displayErrors;
    }
    
    public function handleException($exception) {
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
            'previous' => $this->getPreviousExceptions($exception)
        ];
        
        $this->log($exception->getMessage(), 'EXCEPTION', $context);
    }
    
    public function handleFatalError() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $context = [
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => $this->getErrorType($error['type']),
                'backtrace' => $this->getBacktrace()
            ];
            
            $this->log($error['message'], 'FATAL', $context);
        }
    }
    
    private function getErrorType($errno) {
        return match($errno) {
            E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR => 'ERROR',
            E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => 'WARNING',
            E_NOTICE, E_USER_NOTICE => 'NOTICE',
            E_DEPRECATED, E_USER_DEPRECATED => 'DEPRECATED',
            E_PARSE => 'PARSE',
            default => 'UNKNOWN'
        };
    }
    
    private function getBacktrace() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        // Remove the first few entries that relate to the error handler itself
        return array_slice($trace, 3);
    }
    
    private function getPreviousExceptions($exception) {
        $previous = [];
        $prev = $exception->getPrevious();
        
        while ($prev !== null) {
            $previous[] = [
                'message' => $prev->getMessage(),
                'code' => $prev->getCode(),
                'file' => $prev->getFile(),
                'line' => $prev->getLine()
            ];
            $prev = $prev->getPrevious();
        }
        
        return $previous;
    }
    
    public function getLogPath() {
        return $this->logFile;
    }
    
    public function clearLog() {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }
    
    public function getRecentLogs($lines = 100) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $logs = file($this->logFile);
        return array_slice($logs, -$lines);
    }
    
    public function refreshSettings() {
        $this->initializeSettings();
    }
} 