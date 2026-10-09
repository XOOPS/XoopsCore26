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
 * Highlighter
 *
 * @category  Xmf\Highlighter
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class Highlighter
{
    /**
     * mbstring encoding
     */
    public const ENCODING = 'UTF-8';

    /**
     * Apply highlight to words in body text
     *
     * Surround occurrences of words in body with pre in front and post
     * behind. Considers only occurrences of words outside of HTML tags.
     *
     * @param string|string[] $words words to highlight
     * @param string          $body  body of html text to highlight
     * @param string          $pre   string to begin a highlight
     * @param string          $post  string to end a highlight
     *
     * @return string highlighted body
     */
    public static function apply($words, $body, $pre = '<mark>', $post = '</mark>')
    {
        if (!is_array($words)) {
            $words = preg_replace('/[\s]+/', ' ', $words);
            $words = explode(' ', $words);
        }
        foreach ($words as $word) {
            $body = static::splitOnTag($word, $body, $pre, $post);
        }

        return $body;
    }

    /**
     * find needle in between html tags and add highlighting
     *
     * @param string $needle   string to find
     * @param string $haystack html text to find needle in
     * @param string $pre      insert before needle
     * @param string $post     insert after needle
     *
     * @return mixed return from preg_replace_callback()
     */
    protected static function splitOnTag($needle, $haystack, $pre, $post)
    {
        $encoding = static::ENCODING;
        return preg_replace_callback(
            '#((?:(?!<[/a-z]).)*)([^>]*>|$)#si',
            function ($capture) use ($needle, $pre, $post, $encoding) {
                $haystack = $capture[1];
                if (function_exists('mb_substr')) {
                    $p1 = mb_stripos($haystack, $needle, 0, $encoding);
                    $l1 = mb_strlen($needle, $encoding);
                    $ret = '';
                    while ($p1 !== false) {
                        $ret .= mb_substr($haystack, 0, $p1, $encoding) . $pre
                            . mb_substr($haystack, $p1, $l1, $encoding) . $post;
                        $haystack = mb_substr($haystack, $p1 + $l1, mb_strlen($haystack), $encoding);
                        $p1 = mb_stripos($haystack, $needle, 0, $encoding);
                    }
                } else {
                    $p1 = stripos($haystack, $needle);
                    $l1 = strlen($needle);
                    $ret = '';
                    while ($p1 !== false) {
                        $ret .= substr($haystack, 0, $p1) . $pre . substr($haystack, $p1, $l1) . $post;
                        $haystack = substr($haystack, $p1 + $l1);
                        $p1 = stripos($haystack, $needle);
                    }
                }
                $ret .= $haystack . $capture[2];

                return $ret;
            },
            $haystack
        );
    }
}
