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

/**
 * FilterInput is a class for filtering input from any data source
 *
 * Forked from the php input filter library by Daniel Morris
 *
 * Original Contributors: Gianpaolo Racca, Ghislain Picard,
 *                        Marco Wandschneider, Chris Tobin and Andrew Eddie.
 *
 * @category  Xmf\FilterInput
 * @package   Xmf
 * @author    Daniel Morris <dan@rootcube.com>
 * @author    Louis Landry <louis.landry@joomla.org>
 * @author    Grégory Mage (Aka Mage)
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2005 Daniel Morris
 * @copyright 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @copyright 2011-2023 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class FilterInput
{
    /**
     * Passes remove() makes before it gives up on a value. Real input settles
     * in at most a few passes; this bounds any input that would not.
     */
    private const MAX_FILTER_PASSES = 10;

    protected $tagsArray;         // default is empty array
    protected $attrArray;         // default is empty array

    protected $tagsMethod;        // default is 0
    protected $attrMethod;        // default is 0

    protected $xssAuto;           // default is 1
    protected $tagBlacklist = array(
        'applet',
        'body',
        'bgsound',
        'base',
        'basefont',
        'embed',
        'frame',
        'frameset',
        'head',
        'html',
        'id',
        'iframe',
        'ilayer',
        'layer',
        'link',
        'meta',
        'name',
        'object',
        'script',
        'style',
        'title',
        'xml'
    );
    // also, it will strip ALL event handlers
    protected $attrBlacklist = array('action', 'background', 'codebase', 'dynsrc', 'lowsrc');

    /**
     * Constructor
     *
     * @param array $tagsArray  - list of user-defined tags
     * @param array $attrArray  - list of user-defined attributes
     * @param int   $tagsMethod - 0 = allow just user-defined, 1 = allow all but user-defined
     * @param int   $attrMethod - 0 = allow just user-defined, 1 = allow all but user-defined
     * @param int   $xssAuto    - 0 = only auto clean essentials, 1 = allow clean blacklisted tags/attr
     */
    protected function __construct(
        $tagsArray = array(),
        $attrArray = array(),
        $tagsMethod = 0,
        $attrMethod = 0,
        $xssAuto = 1
    ) {
        // make sure user defined arrays are in lowercase
        $tagsArrayCount = count($tagsArray);
        for ($i = 0; $i < $tagsArrayCount; ++$i) {
            $tagsArray[$i] = strtolower($tagsArray[$i]);
        }
        $attrArrayCount = count($attrArray);
        for ($i = 0; $i < $attrArrayCount; ++$i) {
            $attrArray[$i] = strtolower($attrArray[$i]);
        }
        // assign to member vars
        $this->tagsArray  = (array) $tagsArray;
        $this->attrArray  = (array) $attrArray;
        $this->tagsMethod = $tagsMethod;
        $this->attrMethod = $attrMethod;
        $this->xssAuto    = $xssAuto;
    }

    /**
     * Returns an input filter object, only creating it if it does not already exist.
     *
     * This method must be invoked as:
     *   $filter = FilterInput::getInstance();
     *
     * @param array $tagsArray  list of user-defined tags
     * @param array $attrArray  list of user-defined attributes
     * @param int   $tagsMethod WhiteList method = 0, BlackList method = 1
     * @param int   $attrMethod WhiteList method = 0, BlackList method = 1
     * @param int   $xssAuto    Only auto clean essentials = 0,
     *                          Allow clean blacklisted tags/attr = 1
     *
     * @return FilterInput object.
     */
    public static function getInstance(
        $tagsArray = array(),
        $attrArray = array(),
        $tagsMethod = 0,
        $attrMethod = 0,
        $xssAuto = 1
    ) {
        static $instances;

        $className = get_called_class(); // so an extender gets an instance of itself

        $sig = md5(serialize(array($className, $tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto)));

        if (!isset($instances)) {
            $instances = array();
        }

        if (empty($instances[$sig])) {
            $instances[$sig] = new static($tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto);
        }

        return $instances[$sig];
    }

    /**
     * Method to be called by another php script. Processes for XSS and
     * any specified bad code.
     *
     * @param mixed $source - input string/array-of-string to be 'cleaned'
     *
     * @return string|array $source - 'cleaned' version of input parameter
     */
    public function process($source)
    {
        if (is_array($source)) {
            // clean all elements in this array
            foreach ($source as $key => $value) {
                // filter element for XSS and other 'bad' code etc.
                if (is_string($value)) {
                    $source[$key] = $this->remove($this->decode($value));
                }
            }
            return $source;
        }
        if (is_string($source)) {
            // clean this string
            return $this->remove($this->decode($source));
        } else {
            // return parameter as given
            return $source;
        }
    }

    /**
     * Static method to be called by another php script.
     * Clean the supplied input using the default filter
     *
     * @param mixed  $source Input string/array-of-string to be 'cleaned'
     * @param string $type   Return/cleaning type for the variable, one of
     *                       (INTEGER, FLOAT, BOOLEAN, WORD, ALPHANUM, CMD, BASE64,
     *                        STRING, ARRAY, PATH, USERNAME, WEBURL, EMAIL, IP)
     *
     * @return mixed 'Cleaned' version of input parameter
     * @static
     */
    public static function clean($source, $type = 'string')
    {
        static $filter = null;

        // need an instance for methods, since this is supposed to be static
        // we must instantiate the class - this will take defaults
        if (!is_object($filter)) {
            $filter = static::getInstance();
        }

        return $filter->cleanVar($source, $type);
    }

    /**
     * Method to be called by another php script. Processes for XSS and
     * specified bad code according to rules supplied when this instance
     * was instantiated.
     *
     * @param mixed  $source Input string/array-of-string to be 'cleaned'
     * @param string $type   Return/cleaning type for the variable, one of
     *                       (INTEGER, FLOAT, BOOLEAN, WORD, ALPHANUM, CMD, BASE64,
     *                        STRING, ARRAY, PATH, USERNAME, WEBURL, EMAIL, IP)
     *
     * @return mixed 'Cleaned' version of input parameter
     * @static
     */
    public function cleanVar($source, $type = 'string')
    {
        // Handle the type constraint
        switch (strtoupper($type)) {
            case 'INT':
            case 'INTEGER':
                // Only use the first integer value
                preg_match('/-?\d+/', (string) $source, $matches);
                $result = isset($matches[0]) ? (int) $matches[0] : 0;
                break;

            case 'FLOAT':
            case 'DOUBLE':
                // Only use the first floating point value
                preg_match('/-?\d+(\.\d+)?/', (string) $source, $matches);
                $result = isset($matches[0]) ? (float) $matches[0] : 0;
                break;

            case 'BOOL':
            case 'BOOLEAN':
                $result = (bool) $source;
                break;

            case 'WORD':
                $result = (string) preg_replace('/[^A-Z_]/i', '', $source);
                break;

            case 'ALPHANUM':
            case 'ALNUM':
                $result = (string) preg_replace('/[^A-Z0-9]/i', '', $source);
                break;

            case 'CMD':
                $result = (string) preg_replace('/[^A-Z0-9_\.-]/i', '', $source);
                $result = strtolower($result);
                break;

            case 'BASE64':
                $result = (string) preg_replace('/[^A-Z0-9\/+=]/i', '', $source);
                break;

            case 'STRING':
                $result = (string) $this->process($source);
                break;

            case 'ARRAY':
                $result = (array) $this->process($source);
                break;

            case 'PATH':
                $source = trim((string) $source);
                $pattern = '/^([-_\.\/A-Z0-9=&%?~]+)(.*)$/i';
                preg_match($pattern, $source, $matches);
                $result = isset($matches[1]) ? (string) $matches[1] : '';
                break;

            case 'USERNAME':
                $result = (string) preg_replace('/[\x00-\x1F\x7F<>"\'%&]/', '', $source);
                break;

            case 'WEBURL':
                /** @var string $result */
                $result = trim((string) $this->process($source));
                // reject protocol-relative URLs (//evil.example) and non-http(s) schemes
                if (str_starts_with($result, '//')) {
                    $result = '';
                }
                if ($result !== '') {
                    $urlparts = parse_url($result);
                    if (
                        is_array($urlparts)
                        && !empty($urlparts['scheme'])
                        && !($urlparts['scheme'] === 'http' || $urlparts['scheme'] === 'https')
                    ) {
                        $result = '';
                    }
                }
                // do not allow quotes, tag brackets or controls
                if (!preg_match('#^[^"<>\x00-\x1F]+\z#', $result)) {
                    $result = '';
                }
                break;

            case 'EMAIL':
                $result = (string) $source;
                if (!filter_var((string) $source, FILTER_VALIDATE_EMAIL)) {
                    $result = '';
                }
                break;

            case 'IP':
                $result = (string) $source;
                // this may be too restrictive.
                // Should the FILTER_FLAG_NO_PRIV_RANGE flag be excluded?
                if (!filter_var((string) $source, FILTER_VALIDATE_IP)) {
                    $result = '';
                }
                break;

            default:
                $result = $this->process($source);
                break;
        }

        return $result;
    }

    /**
     * Internal method to iteratively remove all unwanted tags and attributes
     *
     * @param String $source - input string to be 'cleaned'
     *
     * @return String $source - 'cleaned' version of input parameter
     */
    protected function remove($source)
    {
        // provides nested-tag protection; re-filter until the output is stable
        for ($pass = 0; ($filtered = $this->filterTags($source)) !== $source; ++$pass) {
            if ($pass >= self::MAX_FILTER_PASSES) {
                // a value that does not settle is dropped rather than filtered without bound
                return '';
            }
            $source = $filtered;
        }

        return $source;
    }

    /**
     * Internal method to strip a string of certain tags
     *
     * @param String $source - input string to be 'cleaned'
     *
     * @return String $source - 'cleaned' version of input parameter
     */
    protected function filterTags($source)
    {
        // filter pass setup
        $preTag = null;
        $postTag = $source;
        // find initial tag's position
        $tagOpen_start = strpos($source, '<');
        // iterate through string until no tags left
        while ($tagOpen_start !== false) {
            // process tag iteratively
            $preTag .= substr($postTag, 0, $tagOpen_start);
            $postTag = substr($postTag, $tagOpen_start);
            $fromTagOpen = substr($postTag, 1);
            // end of tag
            $tagOpen_end = strpos($fromTagOpen, '>');
            if ($tagOpen_end === false) {
                break;
            }
            // next start of tag (for nested tag assessment)
            $tagOpen_nested = strpos($fromTagOpen, '<');
            if (($tagOpen_nested !== false) && ($tagOpen_nested < $tagOpen_end)) {
                $preTag .= $this->stripTagOpeners(substr($postTag, 0, ($tagOpen_nested + 1)));
                $postTag = substr($postTag, ($tagOpen_nested + 1));
                $tagOpen_start = strpos($postTag, '<');
                continue;
            }
            $currentTag = substr($fromTagOpen, 0, $tagOpen_end);
            $tagLength = strlen($currentTag);
            if (!$tagOpen_end) {
                // "<>" is not a tag: keep it as text and move past it. Appending the whole
                // remainder here made every pass longer, so remove() never terminated.
                $preTag .= '<>';
                $postTag = substr($postTag, 2);
                $tagOpen_start = strpos($postTag, '<');
                continue;
            }
            // iterate through tag finding attribute pairs - setup
            $tagLeft = $currentTag;
            $attrSet = array();
            $currentSpace = strpos($tagLeft, ' ');
            if (substr($currentTag, 0, 1) === "/") {
                // is end tag
                $isCloseTag = true;
                list($tagName) = explode(' ', $currentTag);
                $tagName = substr($tagName, 1);
            } else {
                // is start tag
                $isCloseTag = false;
                list($tagName) = explode(' ', $currentTag);
            }
            // excludes all "non-regular" tagnames OR remove if xssauto is on and tag is blacklisted.
            // An empty (or "0") tag name is already rejected by the regex below, so a
            // separate "!$tagName" test would be dead code.
            if (
                (!preg_match('/^[a-z][a-z0-9]*\z/i', $tagName))
                || ((in_array(strtolower($tagName), $this->tagBlacklist))
                    && ($this->xssAuto))
            ) {
                $postTag = substr($postTag, ($tagLength + 2));
                $tagOpen_start = strpos($postTag, '<');
                // don't append this tag
                continue;
            }
            // this while is needed to support attribute values with spaces in!
            while ($currentSpace !== false) {
                $fromSpace = substr($tagLeft, ($currentSpace + 1));
                $nextSpace = strpos($fromSpace, ' ');
                $openQuotes = strpos($fromSpace, '"');
                $closeQuotes = strpos(substr($fromSpace, ($openQuotes + 1)), '"') + $openQuotes + 1;
                // another equals exists
                if (strpos($fromSpace, '=') !== false) {
                    // opening and closing quotes exists
                    if (
                        ($openQuotes !== false)
                        && (strpos(substr($fromSpace, ($openQuotes + 1)), '"') !== false)
                    ) {
                        $attr = substr($fromSpace, 0, ($closeQuotes + 1));
                    } else {
                        $attr = substr($fromSpace, 0, $nextSpace);
                    }
                    // one or neither exist
                } else {
                    // no more equals exist
                    $attr = substr($fromSpace, 0, $nextSpace);
                }
                // last attr pair
                if (!$attr) {
                    $attr = $fromSpace;
                }
                // add to attribute pairs array
                $attrSet[] = $attr;
                // next inc
                $tagLeft = substr($fromSpace, strlen($attr));
                $currentSpace = strpos($tagLeft, ' ');
            }
            // appears in array specified by user
            $tagFound = in_array(strtolower($tagName), $this->tagsArray);
            // remove this tag on condition
            if ($tagFound !== (bool) $this->tagsMethod) {
                // reconstruct tag with allowed attributes
                if (!$isCloseTag) {
                    $attrSet = $this->filterAttr($attrSet);
                    $preTag .= '<' . $tagName;
                    $attrSetCount = count($attrSet);
                    for ($i = 0; $i < $attrSetCount; ++$i) {
                        $preTag .= ' ' . $attrSet[$i];
                    }
                    // reformat single tags to XHTML
                    if (strpos($fromTagOpen, "</" . $tagName)) {
                        $preTag .= '>';
                    } else {
                        $preTag .= ' />';
                    }
                } else {
                    // just the tagname
                    $preTag .= '</' . $tagName . '>';
                }
            }
            // find next tag's start
            $postTag = substr($postTag, ($tagLength + 2));
            $tagOpen_start = strpos($postTag, '<');
        }
        // append any code after end of tags; an unclosed "<img ..." must not survive as a tag opener
        $preTag .= $this->stripTagOpeners($postTag);

        return $preTag;
    }

    /**
     * Remove every "<" that could open a tag from text that is not a complete tag.
     *
     * Per the HTML tokenizer only "<" followed by a letter, "/", "!" or "?" starts
     * markup; any other "<" (as in "a < b" or "<>") is kept as text.
     *
     * @param string $text text outside any recognised tag
     *
     * @return string
     */
    protected function stripTagOpeners($text)
    {
        return preg_replace('#<(?=[a-z/!?])#i', '', $text) ?? '';
    }

    /**
     * Internal method to strip a tag of certain attributes
     *
     * @param array $attrSet attributes
     *
     * @return array $newSet stripped attributes
     */
    protected function filterAttr($attrSet)
    {
        $newSet = array();
        // process attributes
        $attrSetCount = count($attrSet);
        for ($i = 0; $i < $attrSetCount; ++$i) {
            // skip blank spaces in tag
            if (!$attrSet[$i]) {
                continue;
            }
            // split into attr name and value
            $attrSubSet = explode('=', trim($attrSet[$i]));
            list($attrSubSet[0]) = explode(' ', $attrSubSet[0]);
            $attrSubSet[1] = $attrSubSet[1] ?? '';
            // removes all "non-regular" attr names AND also attr blacklisted
            if (
                (!preg_match('/^[a-z][a-z0-9_:.-]*\z/i', $attrSubSet[0]))
                || (($this->xssAuto)
                    && ((in_array(strtolower($attrSubSet[0]), $this->attrBlacklist))
                        || (strncasecmp($attrSubSet[0], 'on', 2) === 0)))
            ) {
                continue;
            }
            // xss attr value filtering
            if ($attrSubSet[1]) {
                // strips unicode, hex, etc
                $attrSubSet[1] = str_replace('&#', '', $attrSubSet[1]);
                // strip normal newline within attr value
                $attrSubSet[1] = preg_replace('/\s+/', '', $attrSubSet[1]);
                // strip double quotes
                $attrSubSet[1] = str_replace('"', '', $attrSubSet[1]);
                // [requested feature] convert single quotes from either side to doubles
                // (Single quotes shouldn't be used to pad attr value)
                if (
                    (substr($attrSubSet[1], 0, 1) === "'")
                    && (substr($attrSubSet[1], (strlen($attrSubSet[1]) - 1), 1) === "'")
                ) {
                    $attrSubSet[1] = substr($attrSubSet[1], 1, (strlen($attrSubSet[1]) - 2));
                }
                // strip slashes
                $attrSubSet[1] = stripslashes($attrSubSet[1]);
            }
            // auto strip attr's with "javascript:
            // Check the value as a browser reads it: named entities decoded ("&colon;", "&Tab;")
            // and whitespace/control characters removed ("java\tscript:").
            $attrValue = strtolower(
                preg_replace('/[\x00-\x20]+/', '', html_entity_decode($attrSubSet[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? ''
            );
            if (
                ((strpos($attrValue, 'expression') !== false)
                    && (strtolower($attrSubSet[0]) === 'style'))
                || (strpos($attrValue, 'javascript:') !== false)
                || (strpos($attrValue, 'behaviour:') !== false)
                || (strpos($attrValue, 'vbscript:') !== false)
                || (strpos($attrValue, 'mocha:') !== false)
                || (strpos($attrValue, 'livescript:') !== false)
            ) {
                continue;
            }

            // if matches user defined array
            $attrFound = in_array(strtolower($attrSubSet[0]), $this->attrArray);
            // keep this attr on condition
            if ($attrFound !== (bool) $this->attrMethod) {
                if ($attrSubSet[1]) {
                    // attr has value
                    $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[1] . '"';
                } elseif ($attrSubSet[1] === "0") {
                    // attr has decimal zero as value
                    $newSet[] = $attrSubSet[0] . '="0"';
                } else {
                    // reformat single attributes to XHTML
                    $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[0] . '"';
                }
            }
        }

        return $newSet;
    }

    /**
     * Try to convert to plaintext
     *
     * @param String $source string to decode
     *
     * @return String $source decoded
     */
    protected function decode($source)
    {
        // url decode
        $charset = defined('_CHARSET') ? constant('_CHARSET') : 'utf-8';
        $source = html_entity_decode($source, ENT_QUOTES, $charset);
        // convert decimal
        $source = preg_replace_callback(
            '/&#(\d+);/m',
            function ($matches) use ($charset) {
                return html_entity_decode('&#' . $matches[1] . ';', ENT_NOQUOTES, $charset);
            },
            $source
        );
        // convert hex notation
        $source = preg_replace_callback(
            '/&#x([a-f0-9]+);/mi',
            function ($matches) use ($charset) {
                return html_entity_decode('&#x' . $matches[1] . ';', ENT_NOQUOTES, $charset);
            },
            $source
        );

        return $source;
    }

    /**
     * gather - gather input from a source
     *
     * @param string $source    name of source superglobal, get, post or cookie
     * @param array  $input_map each element of the array is an array consisting of
     *                          elements to gather and clean from source
     *                            - name - key in source superglobal, no default
     *                            - type - FilterInput::clean type, default string
     *                            - default - default value, default ''
     *                            - trim - true to trim spaces from input, default true
     *                            - max length - maximum length to accept, 0=no limit, default 0
     *                          Example: array('op','string','view',true)
     * @param mixed  $require   name of required element, or false for nothing
     *                          required name. If the require name is set, values
     *                          will only be returned if the key $require is set
     *                          in the source array.
     *
     * @return array|false array of cleaned elements as specified by input_map, or
     *                     false if require key specified but not set
     *
     * @deprecated
     */
    public static function gather($source, $input_map, $require = false)
    {
        $output = array();

        if (!empty($source)) {
            $source = strtolower($source);
            foreach ($input_map as $input) {
                // set defaults
                if (isset($input[0])) {
                    $name = $input[0];
                    $type = isset($input[1]) ? $input[1] : 'string';
                    $default = isset($input[2]) ?
                        (($require && $require==$name) ? '': $input[2]) : '';
                    $trim = isset($input[3]) ? $input[3] : true;
                    $maxlen = isset($input[4]) ? $input[4] : 0;
                    $value = $default;
                    switch ($source) {
                        case 'get':
                            if (isset($_GET[$name])) {
                                $value=$_GET[$name];
                            }
                            break;
                        case 'post':
                            if (isset($_POST[$name])) {
                                $value=$_POST[$name];
                            }
                            break;
                        case 'cookie':
                            if (isset($_COOKIE[$name])) {
                                $value=$_COOKIE[$name];
                            }
                            break;
                    }
                    if ($trim) {
                        $value = trim($value);
                    }
                    if ($maxlen>0) {
                        if (function_exists('mb_strlen')) {
                            if (mb_strlen($value)>$maxlen) {
                                $value=mb_substr($value, 0, $maxlen);
                            }
                        } else {
                            $value=substr($value, 0, $maxlen);
                        }
                        if ($trim) {
                            $value = trim($value);
                        }
                    }
                    $output[$name] = self::clean($value, $type);
                }
            }
        }
        if ($require) {
            if (empty($output[$require])) {
                $output = false;
            }
        }
        return $output;
    }
}
