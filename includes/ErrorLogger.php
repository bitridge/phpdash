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
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logMessage = "[$timestamp] [$type] $message$contextStr" . PHP_EOL;
        
        error_log($logMessage, 3, $this->logFile);
        
        // If in debug mode and display errors is enabled, also output to error_log
        if ($this->debugMode && $this->displayErrors) {
            error_log($logMessage);
        }
    }
    
    private function shouldLog($type) {
        $logLevels = [
            'DEBUG' => 4,
            'INFO' => 3,
            'WARNING' => 2,
            'ERROR' => 1
        ];
        
        $currentLevel = $logLevels[$this->logLevel] ?? 1;
        $messageLevel = $logLevels[$type] ?? 1;
        
        return $messageLevel <= $currentLevel;
    }
    
    public function handleError($errno, $errstr, $errfile, $errline) {
        $type = 'ERROR';
        switch ($errno) {
            case E_WARNING:
                $type = 'WARNING';
                break;
            case E_NOTICE:
                $type = 'NOTICE';
                break;
            case E_DEPRECATED:
                $type = 'DEPRECATED';
                break;
        }
        
        $context = [
            'file' => $errfile,
            'line' => $errline,
            'error_code' => $errno
        ];
        
        $this->log($errstr, $type, $context);
        
        // Don't execute PHP's internal error handler if display_errors is off
        return !$this->displayErrors;
    }
    
    public function handleException($exception) {
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        $this->log($exception->getMessage(), 'EXCEPTION', $context);
    }
    
    public function handleFatalError() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $context = [
                'file' => $error['file'],
                'line' => $error['line']
            ];
            
            $this->log($error['message'], 'FATAL', $context);
        }
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