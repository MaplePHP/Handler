<?php

declare(strict_types=1);

namespace PHPFuse\Handler;

class ErrorHandler
{
    public const ALL = E_ALL;
    public const NOTICE = E_NOTICE;
    public const WARNING = E_WARNING; // nonfatal runtime error execution of script has been halted
    public const STRICT = E_STRICT;
    public const PARSE = E_PARSE; // compile time error it is generated by the parser
    public const DEPRECATED = E_DEPRECATED; // The script found something that might be an error
    public const ERROR = E_ERROR; //fatal runtime error execution of script has been halted
    public const CORE_ERROR = E_CORE_ERROR; // Fatal errors that occurred during the initial startup of script
    public const CORE_WARNING = E_CORE_WARNING; // Nonfatal errors that occurred during the initial startup of script
    public const CATCH_ALL = [E_ALL, E_NOTICE, E_WARNING, E_STRICT, E_PARSE, E_DEPRECATED,
        E_ERROR, E_CORE_ERROR, E_CORE_WARNING];

    private $handler;
    private $message;
    private $displayError = false;
    private $logError = false;
    private $logErrorFile;
    private $errorLevels = array();
    private static $errorCount = 1;
    private static $errorFilter = array();

    public function __construct(
        bool $displayError,
        bool $logError = false,
        ?string $logErrorFile = null,
        bool $psrLogger = false
    ) {
        $this->displayError = $displayError;
        $this->logError = $logError;
        $this->logErrorFile = $logErrorFile;

        if ($this->displayError) {
            ini_set('display_errors', "1");
            ini_set('error_reporting', (string)E_ALL);
        }
        if ($this->logError) {
            ini_set("log_errors", "1");
        }
        if ($this->logErrorFile) {
            ini_set("error_log", $this->logErrorFile);
        }

        set_error_handler([$this, 'handler']);
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function setErrorLevel(array $errorLevels): void
    {
        $this->errorLevels = $errorLevels;
    }

    public function setHandler(callable $handler, ?array $errorLevels = null): void
    {
        $this->handler = $handler;
        if (!is_null($errorLevels)) {
            $this->errorLevels = $errorLevels;
        }
    }

    private function getMessage($message, $file, $line)
    {
        if (!is_null($this->message)) {
            return vsprintf($this->message, [$message, $file, $line]);
        }
        return "{$message} in {$file} on line {$line}";
    }

    public function handler($number, $message, $file, $line): void
    {
        $msg = $this->getMessage($message, $file, $line);
        $hasError = in_array($number, $this->errorLevels);
        $checksum = crc32($message . basename($file) . $line);

        if (!is_null($this->handler)) {
            $handler = $this->handler;
            $handler($msg, $number, $hasError, $this->displayError, [$number, $message, $file, $line]);
            if ($this->displayError) {
                echo "<pre><strong>{$checksum}:</strong> {$msg}</pre>";
            }
        } else {
            if ($this->displayError) {
                echo $msg;
            }
        }


        if ($this->logError && (!$this->displayError || empty(self::$errorFilter[$checksum]))) {
            error_log("ErrorID: {$checksum}: {$msg}");
            self::$errorFilter[$checksum] = 1;
        }

        if (self::$errorCount >= 100) {
            die();
        }
        self::$errorCount++;
    }
}
