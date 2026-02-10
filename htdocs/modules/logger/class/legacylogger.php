<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Xoops\Core\Logger;

/**
 * Collects information for a page request
 * Records information about database queries, blocks, and execution time
 * and can display it as HTML. It also catches php runtime errors.
 *
 * @category  Logger
 * @package   Logger
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class LegacyLogger implements LoggerInterface
{
    /**
     * @var $queries array of query log lines
     */
    protected $queries = array();

    /**
     * @var $blocks array of block log lines
     */
    protected $blocks = array();

    /**
     * @var $extra array of extra log lines
     */
    protected $extra = array();

    /**
     * @var start time information by name
     */
    protected $logstart = array();

    /**
     * @var end time information by name
     */
    protected $logend = array();

    /**
     * @var $errors array of error log lines
     */
    protected $errors = array();

    /**
     * @var $deprecated array of deprecated log lines
     */
    protected $deprecated = array();

    /**
     * @var true if rendering enables
     */
    protected $renderingEnabled = false;

    /**
     * @var true is logging is activated
     */
    protected $activated = false;

    /**
     * @var mixed boolean false if configs no set or read, array of module/user configs
     */
    protected $configs = false;

    /**
     * constructor
     */
    public function __construct()
    {
        Logger::getInstance()->addLogger($this);
    }

    /**
     * @var true if rendering enables
     */
    protected $usePopup = false;


    /**
     * Get a reference to the only instance of this class
     *
     * @return  object LoggerAbstract  reference to the only instance
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) {
            $class = __CLASS__;
            $instance = new $class();
        }

        return $instance;
    }

    /**
     * Save a copy of our config array
     *
     * @param array $configs array of module/user config options
     *
     * @return void
     */
    public function setConfigs($configs)
    {
        $this->configs = $configs;
    }

    /**
     * disable logging
     *
     * @return void
     */
    public function disable()
    {
        //error_reporting(0);
        $this->activated = false;
    }

    /**
     * Enable logger output rendering
     * When output rendering is enabled, the logger will insert its output within the page content.
     * If the string <!--<xo-logger-output>--> is found in the page content, the logger output will
     * replace it, otherwise it will be inserted after all the page output.
     *
     * @return void
     */
    public function enable()
    {
        error_reporting(E_ALL);
        $xoops = Xoops::getInstance();
        if ($this->configs && array_key_exists('logger_enable', $this->configs)) {
            if ($this->configs['logger_popup']) {
                $this->usePopup = true;
            }
        }
        $this->activated = true;
        $this->enableRendering();
    }

    /**
     * report enabled status
     *
     * @return bool
     */
    public function isEnable()
    {
        return $this->activated;
    }

    /**
     * disable output for the benefit of ajax scripts
     *
     * @return void
     */
    public function quiet()
    {
        $this->activated = false;
    }

    /**
     * Add our resources to the theme as soon as it is available, otherwise return
     *
     * @return void
     */
    private function addToTheme()
    {
        static $addedResource = false;

        if ($this->activated && !$addedResource) {
            if (isset($GLOBALS['xoTheme'])) {
                /*
                $xoops = Xoops::getInstance();
                $head = '</style>' . $this->renderer->renderHead()
                    . '<style>.icon-tags:before { content: ""; width: 16px; background-position: -25px -48px;}';
                $xoops->theme()->addStylesheet(null, null, $head);
                */
                $addedResource = true;
            }
        }
    }

    /**
     * Start a timer
     *
     * @param string $name name of the timer
     *
     * @return void
     */
    public function startTime($name = 'XOOPS')
    {
        if ($this->activated) {
            $this->logstart[$name] = microtime(true);
        }
    }

    /**
     * Stop a timer
     *
     * @param string $name name of the timer
     *
     * @return void
     */
    public function stopTime($name = 'XOOPS')
    {
        if ($this->activated) {
            $this->logend[$name] = microtime(true);
        }
    }

    /**
     * Log a database query
     *
     * @param string $sql        sql that was processed
     * @param string $error      error message
     * @param int    $errno      error number
     * @param float  $query_time execution time
     *
     * @return void
     */
    public function addQuery($sql, $error = null, $errno = null, $query_time = null)
    {
        if ($this->activated) {
            $this->queries[] = array(
                'sql' => $sql, 'error' => $error, 'errno' => $errno, 'query_time' => $query_time
            );
        }
    }

    /**
     * Log display of a block
     *
     * @param string $name      name of the block
     * @param bool   $cached    was the block cached?
     * @param int    $cachetime cachetime of the block
     *
     * @return void
     */
    public function addBlock($name, $cached = false, $cachetime = 0)
    {
        if ($this->activated) {
            $this->blocks[] = array('name' => $name, 'cached' => $cached, 'cachetime' => $cachetime);
        }
    }

    /**
     * Log extra information
     *
     * @param string $name name for the entry
     * @param string $msg  text message for the entry
     *
     * @return void
     */
    public function addExtra($name, $msg)
    {
        if ($this->activated) {
            $this->extra[] = array('name' => $name, 'msg' => $msg);
        }
    }

    /**
     * Log messages for deprecated functions
     *
     * @param string $msg name for the entry
     *
     * @return void
     */
    public function addDeprecated($msg)
    {
        if ($this->activated) {
            $this->deprecated[] = $msg;
        }
    }

    /**
     * Log exceptions
     *
     * @param Exception $e name for the entry
     *
     * @return void
     */
    public function addException($e)
    {
        if ($this->activated) {
            $this->log(
                LogLevel::ERROR,
                'Exception: ' . $e->getMessage() . ' - ' .
                $this->sanitizePath($e->getFile()) . ' ' . $e->getLine()
            );
        }
    }

    /**
     * sanitizePath
     *
     * @param string $path path name to sanitize
     *
     * @return string path with top levels removed
     */
    public function sanitizePath($path)
    {
        $path = str_replace(
            array(
                '\\',
                \XoopsBaseConfig::get('root-path'),
                str_replace('\\', '/', realpath(\XoopsBaseConfig::get('root-path')))
            ),
            array('/', '', ''),
            $path
        );
        return $path;
    }

    /**
     * Enable logger output rendering
     * When output rendering is enabled, the logger will insert its output within the page content.
     * If the string <!--<xo-logger-output>--> is found in the page content, the logger output will
     * replace it, otherwise it will be inserted after all the page output.
     *
     * @return void
     */
    public function enableRendering()
    {
        if (!$this->renderingEnabled) {
            ob_start(array(&$this, 'render'));
            $this->renderingEnabled = true;
        }
    }

    /**
     * Output buffering callback inserting logger dump in page output
     *
     * @param string $output output buffer to add logger rendering to
     *
     * @return string output
     */
    public function render($output)
    {
        if (!$this->activated) {
            return $output;
        }

        $log = $this->dump($this->usePopup ? 'popup' : '');
        $this->renderingEnabled = $this->activated = false;

        $pattern = '<!--<xo-logger-output>-->';
        $pos = strpos($output, $pattern);
        if ($pos !== false) {
            return substr($output, 0, $pos) . $log . substr($output, $pos + strlen($pattern));
        } else {
            return $output . $log;
        }
    }

    /**
     * Dump output
     *
     * @param string $mode unused
     *
     * @return string output
     */
    public function dump($mode = '')
    {
        $ret = '';
// -------------------------------------------------------------
        $xoops = Xoops::getInstance();
        /* @var $this LoggerLegacy */
        $ret = '';
        if ($mode === 'popup') {
            $dump = $this->dump('');
            $content = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<head>
    <meta http-equiv="content-language" content="' . XoopsLocale::getLangCode() . '" />
    <meta http-equiv="content-type" content="text/html; charset=' . XoopsLocale::getCharset() . '" />
    <title>' . $xoops->getConfig('sitename') . ' - ' . _MD_LOGGER_DEBUG . ' </title>
    <meta name="generator" content="XOOPS" />
    <link rel="stylesheet" type="text/css" media="all" href="' . $xoops->getCss($xoops->getConfig('theme_set')) . '" />
</head>
<body>' . $dump . '
    <div style="text-align:center;">
        <input class="formButton" value="' . XoopsLocale::A_CLOSE . '" type="button" onclick="javascript:window.close();" />
    </div>
';
            $ret .= '
<script type="text/javascript">
    debug_window = openWithSelfMain("about:blank", "popup", 680, 450, true);
    debug_window.document.clear();
';
            $lines = preg_split("/(\r\n|\r|\n)( *)/", $content);
            foreach ($lines as $line) {
                $ret .= "\n" . 'debug_window.document.writeln("'
                    . str_replace(array('"', '</'), array('\"', '<\/'), $line) . '");';
            }
            $ret .= '
    debug_window.focus();
    debug_window.document.close();
</script>
';
        }

        $this->addExtra(
            _MD_LOGGER_INCLUDED_FILES,
            sprintf(_MD_LOGGER_FILES, count(get_included_files()))
        );

        /*
        $included_files = get_included_files();
        foreach ($included_files as $filename) {
            $this->addExtra('files',$filename);
        }

        if (function_exists('memory_get_peak_usage')) {
            $this->addExtra('Peak memory',memory_get_peak_usage());
        }
        */

        $memory = 0;

        if (function_exists('memory_get_usage')) {
            $memory = memory_get_usage() . ' bytes';
        } else {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $out = array();
                exec('tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $out);
                if (isset($out[5])) {
                    $memory = sprintf(_MD_LOGGER_MEM_ESTIMATED, substr($out[5], strpos($out[5], ':') + 1));
                }
            }
        }
        if ($memory) {
            $this->addExtra(_MD_LOGGER_MEM_USAGE, $memory);
        }

        if (empty($mode)) {
            $views = array('errors', 'deprecated', 'queries', 'blocks', 'extra');
            $ret .= "\n<div id=\"xo-logger-output\">\n<div id='xo-logger-tabs'>\n";
            $ret .= "<a href='javascript:xoSetLoggerView(\"none\")'>" . _MD_LOGGER_NONE . "</a>\n";
            $ret .= "<a href='javascript:xoSetLoggerView(\"\")'>" . _MD_LOGGER_ALL . "</a>\n";
            foreach ($views as $view) {
                $count = count($this->$view);
                $ret .= "<a href='javascript:xoSetLoggerView(\"$view\")'>" . constant('_MD_LOGGER_' . strtoupper($view)) . " ($count)</a>\n";
            }
            $count = count($this->logstart);
            $ret .= "<a href='javascript:xoSetLoggerView(\"timers\")'>" . _MD_LOGGER_TIMERS . "($count)</a>\n";
            $ret .= "</div>\n";
        }

        if (empty($mode) || $mode === 'errors') {
            $class = 'even';
            $ret .= '<table id="xo-logger-errors" class="outer"><thead><tr><th>' . _MD_LOGGER_ERRORS . '</th></tr></thead><tbody>';
            foreach ($this->errors as $error) {
                $ret .= "\n<tr><td class='$class'>";
                $ret .= $error;
                $ret .= "<br />\n</td></tr>";
                $class = ($class === 'odd') ? 'even' : 'odd';
            }
            $ret .= "\n</tbody></table>\n";
        }

        if (empty($mode) || $mode === 'deprecated') {
            $class = 'even';
            $ret .= '<table id="xo-logger-deprecated" class="outer"><thead><tr><th>' . _MD_LOGGER_DEPRECATED . '</th></tr></thead><tbody>';
            foreach ($this->deprecated as $message) {
                $ret .= "\n<tr><td class='$class'>";
                $ret .= $message;
                $ret .= "<br />\n</td></tr>";
                $class = ($class === 'odd') ? 'even' : 'odd';
            }
            $ret .= "\n</tbody></table>\n";
        }

        if (empty($mode) || $mode === 'queries') {
            $class = 'even';
            $ret .= '<table id="xo-logger-queries" class="outer"><thead><tr><th>' . _MD_LOGGER_QUERIES . '</th></tr></thead><tbody>';
            $pattern = '/\b' . preg_quote(\XoopsBaseConfig::get('db-prefix')) . '\_/i';

            foreach ($this->queries as $q) {
                $sql = preg_replace($pattern, '', $q['sql']);
                $query_time = isset($q['query_time']) ? sprintf('%0.6f - ', $q['query_time']) : '';

                if (isset($q['error'])) {
                    $ret .= '<tr class="' . $class . '"><td><span style="color:#ff0000;">' . $query_time . htmlentities($sql) . '<br /><strong>Error number:</strong> ' . $q['errno'] . '<br /><strong>Error message:</strong> ' . $q['error'] . '</span></td></tr>';
                } else {
                    $ret .= '<tr class="' . $class . '"><td>' . $query_time . htmlentities($sql) . '</td></tr>';
                }

                $class = ($class === 'odd') ? 'even' : 'odd';
            }
            $ret .= '</tbody><tfoot><tr class="foot"><td>' . _MD_LOGGER_TOTAL . ': <span style="color:#ff0000;">' . count($this->queries) . '</span></td></tr></tfoot></table>';
        }
        if (empty($mode) || $mode === 'blocks') {
            $class = 'even';
            $ret .= '<table id="xo-logger-blocks" class="outer"><thead><tr><th>' . _MD_LOGGER_BLOCKS . '</th></tr></thead><tbody>';
            foreach ($this->blocks as $b) {
                if ($b['cached']) {
                    $ret .= '<tr><td class="' . $class . '"><strong>' . $b['name'] . ':</strong> ' . sprintf(_MD_LOGGER_CACHED, (int)($b['cachetime'])) . '</td></tr>';
                } else {
                    $ret .= '<tr><td class="' . $class . '"><strong>' . $b['name'] . ':</strong> ' . _MD_LOGGER_NOT_CACHED . '</td></tr>';
                }
                $class = ($class === 'odd') ? 'even' : 'odd';
            }
            $ret .= '</tbody><tfoot><tr class="foot"><td>' . _MD_LOGGER_TOTAL . ': <span style="color:#ff0000;">' . count($this->blocks) . '</span></td></tr></tfoot></table>';
        }
        if (empty($mode) || $mode === 'extra') {
            $class = 'even';
            $ret .= '<table id="xo-logger-extra" class="outer"><thead><tr><th>' . _MD_LOGGER_EXTRA . '</th></tr></thead><tbody>';
            foreach ($this->extra as $ex) {
                $ret .= '<tr><td class="' . $class . '"><strong>';
                $ret .= htmlspecialchars($ex['name']) . ':</strong> ' . htmlspecialchars($ex['msg']);
                $ret .= '</td></tr>';
                $class = ($class === 'odd') ? 'even' : 'odd';
            }
            $ret .= '</tbody></table>';
        }
        if (empty($mode) || $mode === 'timers') {
            $class = 'even';
            $ret .= '<table id="xo-logger-timers" class="outer"><thead><tr><th>' . _MD_LOGGER_TIMERS . '</th></tr></thead><tbody>';
            foreach ($this->logstart as $k => $v) {
                $ret .= '<tr><td class="' . $class . '"><strong>';
                $ret .= sprintf(_MD_LOGGER_TIMETOLOAD, htmlspecialchars($k) . '</strong>', '<span style="color:#ff0000;">' . sprintf("%.03f", $this->dumpTime($k)) . '</span>');
                $ret .= '</td></tr>';
                $class = ($class === 'odd') ? 'even' : 'odd';
            }
            $ret .= '</tbody></table>';
        }

        if (empty($mode)) {
            $ret .= <<<EOT
</div>
<script type="text/javascript">
    function xoLogCreateCookie(name,value,days) {
        if (days) {
            var date = new Date();
            date.setTime(date.getTime()+(days*24*60*60*1000));
            var expires = "; expires="+date.toGMTString();
        }
        else var expires = "";
        document.cookie = name+"="+value+expires+"; path=/";
    }
    function xoLogReadCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
    }
    function xoLogEraseCookie(name) {
        createCookie(name,"",-1);
    }
    function xoSetLoggerView( name ) {
        var log = document.getElementById( "xo-logger-output" );
        if ( !log ) return;
        var i, elt;
        for ( i=0; i!=log.childNodes.length; i++ ) {
            elt = log.childNodes[i];
            if ( elt.tagName && elt.tagName.toLowerCase() != 'script' && elt.id != "xo-logger-tabs" ) {
                elt.style.display = ( !name || elt.id == "xo-logger-" + name ) ? "block" : "none";
            }
        }
        xoLogCreateCookie( 'XOLOGGERVIEW', name, 1 );
    }
    xoSetLoggerView( xoLogReadCookie( 'XOLOGGERVIEW' ) );
</script>

EOT;
        }
// -------------------------------------------------------------
        return $ret;
    }

    /**
     * get the current execution time of a timer
     *
     * @param string $name  name of the counter
     * @param bool   $unset removes counter from global log
     *
     * @return float current execution time of the counter
     */
    public function dumpTime($name = 'XOOPS', $unset = false)
    {
        if (!$this->activated) {
            return null;
        }

        if (!isset($this->logstart[$name])) {
            return 0;
        }
        $stop = isset($this->logend[$name]) ? $this->logend[$name] : microtime(true);
        $start = $this->logstart[$name];

        if ($unset) {
            unset($this->logstart[$name]);
            unset($this->logend[$name]);
        }

        return $stop - $start;
    }


    /**
     * PSR-3 System is unusable.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of additional context
     *
     * @return void
     */
    public function emergency(string|\Stringable $message, array $context = array()): void
    {
        if ($this->activated) {
            $this->log(LogLevel::EMERGENCY, $message, $context);
        }
    }

    /**
     * PSR-3 Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of additional context
     *
     * @return void
     */
    public function alert(string|\Stringable $message, array $context = array()): void
    {
        if ($this->activated) {
            $this->log(LogLevel::ALERT, $message, $context);
        }
    }

    /**
     * PSR-3 Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of additional context
     *
     * @return void
     */
    public function critical(string|\Stringable $message, array $context = array()): void
    {
        if ($this->activated) {
            $this->log(LogLevel::CRITICAL, $message, $context);
        }
    }

    /**
     * PSR-3 Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of additional context
     *
     * @return void
     */
    public function error(string|\Stringable $message, array $context = array()): void
    {
        if ($this->activated) {
            $this->log(LogLevel::ERROR, $message, $context);
        }
    }

    /**
     * PSR-3 Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of additional context
     *
     * @return void
     */
    public function warning(string|\Stringable $message, array $context = array()): void
    {
        if ($this->activated) {
            $this->log(LogLevel::WARNING, $message, $context);
        }
    }

    /**
     * PSR-3 Normal but significant events.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of additional context
     *
     * @return void
     */
    public function notice(string|\Stringable $message, array $context = array()): void
    {
        if ($this->activated) {
            $this->log(LogLevel::NOTICE, $message, $context);
        }
    }

    /**
     * PSR-3 Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of additional context
     *
     * @return void
     */
    public function info(string|\Stringable $message, array $context = array()): void
    {
        if ($this->activated) {
            $this->log(LogLevel::INFO, $message, $context);
        }
    }

    /**
     * PSR-3 Detailed debug information.
     *
     * @param string|\Stringable $message message
     * @param array  $context array of additional context
     *
     * @return void
     */
    public function debug(string|\Stringable $message, array $context = array()): void
    {
        if ($this->activated) {
            $this->log(LogLevel::DEBUG, $message, $context);
        }
    }

    /**
     * PSR-3 Logs with an arbitrary level.
     *
     * @param mixed  $level   logging level
     * @param string|\Stringable $message message
     * @param array  $context array of additional context
     *
     * @return void
     */
    public function log($level, string|\Stringable $message, array $context = array()): void
    {
        if (!$this->activated) {
            return;
        }

        $this->errors[] = $message;
    }
}
