<?php
class ErrorLogger {
    private static $instance = null;
    private $logFile;
    private $isDebug;
    
    private function __construct() {
        $this->logFile = __DIR__ . '/../logs/error.log';
        $this->isDebug = defined('DEBUG_MODE') && DEBUG_MODE === true;
        
        // Create logs directory if it doesn't exist
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0777, true);
        }
        
        // Set up error handling
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleFatalError']);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function log($message, $type = 'INFO', $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logMessage = "[$timestamp] [$type] $message$contextStr" . PHP_EOL;
        
        error_log($logMessage, 3, $this->logFile);
        
        // If in debug mode, also output to error_log
        if ($this->isDebug) {
            error_log($logMessage);
        }
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
        
        // Don't execute PHP's internal error handler
        return true;
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
} 