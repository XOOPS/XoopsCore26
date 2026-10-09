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
 * StopWords - facilitate filtering of common or purely connective words for natural language processing
 *
 * @category  Xmf\StopWords
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @author    trabis <lusopoemas@gmail.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 * @see       https://en.wikipedia.org/wiki/Stop_words
 */
class StopWords
{
    /**
     * mbstring encoding
     */
    public const ENCODING = 'UTF-8';

    /** @var array<string, true> */
    protected $stopwordList = array();

    /**
     * StopWords constructor - load stop words for current locale
     *
     * @todo specify locale to constructor, will require shift away from defined constant
     */
    public function __construct()
    {
        if (!defined('_XMF_STOPWORDS')) {
            Language::load('stopwords');
        }
        if (defined('_XMF_STOPWORDS')) {
            $sw = explode(' ', _XMF_STOPWORDS);
            $this->stopwordList = array_fill_keys($sw, true);
        }
    }

    /**
     * check - look up a word in a list of stop words and
     * classify it as a significant word or a stop word.
     *
     * @param string $key the word to check
     *
     * @return bool True if word is significant, false if it is a stop word
     */
    public function check($key)
    {
        $key = function_exists('mb_strtolower')
            ? mb_strtolower($key, static::ENCODING)
            : strtolower($key);
        return !isset($this->stopwordList[$key]);
    }
}
