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
 * Language
 *
 * @category  Xmf\Language
 * @package   Xmf
 * @author    trabis <lusopoemas@gmail.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class Language
{
    /**
     * Attempt a translation of a simple string
     *
     * @param string $string string to translate
     * @param string $domain language domain
     *
     * @return string translated string
     *
     * @todo do something useful
     */
    public static function translate($string, $domain = null)
    {
        return $string;
    }

    /**
     * load - load a language file
     *
     * @param string $name     name of the language file
     * @param string $domain   domain or module supplying language file
     * @param string $language language folder name
     *
     * @return bool true if loaded, otherwise false
     */
    public static function load($name, $domain = '', $language = null)
    {
        if (empty($language)) {
            if (!empty($GLOBALS['xoopsConfig']['language'])) {
                $language = $GLOBALS['xoopsConfig']['language'];
            } else {
                $language = 'english';
            }
        }
        $path = \XoopsBaseConfig::get('root-path') . '/' . ((empty($domain) || 'global' === $domain) ? ''
            : "modules/{$domain}/") . 'language';
        if (!$ret = static::loadFile("{$path}/{$language}/{$name}.php")) {
            $ret = static::loadFile("{$path}/english/{$name}.php");
        }

        return $ret;
    }

    /**
     * Load a file
     *
     * @param string $filename filename to load
     *
     * @return bool true if file exists and was loaded
     *
     * @throws \InvalidArgumentException
     */
    protected static function loadFile($filename)
    {
        if (preg_match('/[[:cntrl:]]/i', $filename)) {
            throw new \InvalidArgumentException('Security check: Illegal character in filename');
        }
        $realPath = realpath($filename);
        if ($realPath === false) {
            return false;
        }
        $allowedDir = realpath((string) \XoopsBaseConfig::get('root-path'));
        if ($allowedDir === false) {
            return false;
        }
        $allowedDirWithSep = rtrim($allowedDir, '/\\') . DIRECTORY_SEPARATOR;
        if (strpos($realPath, $allowedDirWithSep) !== 0 && $realPath !== $allowedDir) {
            return false;
        }
        include_once $realPath;
        return true;
    }
}
