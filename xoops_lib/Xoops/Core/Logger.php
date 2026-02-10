<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core;

use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * Xoops\Core\Logger - dispatch log requests to any registered loggers.
 *
 * No logging is done in this class, but any logger, implemented as a
 * module or extension, can register as a logger using the addLogger()
 * method. Multiple loggers can be registered, and each will be
 * invoked in turn for each log() call.
 *
 * Such loggers are expected to implement the PSR-3 LoggerInterface.
 * In addition, any logger that generates output as part of the XOOPS
 * delivered page should implement the quiet() method, to disable output.
 *
 * Loggers are managed this way so that any routine may easily add a
 * log entry without needing to know any details of the implementation.
 *
 * Not all events are published through this mechanism, only specific requests
 * to log() or related methods. Individual loggers may listen for events (i.e.
 * preloads) or other sources and gain access to detailed debugging information.
 *
 * @category  Xoops\Core\Logger
 * @package   Logger
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @version   Release: 2.6.0
 * @link      http://xoops.org
 * @since     2.6.0
 */
class Logger implements LoggerInterface
{
    /**
     * @var LoggerInterface[] chain of PSR-3 compatible loggers to call
     */
    private $loggers = array();

    /**
     * @var boolean do we have active loggers?
     */
    private $logging_active = false;

    /**
     * @var boolean just to prevent fatal legacy errors. Does nothing. Stop it!
     */
    //public $activated = false;

    /**
     * Get the Xoops\Core\Logger instance
     *
     * @return Logger object
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) {
            $class = __CLASS__;
            $instance = new $class();
            // Always catch errors, for security reasons
            set_error_handler(array($instance, 'handleError'));
            // grab any uncaught exception
            set_exception_handler(array($instance, 'handleException'));
        }

        return $instance;
    }

    /**
     * Error handling callback.
     *
     * This will
     *
     * @param integer $errorNumber error number
     * @param string  $errorString error message
     * @param string  $errorFile   file
     * @param integer $errorLine   line number
     *
     * @return void
     */
    public function handleError($errorNumber, $errorString, $errorFile, $errorLine)
    {
        if (0 === error_reporting() && ($errorNumber !== E_DEPRECATED) && ($errorNumber !== E_USER_DEPRECATED)) {
            return;
        }
        if ($this->logging_active && $errorNumber) {

            // if an error occurs before a locale is established,
            // we still need messages, so check and deal with it

            $msg = ': ' . sprintf(
                    (class_exists('\XoopsLocale', false) ? \XoopsLocale::EF_LOGGER_FILELINE : "%s in file %s line %s"),
                    $this->sanitizePath($errorString),
                    $this->sanitizePath($errorFile),
                    $errorLine
                );

            switch ($errorNumber) {
                case E_USER_NOTICE:
                    $msg = (class_exists('\XoopsLocale', false) ? \XoopsLocale::E_LOGGER_ERROR : '*Error:') . $msg;
                    $this->log(LogLevel::NOTICE, $msg);
                    break;
                case E_NOTICE:
                    $msg = (class_exists('\XoopsLocale', false) ? \XoopsLocale::E_LOGGER_NOTICE : '*Notice:') . $msg;
                    $this->log(LogLevel::NOTICE, $msg);
                    break;
                case E_WARNING:
                    $msg = (class_exists('\XoopsLocale', false) ? \XoopsLocale::E_LOGGER_WARNING : '*Warning:') . $msg;
                    $this->log(LogLevel::WARNING, $msg);
                    break;
                case E_USER_ERROR:
                    $msg = (class_exists('\XoopsLocale', false) ? \XoopsLocale::E_LOGGER_ERROR : '*Error:') . $msg;
                    @$this->log(LogLevel::CRITICAL, $msg);
                    break;
                case E_USER_DEPRECATED:
                case E_DEPRECATED:
                    \Xoops::getInstance()->events()->triggerEvent('core.deprecated', array($msg));
                    break;
                default:
                    $msg = $this->getReadableErrorType($errorNumber) . $msg;
                    $this->log(LogLevel::ERROR, $msg);
                    break;
            }
        }

        if ($errorNumber == E_USER_ERROR) {
            $trace = true;
            if (substr($errorString, 0, '8') === 'notrace:') {
                $trace = false;
                $errorString = substr($errorString, 8);
            }
            $this->reportFatalError($errorString);
            if ($trace) {
                $trace = debug_backtrace();
                array_shift($trace);
                if ('cli' === php_sapi_name()) {
                    foreach ($trace as $step) {
                        if (isset($step['file'])) {
                            fprintf(STDERR, "%s (%d)\n", $this->sanitizePath($step['file']), $step['line']);
                        }
                    }
                } else {
                    echo "<div style='color:#f0f0f0;background-color:#f0f0f0'>" . _XOOPS_FATAL_BACKTRACE . ":<br />";
                    foreach ($trace as $step) {
                        if (isset($step['file'])) {
                            printf("%s (%d)\n<br />", $this->sanitizePath($step['file']), $step['line']);
                        }
                    }
                    echo '</div>';
                }
            }
            exit();
        }
    }

    /**
     * getReadableErrorType - given a numeric $errorNumber return constant name
     *
     * @param int $errorNumber error number as used in handleError()
     *
     * @return string
     */
    protected function getReadableErrorType(int $errorNumber)
    {
        static $lookupTable = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];

        if (null !== $lookupTable[$errorNumber]) {
            return $lookupTable[$errorNumber];
        }
        return class_exists('\XoopsLocale', false) ? \XoopsLocale::E_LOGGER_UNKNOWN : '*Unknown:';
}
    /**
     * Exception handling callback.
     *
     * @param \Exception|\Throwable $e uncaught Exception or Error
     *
     * @return void
     */
    public function handleException($e)
    {
        if ($this->isThrowable($e)) {
            $msg = $e->getMessage();
            $this->reportFatalError($msg);
        }
    }

    /**
     * Determine if an object implements Throwable (or is an Exception that would under PHP 7.)
     *
     * @param mixed $e Expected to be an object related to Exception or Throwable
     *
     * @return bool true if related to Throwable or Exception, otherwise false
     */
    protected function isThrowable($e)
    {
        $type = interface_exists('\Throwable', false) ? '\Throwable' : '\Exception';
        return $e instanceof $type;
    }

    /**
     * Announce fatal error, attempt to log
     *
     * @param string $msg error message to report
     *
     * @return void
     */
    private function reportFatalError($msg)
    {
        $msg=$this->sanitizePath($msg);
        if ('cli' === php_sapi_name()) {
            fprintf(STDERR, "\nError : %s\n", $msg);
        } else {
            if (defined('_XOOPS_FATAL_MESSAGE')) {
                printf(_XOOPS_FATAL_MESSAGE, XOOPS_URL, $msg);
            } else {
                printf("\nError : %s\n", $msg);
            }
        }
        @$this->log(LogLevel::CRITICAL, $msg);
    }

    /**
     * clean a path to remove sensitive details
     *
     * @param string $message text to sanitize
     *
     * @return string sanitized message
     */
    public function sanitizePath($message)
    {
        $cleaners = [
            ['\\', '/',],
            [\XoopsBaseConfig::get('var-path'), 'VAR',],
            [str_replace('\\', '/', realpath(\XoopsBaseConfig::get('var-path'))), 'VAR',],
            [\XoopsBaseConfig::get('lib-path'), 'LIB',],
            [str_replace('\\', '/', realpath(\XoopsBaseConfig::get('lib-path'))), 'LIB',],
            [\XoopsBaseConfig::get('root-path'), 'ROOT',],
            [str_replace('\\', '/', realpath(\XoopsBaseConfig::get('root-path'))), 'ROOT',],
            [\XoopsBaseConfig::get('db-name') . '.', '',],
            [\XoopsBaseConfig::get('db-name'), '',],
            [\XoopsBaseConfig::get('db-prefix') . '_', '',],
            [\XoopsBaseConfig::get('db-user'), '***',],
            [\XoopsBaseConfig::get('db-pass'), '***',],
        ];
        $stringsToClean = array_column($cleaners, 0);
        $replacementStings = array_column($cleaners, 1);

        $message = str_replace($stringsToClean, $replacementStings, $message);

        return $message;
    }

    /**
     * add a PSR-3 compatible logger to the chain
     *
     * @param object $logger a PSR-3 compatible logger object
     *
     * @return void
     */
    public function addLogger($logger)
    {
        if (is_object($logger) && method_exists($logger, 'log')) {
                $this->loggers[] = $logger;
                $this->logging_active = true;
        }
    }

    /**
     * System is unusable.
     *
     * @param string $message message
     * @param array  $context array of context data for this log entry
     *
     * @return void
     */
    public function emergency(string|\Stringable $message, array $context = array()): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of context data for this log entry
     *
     * @return void
     */
    public function alert(string|\Stringable $message, array $context = array()): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of context data for this log entry
     *
     * @return void
     */
    public function critical(string|\Stringable $message, array $context = array()): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of context data for this log entry
     *
     * @return void
     */
    public function error(string|\Stringable $message, array $context = array()): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of context data for this log entry
     *
     * @return void
     */
    public function warning(string|\Stringable $message, array $context = array()): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of context data for this log entry
     *
     * @return void
     */
    public function notice(string|\Stringable $message, array $context = array()): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of context data for this log entry
     *
     * @return void
     */
    public function info(string|\Stringable $message, array $context = array()): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of context data for this log entry
     *
     * @return void
     */
    public function debug(string|\Stringable $message, array $context = array()): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level   PSR-3 LogLevel constant
     * @param string|\Stringable $message message
     * @param array  $context array of context data for this log entry
     *
     * @return void
     */
    public function log($level, string|\Stringable $message, array $context = array()): void
    {
        if (!empty($this->loggers)) {
            foreach ($this->loggers as $logger) {
                if (is_object($logger)) {
                    try {
                        $logger->log($level, $message, $context);
                    } catch (\Exception $e) {
                        // just ignore, as we can't do anything, not even log it.
                    }
                }
            }
        }
    }

    /**
     * quiet - turn off output if output is rendered in XOOPS page output.
     * This is intended to assist ajax code that may fail with any extra
     * content the logger may introduce.
     *
     * It should have no effect on loggers using other methods, such a write
     * to file.
     *
     * @return void
     */
    public function quiet()
    {
        if (!empty($this->loggers)) {
            foreach ($this->loggers as $logger) {
                if (is_object($logger) && method_exists($logger, 'quiet')) {
                    try {
                        $logger->quiet();
                    } catch (\Exception $e) {
                        // just ignore, as we can't do anything, not even log it.
                    }
                }
            }
        }
    }

    // Deprecated uses

    /**
     * Keep deprecated calls from failing
     *
     * @param string $var property
     * @param string $val value
     *
     * @return void
     *
     * @deprecated
     */
    public function __set($var, $val)
    {
        $this->deprecatedMessage();
        // legacy compatibility: turn off logger display for $xoopsLogger->activated = false; usage
        if ($var==='activated' && !$val) {
            $this->quiet();
        }

    }

    /**
     * Keep deprecated calls from failing
     *
     * @param string $var property
     *
     * @return void
     *
     * @deprecated
     */
    public function __get($var)
    {
        $this->deprecatedMessage();
    }

    /**
     * Keep deprecated calls from failing
     *
     * @param string $method method
     * @param string $args   arguments
     *
     * @return void
     *
     * @deprecated
    */
    public function __call($method, $args)
    {
        $this->deprecatedMessage();
    }

    /**
     * issue a deprecated warning
     *
     * @return void
     */
    private function deprecatedMessage()
    {
        $xoops = \Xoops::getInstance();
        $xoops->deprecated('This use of XoopsLogger is deprecated since 2.6.0.');
    }
}
