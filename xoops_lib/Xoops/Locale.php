<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xoops;

use Punic\Data;
use Punic\Exception\InvalidLocale;
use Xoops\Core\HttpRequest;
use Xmf\Request;
use Xoops\Core\Theme\XoopsTheme;
use Xoops\Locale\MessageFormatter;

/**
 * Locale
 *
 * @author    trabis <lusopoemas@gmail.com>
 * @copyright 2011-2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 */
class Locale
{
    const FALLBACK_LOCALE = 'en_US';

    protected static $currentLocale = null;
    protected static $currentTimeZone = null;
    protected static $defaultTimeZone = null;
    protected static $systemTimeZone = null;
    protected static $userLocales = array();

    /**
     * get the current active locale
     *
     * @return string current locale
     */
    public static function getCurrent()
    {
        // if none set, take the top of the user locales
        if (null === static::$currentLocale) {
            $localeArray = static::getUserLocales();
            static::$currentLocale = reset($localeArray);
        }
        return static::$currentLocale;
    }

    /**
     * Set the current locale
     *
     * @param string $locale local code
     *
     * @return void
     *
     * @throws InvalidLocale
     */
    public static function setCurrent($locale)
    {
        Data::setDefaultLocale($locale);
        static::$currentLocale = static::normalizeLocale($locale);
    }

    /**
     * Get the current timezone
     *
     * @return \DateTimeZone current timezone
     */
    public static function getTimeZone()
    {
        if (null === static::$currentTimeZone) {
            $xoops = \Xoops::getInstance();
            static::$currentTimeZone = static::getDefaultTimeZone();
            if ($xoops->isUser()) {
                $tz = $xoops->user->timezone();
                if (is_a($tz, '\DateTimeZone')) {
                    static::$currentTimeZone = $tz;
                } elseif (is_string($tz)) {
                    static::$currentTimeZone = static::newDateTimeZone($tz);
                }
            }
        }
        return static::$currentTimeZone;
    }

    /**
     * Set the current timezone
     *
     * @param \DateTimeZone $timeZone
     *
     * @return void
     */
    public static function setTimeZone(\DateTimeZone $timeZone)
    {
        static::$currentTimeZone = $timeZone;
    }

    /**
     * Instantiate a new DateTimeZone object for a timezone name, with fallback to UTC on error
     *
     * @param string $timeZoneName name of timezone
     *
     * @return \DateTimeZone
     */
    protected static function newDateTimeZone($timeZoneName)
    {
        try {
            $timeZone = new \DateTimeZone($timeZoneName);
        } catch (\Exception $e) {
            $timeZone = new \DateTimeZone('UTC');
        }

        return $timeZone;
    }

    /**
     * Get the default timezone as set in default_TZ config
     *
     * @return \DateTimeZone
     */
    public static function getDefaultTimeZone()
    {
        if (null === static::$defaultTimeZone) {
            $tz = \Xoops::getInstance()->getConfig('default_TZ');
            if (is_numeric($tz)) {
                $tz = 'UTC';
            }
            static::$defaultTimeZone = static::newDateTimeZone($tz);
        }
        return static::$defaultTimeZone;
    }

    /**
     * Get the server timezone as set in server_TZ config
     *
     * @return \DateTimeZone
     */
    public static function getSystemTimeZone()
    {
        if (null === static::$systemTimeZone) {
            $tz = \Xoops::getInstance()->getConfig('server_TZ');
            if (is_numeric($tz)) {
                $tz = 'UTC';
            }
            static::$systemTimeZone = static::newDateTimeZone($tz);
        }
        return static::$systemTimeZone;
    }

    /**
     * @param string $name Name of language file to be loaded, without extension
     * @param mixed $domain string: Module dirname; global language file will be loaded if
     *                                 $domain is set to 'global' or not specified
     *                         array:  example; array('Frameworks/moduleclasses/moduleadmin')
     * @param string $language Language to be loaded, current language content will be loaded if not specified
     *
     * @return  boolean
     */
    public static function loadLanguage($name, $domain = '', $language = null)
    {
        if (empty($name)) {
            return false;
        }
        $language = empty($language) ? \XoopsLocale::getLegacyLanguage() : $language;
        // expanded domain to multiple categories, e.g. module:system, framework:filter, etc.
        if ((empty($domain) || 'global' === $domain)) {
            $path = '';
        } else {
            $path = (is_array($domain)) ? array_shift($domain) : "modules/{$domain}";
        }
        $xoops = \Xoops::getInstance();
        $fullPath = $xoops->path("{$path}/language/{$language}/{$name}.php");
        if (!$ret = \XoopsLoad::loadFile($fullPath)) {
            $fullPath2 = $xoops->path("{$path}/language/english/{$name}.php");
            $ret = \XoopsLoad::loadFile($fullPath2);
        }
        return $ret;
    }

    /**
     * @param string $domain module dirname to load, if null will load global locale
     * @param string $forcedLocale Locale to be loaded, current language content will be loaded if not specified
     *
     * @return  boolean
     */
    public static function loadLocale($domain = null, $forcedLocale = null)
    {
        $xoops = \Xoops::getInstance();
        // expanded domain to multiple categories, e.g. module:system, framework:filter, etc.
        if ($domain === null) {
            $path = '';
            $domain = 'xoops';
        } else {
            $path = (is_array($domain)) ? array_shift($domain) : "modules/{$domain}";
        }
        if (null !== $forcedLocale) {
            try {
                Data::setDefaultLocale($locale);
            } catch (InvalidLocale $e) {
                return false;
            }
            $locales = [$forcedLocale];
            $locale = $forcedLocale;
        } else {
            $locales = self::getUserLocales();
            $locale = reset($locales);
            try {
                Data::setDefaultLocale($locale);
            } catch (InvalidLocale $e) {
                $locale = static::FALLBACK_LOCALE;
                array_shift($locales);
                array_unshift($locales, $locale);
                Data::setDefaultLocale($locale);
            }
        }
        foreach ($locales as $locale) {
            $fullPath = $xoops->path("{$path}/locale/{$locale}/locale.php");
            $fullPath2 = $xoops->path("{$path}/locale/{$locale}/{$locale}.php");
            if (\XoopsLoad::fileExists($fullPath)) {
                \XoopsLoad::addMap(array($domain . 'locale' => $fullPath));
                if (\XoopsLoad::fileExists($fullPath2)) {
                    \XoopsLoad::addMap(array(strtolower($domain . "locale{$locale}") => $fullPath2));
                }
                return true;
            }
        }
        return false;
    }

    /**
     * load locale for theme
     *
     * @param XoopsTheme $theme
     *
     * @return bool
     */
    public static function loadThemeLocale(XoopsTheme $theme)
    {
        $xoops = \Xoops::getInstance();
        $locales = self::getUserLocales();
        foreach ($locales as $locale) {
            $fullPath = $xoops->path($theme->resourcePath("locale/{$locale}/locale.php"));
            $fullPath2 = $xoops->path($theme->resourcePath("locale/{$locale}/{$locale}.php"));
            if (\XoopsLoad::fileExists($fullPath)) {
                \XoopsLoad::addMap(array(strtolower($theme->folderName . 'ThemeLocale') => $fullPath));
                if (\XoopsLoad::fileExists($fullPath2)) {
                    \XoopsLoad::addMap(array(strtolower($theme->folderName . "ThemeLocale{$locale}") => $fullPath2));
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @return  boolean
     */
    public static function loadMailerLocale()
    {
        $xoops = \Xoops::getInstance();
        $locales = self::getUserLocales();
        foreach ($locales as $locale) {
            $fullPath = $xoops->path("locale/{$locale}/mailer.php");
            if (\XoopsLoad::fileExists($fullPath)) {
                \XoopsLoad::addMap(array(strtolower('XoopsMailerLocale') => $fullPath));
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $key
     * @param string $dirname
     * @param array  $params
     * @return string
     */
    public static function translate($key, $dirname = 'xoops', $params = [])
    {
        $class = self::getClassFromDirname($dirname);
        $message = self::getMessage($class, $key);
        return self::format($message, $params, self::getCurrent());
    }

    /**
     * @param string $key
     * @param string $dirname
     * @param array  $params
     * @return string
     */
    public static function translateTheme($key, $dirname = '', $params = [])
    {
        $class = self::getThemeClassFromDirname($dirname);
        $message = self::getMessage($class, $key);
        return self::format($message, $params, self::getCurrent());
    }

    /**
     * Returns the raw translation
     *
     * @param string $class
     * @param string $key
     * @return string
     */
    private static function getMessage($class, $key) {
        if (defined("$class::$key")) {
            return constant("$class::$key");
        } elseif (defined($key)) {
            return constant($key);
        }
        return $key;
    }

    /**
     * Formats a message using [[MessageFormatter]].
     *
     * @copyright Copyright (c) 2008 Yii Software LLC
     *
     * @param string $message the message to be formatted.
     * @param array  $params the parameters that will be used to replace the corresponding placeholders in the message.
     * @param string $language the language code (e.g. `en-US`, `en`).
     * @return string the formatted message.
     */
    private static function format($message, $params, $language)
    {
        $params = (array)$params;
        if ($params === []) {
            return $message;
        }

        if (preg_match('~{\s*[\d\w]+\s*,~u', $message)) {
            $formatter = self::getMessageFormatter();
            $result = $formatter->format($message, $params, $language);
            if ($result === false) {
                $errorMessage = $formatter->getErrorMessage();
                \Xoops::getInstance()->logger()->warning("Formatting message for language '$language' failed with error: $errorMessage. The message being formatted was: $message.", [__METHOD__]);
                return $message;
            } else {
                return $result;
            }
        }

        $p = [];
        foreach ($params as $name => $value) {
            $p['{' . $name . '}'] = $value;
        }

        return strtr($message, $p);
    }

    /**
     * Returns the message formatter instance.
     * @return MessageFormatter the message formatter to be used to format message via ICU message format.
     */
    private static function getMessageFormatter()
    {
        static $messageFormatter = null;
        if ($messageFormatter === null) {
            $messageFormatter = new MessageFormatter();
        }
        return $messageFormatter;
    }

    /**
     * @param string $dirname
     *
     * @return string
     */
    protected static function getClassFromDirname($dirname)
    {
        return ucfirst($dirname) . 'Locale';
    }

    /**
     * @param string $dirname
     *
     * @return string
     */
    protected static function getThemeClassFromDirname($dirname = '')
    {
        if (!$dirname) {
            $dirname = \Xoops::getInstance()->theme()->folderName;
        }
        return ucfirst($dirname) . 'ThemeLocale';
    }

    /**
     * getUserLocales()
     * Returns the user locales
     * Normally it returns an array like this:
     * 1. Forced language
     * 2. Language in $_GET['lang']
     * 3. Language in $_SESSION['lang']
     * 4. HTTP_ACCEPT_LANGUAGE
     * 5. Fallback language
     * Note: duplicate values are deleted.
     *
     * @return array with the user locales sorted by priority. Highest is best.
     */
    public static function getUserLocales()
    {
        if (empty(self::$userLocales)) {
            // reset user_lang array
            $userLocales = array();

            // Highest priority: forced language
            //if ($this->forcedLang != NULL) {
            //    $userLocales[] = $this->forcedLang;
            //}

            // 2nd highest priority: GET parameter 'lang'
            $requestLocale = self::normalizeLocale(Request::getString('lang', ''));
            if (!empty($requestLocale)) {
                $userLocales[] = $requestLocale;
            }

            // 3rd highest priority: SESSION parameter 'lang'
            if (isset($_SESSION['lang']) && is_string($_SESSION['lang'])) {
                $userLocales[] = self::normalizeLocale($_SESSION['lang']);
            }

            // 4th highest priority: HTTP_ACCEPT_LANGUAGE
            $browserLocales = HttpRequest::getInstance()->getAcceptedLanguages();
            $browserLocales = array_keys($browserLocales);
            foreach ($browserLocales as $bloc) {
                $userLocales[] = self::normalizeLocale($bloc);
            }

            $configLocale = \Xoops::getInstance()->getConfig('locale');
            if (!empty($configLocale)) {
                $userLocales[] = $configLocale;
            }

            // Lowest priority: fallback
            $userLocales[] = static::FALLBACK_LOCALE;

            static::$userLocales = array_unique($userLocales);
        }
        return static::$userLocales;
    }

    /**
     * Convert a locale designation to a normal form ll_Ssss_CC, where
     *   ll   is language code
     *   Ssss is the script code, if specified
     *   CC   is the country code, if specified
     *
     * @param string $locale     locale code
     * @param string $separator  string to use to join locale parts
     * @param bool   $withScript include script if specified, always remove if false
     *
     * @return string normalized locale, or empty string on error
     */
    public static function normalizeLocale($locale, $separator = '_', $withScript = true)
    {
        if (empty($locale) || !is_string($locale)) {
            return '';
        }
        try {
            $keys = Data::explodeLocale($locale);
            $key = strtolower((string) $keys['language']);
            $key .= (empty($keys['script']) || false === $withScript) ?
                '' : $separator . ucfirst(strtolower($keys['script']));
            $key .= empty($keys['territory']) ? '' : $separator . strtoupper($keys['territory']);
        } catch (InvalidLocale $e) {
            $key = '';
        }

        return $key;
    }

    /**
     * Return a normalized form of a resource domain. A resource domain is always lowercase.
     *
     * @param string $domain resource domain (usually a module dirname)
     *
     * @return string normalized resource domain
     */
    public static function normalizeDomain($domain)
    {
        return strtolower($domain);
    }
}
