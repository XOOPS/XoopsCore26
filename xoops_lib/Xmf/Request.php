<?php

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf;

use Xoops\Core\Locale\Time;

/**
 * Request Class
 *
 * This class serves to provide a common interface to access
 * request variables.  This includes $_POST, $_GET, and naturally $_REQUEST.  Variables
 * can be passed through an input filter to avoid injection or returned raw.
 *
 * @category  Xmf\Request
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Joomla!
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class Request
{
    /**
     * Available masks for cleaning variables
     */
    public const MASK_NO_TRIM    = 1;
    public const MASK_ALLOW_RAW  = 2;
    public const MASK_ALLOW_HTML = 4;

    /**
     * Gets the request method
     *
     * @return string
     */
    public static function getMethod()
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        return $method;
    }

    /**
     * Fetches and returns a given variable.
     *
     * The default behaviour is fetching variables depending on the
     * current request method: GET and HEAD will result in returning
     * an entry from $_GET, POST and PUT will result in returning an
     * entry from $_POST.
     *
     * You can force the source by setting the $hash parameter:
     *
     *  - post       $_POST
     *  - get        $_GET
     *  - files      $_FILES
     *  - cookie     $_COOKIE
     *  - env        $_ENV
     *  - server     $_SERVER
     *  - session    $_SESSION (returns default if no active session)
     *  - method     via current $_SERVER['REQUEST_METHOD']
     *  - default    $_REQUEST
     *
     * @param string $name    Variable name
     * @param mixed  $default Default value if the variable does not exist
     * @param string $hash    Source of variable value (GET, POST, FILES, COOKIE, ENV, SERVER, SESSION, METHOD, DEFAULT/REQUEST)
     * @param string $type    Return type for the variable (INT, FLOAT, BOOLEAN, WORD,
     *                         ALPHANUM, CMD, BASE64, STRING, ARRAY, PATH, NONE) For more
     *                         information see FilterInput::clean().
     * @param int    $mask    Filter mask for the variable
     *
     * @return mixed Requested variable
     */
    public static function getVar($name, $default = null, $hash = 'default', $type = 'none', $mask = 0)
    {
        // Ensure hash and type are uppercase
        $hash = strtoupper($hash);
        if ('METHOD' === $hash) {
            $hash = static::getMethod();
        }
        $type = strtoupper($type);

        // Get the input hash
        switch ($hash) {
            case 'GET':
                $input = &$_GET;
                break;
            case 'POST':
                $input = &$_POST;
                break;
            case 'FILES':
                $input = &$_FILES;
                break;
            case 'COOKIE':
                $input = &$_COOKIE;
                break;
            case 'ENV':
                $input = &$_ENV;
                break;
            case 'SERVER':
                $input = &$_SERVER;
                break;
            case 'SESSION':
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    $input = [];
                    break;
                }
                $input = $_SESSION;
                break;
            default:
                $input = &$_REQUEST;
                break;
        }

        if (isset($input[$name]) && null !== $input[$name]) {
            // Get the variable from the input hash and clean it
            $var = static::cleanVar($input[$name], $mask, $type);
        } elseif (null !== $default) {
            // Clean the default value
            $var = static::cleanVar($default, $mask, $type);
        } else {
            $var = $default;
        }

        return $var;
    }

    /**
     * Fetches and returns a given filtered variable. The integer
     * filter will allow only digits to be returned. This is currently
     * only a proxy function for getVar().
     *
     * See getVar() for more in-depth documentation on the parameters.
     *
     * @param string $name    Variable name
     * @param int    $default Default value if the variable does not exist
     * @param string $hash    Where the var should come from (GET, POST, FILES, COOKIE, ENV, SERVER, SESSION, METHOD, DEFAULT/REQUEST)
     *
     * @return int Requested variable
     */
    public static function getInt($name, $default = 0, $hash = 'default')
    {
        return (int) static::getVar($name, $default, $hash, 'int');
    }

    /**
     * Fetches and returns a given filtered variable.  The float
     * filter only allows digits and periods.  This is currently
     * only a proxy function for getVar().
     *
     * See getVar() for more in-depth documentation on the parameters.
     *
     * @param string $name    Variable name
     * @param float  $default Default value if the variable does not exist
     * @param string $hash    Where the var should come from (GET, POST, FILES, COOKIE, ENV, SERVER, SESSION, METHOD, DEFAULT/REQUEST)
     *
     * @return float Requested variable
     */
    public static function getFloat($name, $default = 0.0, $hash = 'default')
    {
        return (float) static::getVar($name, $default, $hash, 'float');
    }

    /**
     * Fetches and returns a given filtered variable. The bool
     * filter will only return true/false bool values. This is
     * currently only a proxy function for getVar().
     *
     * See getVar() for more in-depth documentation on the parameters.
     *
     * @param string $name    Variable name
     * @param bool   $default Default value if the variable does not exist
     * @param string $hash    Where the var should come from (GET, POST, FILES, COOKIE, ENV, SERVER, SESSION, METHOD, DEFAULT/REQUEST)
     *
     * @return bool Requested variable
     */
    public static function getBool($name, $default = false, $hash = 'default')
    {
        return (bool) static::getVar($name, $default, $hash, 'bool');
    }

    /**
     * Fetches and returns a given filtered variable. The word
     * filter only allows the characters [A-Za-z_]. This is currently
     * only a proxy function for getVar().
     *
     * See getVar() for more in-depth documentation on the parameters.
     *
     * @param string $name    Variable name
     * @param string $default Default value if the variable does not exist
     * @param string $hash    Where the var should come from (GET, POST, FILES, COOKIE, ENV, SERVER, SESSION, METHOD, DEFAULT/REQUEST)
     *
     * @return string Requested variable
     */
    public static function getWord($name, $default = '', $hash = 'default')
    {
        return static::getVar($name, $default, $hash, 'word');
    }

    /**
     * Fetches and returns a given filtered variable. The cmd filter only allows the characters
     * [A-Za-z0-9.-_] and returns in lower case. This is currently a proxy function for getVar().
     *
     * See getVar() for more in-depth documentation on the parameters.
     *
     * @param string $name    Variable name
     * @param string $default Default value if the variable does not exist
     * @param string $hash    Where the var should come from (GET, POST, FILES, COOKIE, ENV, SERVER, SESSION, METHOD, DEFAULT/REQUEST)
     *
     * @return string Requested variable
     */
    public static function getCmd($name, $default = '', $hash = 'default')
    {
        return static::getVar($name, $default, $hash, 'cmd');
    }

    /**
     * Fetches and returns a given filtered variable. The string
     * filter deletes 'bad' HTML code, if not overridden by the mask.
     * This is currently only a proxy function for getVar().
     *
     * See getVar() for more in-depth documentation on the parameters.
     *
     * @param string $name    Variable name
     * @param string $default Default value if the variable does not exist
     * @param string $hash    Where the var should come from (GET, POST, FILES, COOKIE, ENV, SERVER, SESSION, METHOD, DEFAULT/REQUEST)
     * @param int    $mask    Filter mask for the variable
     *
     * @return string Requested variable
     */
    public static function getString($name, $default = '', $hash = 'default', $mask = 0)
    {
        // Cast to string, in case static::MASK_ALLOW_RAW was specified for mask
        return (string) static::getVar($name, $default, $hash, 'string', $mask);
    }

    /**
     * Fetches and returns an array
     *
     * @param string $name    Variable name
     * @param mixed  $default Default value if the variable does not exist
     * @param string $hash    Where the var should come from (GET, POST, FILES, COOKIE, ENV, SERVER, SESSION, METHOD, DEFAULT/REQUEST)
     *
     * @return array
     */
    public static function getArray($name, $default = array(), $hash = 'default')
    {
        return static::getVar($name, $default, $hash, 'array');
    }

    /**
     * Fetches and returns raw text
     *
     * @param string $name    Variable name
     * @param string $default Default value if the variable does not exist
     * @param string $hash    Where the var should come from (GET, POST, FILES, COOKIE, ENV, SERVER, SESSION, METHOD, DEFAULT/REQUEST)
     *
     * @return string Requested variable
     */
    public static function getText($name, $default = '', $hash = 'default')
    {
        return (string) static::getVar($name, $default, $hash, 'string', static::MASK_ALLOW_RAW);
    }

    /**
     * Fetches and returns a web url
     *
     * @param string $name    Variable name
     * @param string $default Default value if the variable does not exist
     * @param string $hash    Where the var should come from (GET, POST, FILES, COOKIE, ENV, SERVER, SESSION, METHOD, DEFAULT/REQUEST)
     *
     * @return string Requested variable
     */
    public static function getUrl($name, $default = '', $hash = 'default')
    {
        return (string) static::getVar($name, $default, $hash, 'weburl');
    }

    /**
     * Fetches and returns a file (or web) path
     *
     * @param string $name    Variable name
     * @param string $default Default value if the variable does not exist
     * @param string $hash    Where the var should come from (GET, POST, FILES, COOKIE, ENV, SERVER, SESSION, METHOD, DEFAULT/REQUEST)
     *
     * @return string Requested variable
     */
    public static function getPath($name, $default = '', $hash = 'default')
    {
        return (string) static::getVar($name, $default, $hash, 'path');
    }

    /**
     * Fetches and returns an email address
     *
     * @param string $name    Variable name
     * @param string $default Default value if the variable does not exist
     * @param string $hash    Where the var should come from (GET, POST, FILES, COOKIE, ENV, SERVER, SESSION, METHOD, DEFAULT/REQUEST)
     *
     * @return string email address or default if invalid
     */
    public static function getEmail($name, $default = '', $hash = 'default')
    {
        $ret = (string) static::getVar($name, $default, $hash, 'email');
        return empty($ret) ? $default : $ret;
    }

    /**
     * Fetches and returns an IP address
     *
     * @param string $name    Variable name
     * @param string $default Default value if the variable does not exist
     * @param string $hash    Where the var should come from (GET, POST, FILES, COOKIE, ENV, SERVER, SESSION, METHOD, DEFAULT/REQUEST)
     *
     * @return string IP address or default if invalid
     */
    public static function getIP($name, $default = '', $hash = 'default')
    {
        $ret = (string) static::getVar($name, $default, $hash, 'ip');
        return empty($ret) ? $default : $ret;
    }

    /**
     * Return a DateTime object from a Xoops\Form\DateSelect or Xoops\Form\DateTimeSelect field
     *
     * @param string $name    Variable name
     * @param mixed  $default Default value if the variable does not exist
     * @param string $hash    Where the var should come from (POST, GET, FILES, COOKIE, METHOD)
     *
     * @return \DateTime object
     */
    public static function getDateTime($name, $default = null, $hash = 'default')
    {
        $values = self::getVar($name, [], $hash, 'array');
        $count = count($values);
        if ($count === 1) {
            $date = reset($values);
            $ret = (empty($date)) ? $default : Time::inputToDateTime($date);
        } elseif (isset($values['date']) && isset($values['time'])) {
            $ret = (empty($values['date'])) ? $default : Time::inputToDateTime($values);
        } else {
            $ret = $default;
        }
        return $ret;
    }

    /**
     * get request header
     *
     * @param string      $headerName name of header to retrieve, case-insensitive
     * @param string|null $default    default to return if named header is not found
     *
     * @return string header value or default if header was not found
     */
    public static function getHeader($headerName, $default = '')
    {
        /** @var string[] $headers */
        static $headers = null;

        if (null === $headers) {
            $headers = array();
            if (function_exists('apache_request_headers')) {
                $rawHeaders = apache_request_headers();
                foreach ($rawHeaders as $name => $value) {
                    $headers[strtolower($name)] = $value;
                }
            } else {
                // From joyview - https://php.net/manual/en/function.getallheaders.php
                foreach ($_SERVER as $name => $value) {
                    if ('HTTP_' === substr($name, 0, 5)) {
                        $translatedName = (string)str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))));
                        $headers[$translatedName] = $value;
                    }
                }
            }
        }

        $name = strtolower($headerName);
        if (isset($headers[$name])) {
            $headerValue = $headers[$name];
            if (is_string($headerValue)) {
                $cleanedHeader = static::cleanVar($headerValue);
                if (is_string($cleanedHeader)) {
                    return $cleanedHeader;
                }
            }
        }
        return $default;
    }

    /**
     * See if a variable exists in one of the request hashes
     *
     * @param string $name variable to look for
     * @param string $hash hash to check
     *
     * @return bool True if hash has an element 'name', otherwise false
     */
    public static function hasVar($name, $hash = 'default')
    {
        $hash = strtoupper($hash);
        if ('METHOD' === $hash) {
            $hash = strtoupper($_SERVER['REQUEST_METHOD']);
        }

        // Get the requested hash and determine existing value
        $original = static::get($hash, static::MASK_ALLOW_RAW);
        if (isset($original[$name])) {
            return true;
        }
        return false;
    }

    /**
     * Set a variable in one of the request variables
     *
     * For SESSION, the write is silently skipped if no session is active.
     *
     * @param string $name      Name
     * @param mixed  $value     Value
     * @param string $hash      Hash (GET, POST, REQUEST, COOKIE, FILES, ENV, SERVER, SESSION, METHOD)
     * @param bool   $overwrite Boolean
     *
     * @return mixed Previous value
     */
    public static function setVar($name, $value = null, $hash = 'method', $overwrite = true)
    {
        $hash = strtoupper($hash);
        if ('METHOD' === $hash) {
            $hash = strtoupper($_SERVER['REQUEST_METHOD']);
        }

        // Get the requested hash and determine existing value
        $original = static::get($hash, static::MASK_ALLOW_RAW);
        if (isset($original[$name])) {
            $previous = $original[$name];
            // don't overwrite value unless asked
            if (!$overwrite) {
                return $previous;
            }
        } else {
            $previous = null;
        }

        // set the value
        switch ($hash) {
            case 'GET':
                $_GET[$name] = $value;
                $_REQUEST[$name] = $value;
                break;
            case 'POST':
                $_POST[$name] = $value;
                $_REQUEST[$name] = $value;
                break;
            case 'REQUEST':
                $_REQUEST[$name] = $value;
                break;
            case 'COOKIE':
                $_COOKIE[$name] = $value;
                $_REQUEST[$name] = $value;
                break;
            case 'FILES':
                $_FILES[$name] = $value;
                break;
            case 'ENV':
                $_ENV[$name] = $value;
                break;
            case 'SERVER':
                $_SERVER[$name] = $value;
                break;
            case 'SESSION':
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION[$name] = $value;
                }
                break;
        }

        return $previous;
    }

    /**
     * Fetches and returns a request array.
     *
     * The default behaviour is fetching variables depending on the
     * current request method: GET and HEAD will result in returning
     * $_GET, POST and PUT will result in returning $_POST.
     *
     * You can force the source by setting the $hash parameter:
     *
     *  - post        $_POST
     *  - get         $_GET
     *  - files       $_FILES
     *  - cookie      $_COOKIE
     *  - env         $_ENV
     *  - server      $_SERVER
     *  - session     $_SESSION (returns empty if no active session)
     *  - method      via current $_SERVER['REQUEST_METHOD']
     *  - default     $_REQUEST
     *
     * @param string $hash to get (GET, POST, FILES, COOKIE, ENV, SERVER, SESSION, METHOD, DEFAULT/REQUEST)
     * @param int    $mask Filter mask for the variable
     *
     * @return mixed Request hash
     */
    public static function get($hash = 'default', $mask = 0)
    {
        $hash = strtoupper($hash);

        if ('METHOD' === $hash) {
            $hash = strtoupper($_SERVER['REQUEST_METHOD']);
        }

        switch ($hash) {
            case 'GET':
                $input = $_GET;
                break;
            case 'POST':
                $input = $_POST;
                break;
            case 'FILES':
                $input = $_FILES;
                break;
            case 'COOKIE':
                $input = $_COOKIE;
                break;
            case 'ENV':
                $input = &$_ENV;
                break;
            case 'SERVER':
                $input = &$_SERVER;
                break;
            case 'SESSION':
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    $input = [];
                    break;
                }
                $input = &$_SESSION;
                break;
            default:
                $input = $_REQUEST;
                break;
        }

        $result = static::cleanVars($input, $mask);

        return $result;
    }

    /**
     * Sets a request variable
     *
     * @param array  $array       An associative array of key-value pairs
     * @param string $hash        The request variable to set (GET, POST, REQUEST, COOKIE, FILES, ENV, SERVER, SESSION, METHOD)
     * @param bool   $overwrite   If true and an existing key is found, the value is overwritten,
     *                            otherwise it is ignored
     *
     * @return void
     */
    public static function set($array, $hash = 'method', $overwrite = true)
    {
        foreach ($array as $key => $value) {
            static::setVar($key, $value, $hash, $overwrite);
        }
    }

    /**
     * Clean up an input variable.
     *
     * @param mixed  $var  The input variable.
     * @param int    $mask Filter bit mask.
     *                      - 1=no trim: If this flag is cleared and the input is a string,
     *                        the string will have leading and trailing whitespace trimmed.
     *                      - 2=allow_raw: If set, no more filtering is performed, higher bits are ignored.
     *                      - 4=allow_html: HTML is allowed, but passed through a safe HTML filter first.
     *                        If set, no more filtering is performed.
     *                      - If no bits other than the 1 bit is set, a strict filter is applied.
     * @param string $type The variable type. See {@link FilterInput::clean()}.
     *
     * @return mixed
     */
    protected static function cleanVar($var, $mask = 0, $type = null)
    {
        // Static input filters for specific settings
        static $noHtmlFilter = null;
        static $safeHtmlFilter = null;

        // convert $var in array if $type is ARRAY
        if ('array' === strtolower((string)$type) && !is_array($var)) {
            $var = array($var);
        }

        // If the no trim flag is not set, trim the variable
        if (!($mask & static::MASK_NO_TRIM) && is_string($var)) {
            $var = trim($var);
        }

        // Now we handle input filtering
        // If the 'allow raw' flag is set, do not modify the variable
        if (!($mask & static::MASK_ALLOW_RAW)) {
            if ($mask & static::MASK_ALLOW_HTML) {
                // If the allow html flag is set, apply a safe html filter to the variable
                if (null === $safeHtmlFilter) {
                    $safeHtmlFilter = FilterInput::getInstance(array(), array(), 1, 1);
                }
                $var = $safeHtmlFilter->cleanVar($var, $type);
            } else {
                // Since no allow flags were set, we will apply the most strict filter to the variable
                if (null === $noHtmlFilter) {
                    $noHtmlFilter = FilterInput::getInstance();
                }
                $var = $noHtmlFilter::clean($var, $type);
            }
        }

        return $var;
    }

    /**
     * Clean up an array of variables.
     *
     * @param mixed  $var  The input variable.
     * @param int    $mask Filter bit mask. See {@link Request::cleanVar()}
     * @param string $type The variable type. See {@link FilterInput::clean()}.
     *
     * @return mixed
     */
    protected static function cleanVars($var, $mask = 0, $type = null)
    {
        if (is_array($var)) {
            foreach ($var as $key => &$value) {
                $value = static::cleanVars($value, $mask, $type);
            }
        } else {
            $var = static::cleanVar($var, $mask, $type);
        }

        return $var;
    }

    /**
     * Strips slashes recursively on an array
     *
     * @param string|array $value string of Array of (nested arrays of) strings
     *
     * @return array The input array with stripslashes applied to it
     */
    protected static function stripSlashesRecursive($value)
    {
        $value = is_array($value)
            ? array_map(array(get_called_class(), 'stripSlashesRecursive'), $value)
            : stripslashes($value);

        return $value;
    }
}
