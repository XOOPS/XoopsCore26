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
 * Metagen facilitates generating and assigning page meta tags
 *
 * @category  Xmf\Metagen
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @author    trabis <lusopoemas@gmail.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class Metagen
{
    /**
     * mbstring encoding
     */
    public const ENCODING = 'UTF-8';

    /**
     * horizontal ellipsis
     * This will be used to replace omitted text.
     */
    public const ELLIPSIS = "...";

    /**
     * assignTitle set the page title
     *
     * @param string $title page title
     *
     * @return void
     */
    public static function assignTitle($title)
    {
        $title = trim($title);
        $title = static::asPlainText($title);
        static::assignTemplateVar('xoops_pagetitle', $title);
    }

    /**
     * assignKeywords set the meta keywords tag
     *
     * @param string[] $keywords keywords list
     *
     * @return void
     */
    public static function assignKeywords($keywords)
    {
        if (!empty($keywords) && \is_array($keywords)) {
            $keyword_tag = implode(', ', $keywords);
            static::assignThemeMeta('keywords', $keyword_tag);
        }
    }

    /**
     * assignDescription set the meta description tag
     *
     * @param string $description page description
     *
     * @return void
     */
    public static function assignDescription($description)
    {
        $description = trim($description);
        if (!empty($description)) {
            static::assignThemeMeta('description', $description);
        }
    }

    /**
     * assign meta variables in template engine
     *
     * @param string $name  meta name (keywords, description)
     * @param string $value meta value
     */
    protected static function assignThemeMeta($name, $value)
    {
        if (class_exists('Xoops', false)) {
            \Xoops::getInstance()->theme()->addMeta('meta', $name, $value);
        } else {
            global $xoTheme;
            $xoTheme->addMeta('meta', $name, $value);
        }
    }

    /**
     * assign meta variables in template engine
     *
     * @param string $name  variable name (i.e. xoops_pagtitle)
     * @param string $value meta value
     */
    protected static function assignTemplateVar($name, $value)
    {
        if (class_exists('Xoops', false)) {
            \Xoops::getInstance()->tpl()->assign($name, $value);
        } else {
            global $xoopsTpl;
            $xoopsTpl->assign($name, $value);
        }
    }

    /**
     * generateKeywords builds a set of keywords from text body
     *
     * @param string        $body      text to extract keywords from
     * @param integer       $count     number of keywords to use
     * @param integer       $minLength minimum length of word to consider as a keyword
     * @param string[]|null $forceKeys array of keywords to force use, or null for none
     *
     * @return array of keywords
     */
    public static function generateKeywords(
        $body,
        $count = 20,
        $minLength = 4,
        $forceKeys = null
    ) {
        $keyCount = array();
        if (!is_array($forceKeys)) {
            $forceKeys = array();
        }

        $text = static::asPlainText($body);
        if (function_exists('mb_strtolower')) {
            $text = mb_strtolower($text, static::ENCODING);
        } else {
            $text = strtolower($text);
        }

        $originalKeywords = preg_split(
            '/[^\w\']+/u',
            $text,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        foreach ($originalKeywords as $originalKeyword) {
            if (static::stopWordsObject()->check($originalKeyword)) {
                $secondRoundKeywords = explode("'", $originalKeyword);
                foreach ($secondRoundKeywords as $secondRoundKeyword) {
                    if (
                        static::stopWordsObject()->check($secondRoundKeyword)
                        && strlen($secondRoundKeyword) >= $minLength
                    ) {
                        $keyCount[$secondRoundKeyword] =
                            empty($keyCount[$secondRoundKeyword]) ? 1 : $keyCount[$secondRoundKeyword] + 1;
                    }
                }
            }
        }

        while (!empty($forceKeys)) {
            $tempKey = strtolower(array_pop($forceKeys));
            $keyCount[$tempKey] = 999999;
        }

        arsort($keyCount, SORT_NUMERIC);
        $key = array_keys($keyCount);
        $keywords = array_slice($key, 0, $count);

        return $keywords;
    }

    /**
     * generateDescription - generate a short description from a body of text
     *
     * @param string  $body      body text
     * @param integer $wordCount maximum word count for description
     *
     * @return string
     */
    public static function generateDescription($body, $wordCount = 100)
    {
        $text = static::asPlainText($body);

        $words = explode(" ", $text);

        // Only keep $maxWords words
        $newWords = array();
        $i = 0;
        while ($i < $wordCount - 1 && $i < count($words)) {
            $newWords[] = $words[$i];
            ++$i;
        }
        $ret = implode(' ', $newWords);
        if (function_exists('mb_strlen')) {
            $len = mb_strlen($ret, static::ENCODING);
            $lastPeriod = mb_strrpos($ret, '.', 0, static::ENCODING);
            $ret .= ($lastPeriod === false) ? static::ELLIPSIS : '';
            if ($len > 100 && ($len - $lastPeriod) < 30) {
                $ret = mb_substr($ret, 0, $lastPeriod + 1, static::ENCODING);
            }
        } else {
            $len = strlen($ret);
            $lastPeriod = strrpos($ret, '.');
            $ret .= ($lastPeriod === false) ? static::ELLIPSIS : '';
            if ($len > 100 && ($len - $lastPeriod) < 30) {
                $ret = substr($ret, 0, $lastPeriod + 1);
            }
        }

        return $ret;
    }

    /**
     * generateMetaTags - generate and assign all meta tags
     *
     * @param string        $title     title
     * @param string        $body      body text
     * @param int           $count     maximum keywords to use
     * @param int           $minLength minimum length of word to consider as keyword
     * @param int           $wordCount maximum word count for description summary
     * @param string[]|null $forceKeys associative array of keywords to force use
     *
     * @return void
     */
    public static function generateMetaTags(
        $title,
        $body,
        $count = 20,
        $minLength = 4,
        $wordCount = 100,
        $forceKeys = null
    ) {
        $title_keywords = static::generateKeywords($title, $count, 3, $forceKeys);
        $keywords = static::generateKeywords($body, $count, $minLength, $title_keywords);
        $description = static::generateDescription($body, $wordCount);
        static::assignTitle($title);
        static::assignKeywords($keywords);
        static::assignDescription($description);
    }

    /**
     * Return true if the string is length > 0
     *
     * @param string $var to test
     *
     * @return boolean
     *
     * @author psylove
     */
    protected static function nonEmptyString($var)
    {
        return (strlen($var) > 0);
    }

    /**
     * Create a title for the short_url field of an article
     *
     * @param string $title     title of the article
     * @param string $extension extension to add
     *
     * @return string sort_url for the article
     *
     * @author psylove
     */
    public static function generateSeoTitle($title = '', $extension = '')
    {
        $title = preg_replace("/[^\p{N}\p{L}]/u", "-", $title);

        $tableau = explode("-", $title);
        $tableau = array_filter($tableau, static::nonEmptyString(...));
        $tableau = array_filter($tableau, array(static::stopWordsObject(), 'check'));
        $title = implode("-", $tableau);

        $title = (empty($title)) ? '' : $title . $extension;
        return $title;
    }

    /**
     * getSearchSummary splits a string into string no larger than a
     * specified length, and centered around the first occurrence
     * of any of an array of needles, or starting at the beginning
     * of the string if no needles are specified or found.
     *
     * The string will be broken on spaces and an ellipsis (…) will be
     * added to the string when broken.
     *
     * @param string $haystack the string to summarize
     * @param mixed  $needles  search term, array of search terms, or null
     * @param int    $length   maximum character length for the summary
     *
     * @return string a substring of haystack
     */
    public static function getSearchSummary($haystack, $needles = null, $length = 120)
    {
        $haystack = static::asPlainText($haystack);
        $pos = static::getNeedlePositions($haystack, $needles);

        $start = empty($pos) ? 0 : min($pos);

        $start = max($start - (int) ($length / 2), 0);

        $pre = ($start > 0); // need an ellipsis in front?
        if (function_exists('mb_strlen')) {
            if ($pre) {
                // we are not at the beginning so find first blank
                $temp = mb_strpos($haystack, ' ', $start, static::ENCODING);
                $start = ($temp === false) ? $start : $temp;
                $haystack = mb_substr($haystack, $start, mb_strlen($haystack), static::ENCODING);
            }

            $post = !(mb_strlen($haystack, static::ENCODING) < $length); // need an ellipsis in back?
            if ($post) {
                $haystack = mb_substr($haystack, 0, $length, static::ENCODING);
                $end = mb_strrpos($haystack, ' ', 0, static::ENCODING);
                if ($end) {
                    $haystack = mb_substr($haystack, 0, $end, static::ENCODING);
                }
            }
        } else {
            if ($pre) {
                // we are not at the beginning so find first blank
                $temp = strpos($haystack, ' ', $start);
                $start = ($temp === false) ? $start : $temp;
                $haystack = substr($haystack, $start);
            }

            $post = !(strlen($haystack) < $length); // need an ellipsis in back?
            if ($post) {
                $haystack = substr($haystack, 0, $length);
                $end = strrpos($haystack, ' ', 0);
                if ($end) {
                    $haystack = substr($haystack, 0, $end);
                }
            }
        }
        $haystack = ($pre ? static::ELLIPSIS : '') . trim($haystack) . ($post ? static::ELLIPSIS : '');
        return $haystack;
    }

    /**
     * asPlainText - clean string to be plain text, without control characters
     * such as newlines, html markup, or leading trailing or repeating spaces.
     *
     * @param string $rawText a text string to be cleaned
     *
     * @return string
     */
    protected static function asPlainText($rawText)
    {
        $text = static::normalizeMultilingual($rawText);
        $text = static::html2text($text);
        $text = static::purifyText($text);

        $text = str_replace(array("\n", "\r"), ' ', $text);
        $text = preg_replace('/[ ]* [ ]*/', ' ', $text);

        return trim($text);
    }

    /** @var callable|null normalizer applied to reduce multilingual markup */
    protected static $multilingualNormalizer;

    /**
     * Register a callback that reduces multilingual markup to a single language
     * before description/keyword extraction — for example selecting the active
     * language from markup such as [en]...[/en].
     *
     * A module such as xlanguage can register its processor (e.g.
     * Xlanguage\Utility::cleanMultiLang) from a preload, so XMF needs no hard
     * dependency on it. Pass null to clear.
     *
     * @param callable|null $normalizer signature: function (string $text): string
     *
     * @return void
     */
    public static function setMultilingualNormalizer(?callable $normalizer)
    {
        static::$multilingualNormalizer = $normalizer;
    }

    /**
     * Apply the registered multilingual normalizer, if any, before plain-text
     * extraction. No-op when none is registered — which avoids both the crash
     * reported when xlanguage markup reached Metagen (issue #86) and the data
     * loss of a blanket bracket strip (which would mash every language together
     * and remove legitimate bracketed content). Override in a subclass for a
     * different strategy.
     *
     * @param string $text raw text possibly containing multilingual markup
     *
     * @return string text reduced to the active language, or unchanged
     */
    protected static function normalizeMultilingual($text)
    {
        if (is_string($text) && null !== static::$multilingualNormalizer) {
            $result = call_user_func(static::$multilingualNormalizer, $text);
            if (is_string($result)) {
                $text = $result;
            }
        }

        return $text;
    }

    /**
     * getNeedlePositions - Essentially this is a strpos() for an array of needles.
     * Given a haystack and an array of needles, return an array of all initial
     * positions, if any, of those needles in that haystack.
     *
     * @param string $haystack the string to summarize
     * @param mixed  $needles  search term, array of search terms, or null
     *
     * @return integer[] array of initial positions of substring of haystack
     */
    protected static function getNeedlePositions($haystack, $needles)
    {
        $pos = array();
        $needles = empty($needles) ? array() : (array) $needles;
        foreach ($needles as $needle) {
            if (function_exists('mb_stripos')) {
                $i = mb_stripos($haystack, $needle, 0, static::ENCODING);
            } else {
                $i = stripos($haystack, $needle, 0);
            }
            if ($i !== false) {
                $pos[] = $i; // only store matches
            }
        }
        return $pos;
    }

    /**
     * purifyText
     *
     * @param string  $text    text to clean
     * @param boolean $keyword replace some punctuation with white space
     *
     * @return string cleaned text
     */
    protected static function purifyText($text, $keyword = false)
    {
        $text = str_replace('&nbsp;', ' ', $text);
        $text = str_replace('<br />', ' ', $text);
        $text = str_replace('<br/>', ' ', $text);
        $text = str_replace('<br', ' ', $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, self::ENCODING);
        $text = htmlspecialchars_decode($text, ENT_QUOTES);
        $text = str_replace(')', ' ', $text);
        $text = str_replace('(', ' ', $text);
        $text = str_replace(':', ' ', $text);
        $text = str_replace('&euro', ' euro ', $text);
        $text = str_replace('&hellip', '...', $text);
        $text = str_replace('&rsquo', ' ', $text);
        $text = str_replace('!', ' ', $text);
        $text = str_replace('?', ' ', $text);
        $text = str_replace('"', ' ', $text);
        $text = str_replace('-', ' ', $text);
        $text = str_replace(["\r", "\n"], ' ', $text);
        $text = str_replace('&#8213;', ' ', $text);

        if ($keyword) {
            $text = str_replace('.', ' ', $text);
            $text = str_replace(',', ' ', $text);
            $text = str_replace('\'', ' ', $text);
        }
        $text = str_replace(';', ' ', $text);

        return $text;
    }

    /**
     * html2text
     * This will remove HTML tags, javascript sections and white space. It will also
     * convert some common HTML entities to their text equivalent. Credits to newbb2
     *
     * @param string $document HTML to be converted
     *
     * @return string Text version of $document parameter
     */
    protected static function html2text($document)
    {
        $search = array(
            "'<script[^>]*?>.*?</script>'si", // Strip out javascript
            "'<img.*?/>'si",                  // Strip out img tags
            "'<[\/\!]*?[^<>]*?>'si",          // Strip out HTML tags
            "'([\r\n])[\s]+'",                // Strip out white space
            "'&(quot|#34);'i",                // Replace HTML entities
            "'&(amp|#38);'i",
            "'&(lt|#60);'i",
            "'&(gt|#62);'i",
            "'&(nbsp|#160);'i",
            "'&(iexcl|#161);'i",
            "'&(cent|#162);'i",
            "'&(pound|#163);'i",
            "'&(copy|#169);'i"
        );

        $replace = array(
            "",
            "",
            "",
            "\\1",
            "\"",
            "&",
            "<",
            ">",
            " ",
            chr(161),
            chr(162),
            chr(163),
            chr(169)
        );

        $text = preg_replace($search, $replace, $document);

        $text = preg_replace_callback(
            '/&#(\d+);/',
            function ($matches) {
                return html_entity_decode('&#' . $matches[1] . ';', ENT_NOQUOTES, self::ENCODING);
            },
            $text
        );

        return $text;
    }

    /**
     * checkStopWords - look up a word in a list of stop words and
     * classify it as a significant word or a stop word.
     *
     * @param string $key the word to check
     *
     * @return bool True if word is significant, false if it is a stop word
     * @deprecated since v1.2.0 - use Xmf\StopWords::check()
     */
    public static function checkStopWords($key)
    {
        return static::stopWordsObject()->check($key);
    }

    /**
     * Get a StopWords object
     *
     * @return StopWords
     */
    protected static function stopWordsObject()
    {
        static $object;
        if (null === $object) {
            $object = new StopWords();
        }
        return $object;
    }
}
