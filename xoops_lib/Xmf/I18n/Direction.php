<?php

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

declare(strict_types=1);

namespace Xmf\I18n;

/**
 * Text direction detection and caching for RTL/LTR layouts.
 *
 * @category  Xmf\I18n\Direction
 * @package   Xmf
 * @author    MAMBA <mambax7@gmail.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
final class Direction
{
    public const LTR  = 'ltr';
    public const RTL  = 'rtl';
    public const AUTO = 'auto';

    private static ?string $cachedDir = null;

    /** @var array<string, string> */
    private static array $cacheByLocale = [];

    private static bool $rtlDeprecationWarned = false;

    private const MAX_LOCALE_CACHE = 50;

    private const RTL_LANGS = [
        'ar',  // Arabic
        'arc', // Aramaic
        'bcc', // Southern Balochi
        'bqi', // Bakhtiari
        'ckb', // Central Kurdish
        'dv',  // Dhivehi
        'fa',  // Persian
        'glk', // Gilaki
        'he',  // Hebrew
        'iw',  // Hebrew (old code)
        'khw', // Khowar
        'ks',  // Kashmiri
        'ku',  // Kurdish
        'mzn', // Mazanderani
        'pnb', // Western Punjabi
        'ps',  // Pashto
        'sd',  // Sindhi
        'ug',  // Uyghur
        'ur',  // Urdu
        'yi',  // Yiddish
    ];

    private const RTL_SCRIPTS = ['Arab', 'Hebr', 'Thaa', 'Syrc', 'Nkoo', 'Adlm', 'Mand', 'Samr'];

    /**
     * Get text direction for a locale.
     *
     * @param string|null $locale Locale code, or null/AUTO for global locale
     *
     * @return string 'ltr' or 'rtl'
     */
    public static function dir(?string $locale = null): string
    {
        if ($locale === self::AUTO) {
            $locale = null;
        }

        $isGlobal = ($locale === null);

        if ($isGlobal && self::$cachedDir !== null) {
            return self::$cachedDir;
        }

        $isLegacy = !\class_exists('Xoops', false);

        $resolved = $locale;
        if ($resolved === null && $isLegacy && \defined('_LANGCODE')) {
            /** @var mixed $langCode */
            $langCode = \constant('_LANGCODE');
            if (\is_string($langCode)) {
                $resolved = $langCode;
            }
        }
        if ($resolved === null) {
            $resolved = 'en';
        }

        if (!$isGlobal && isset(self::$cacheByLocale[$resolved])) {
            return self::$cacheByLocale[$resolved];
        }

        $result = null;

        // Priority 1: Explicit _TEXT_DIRECTION (normalized for robustness)
        if ($isGlobal && $isLegacy && \defined('_TEXT_DIRECTION')) {
            /** @var mixed $raw */
            $raw = \constant('_TEXT_DIRECTION');
            if (\is_string($raw)) {
                $decl = \strtolower(\trim($raw));
                if (\in_array($decl, [self::RTL, self::LTR], true)) {
                    $result = $decl;
                } else {
                    \trigger_error(
                        'Constant _TEXT_DIRECTION has invalid value "' . $raw
                        . '". Expected \'ltr\' or \'rtl\'.',
                        E_USER_WARNING
                    );
                }
            }
        }

        // Priority 2: Legacy _RTL constant
        if ($result === null && $isGlobal && $isLegacy && \defined('_RTL')) {
            if (!self::$rtlDeprecationWarned) {
                \trigger_error(
                    'Constant _RTL is deprecated. Define _TEXT_DIRECTION as \'ltr\' or \'rtl\' instead.',
                    E_USER_DEPRECATED
                );
                self::$rtlDeprecationWarned = true;
            }
            $result = ((bool) \constant('_RTL')) ? self::RTL : self::LTR;
        }

        // Priority 3: Auto-detect from locale
        if ($result === null) {
            $result = self::detect($resolved);
        }

        // Cache
        if ($isGlobal) {
            self::$cachedDir = $result;
        } else {
            if (\count(self::$cacheByLocale) >= self::MAX_LOCALE_CACHE) {
                \array_shift(self::$cacheByLocale);
            }
            self::$cacheByLocale[$resolved] = $result;
        }

        return $result;
    }

    /**
     * Check if a locale uses right-to-left text direction.
     *
     * @param string|null $locale Locale code, or null for global locale
     *
     * @return bool True if RTL, false if LTR
     */
    public static function isRtl(?string $locale = null): bool
    {
        return self::dir($locale) === self::RTL;
    }

    /**
     * Core detection logic.
     *
     * @param string $locale Resolved locale code (never null)
     *
     * @return string 'ltr' or 'rtl'
     */
    private static function detect(string $locale): string
    {
        $locale = \trim($locale);
        if ($locale === '') {
            return self::LTR;
        }

        $norm    = \str_replace('_', '-', \strtolower($locale));
        $primary = \explode('-', $norm, 2)[0];

        if (\in_array($primary, self::RTL_LANGS, true)) {
            return self::RTL;
        }

        if (\extension_loaded('intl')) {
            try {
                $script = \Locale::getScript($norm) ?: '';
                if (\in_array($script, self::RTL_SCRIPTS, true)) {
                    return self::RTL;
                }
            } catch (\IntlException $e) {
                $debug = (\defined('XOOPS_DEBUG_MODE') && (bool) \constant('XOOPS_DEBUG_MODE'))
                         || (\defined('XOOPS_DEBUG') && (bool) \constant('XOOPS_DEBUG'));
                if ($debug) {
                    \error_log(
                        'Direction: ICU script detection failed for locale "'
                        . $locale . '": ' . $e->getMessage()
                    );
                }
            }
        }

        return self::LTR;
    }

    /**
     * Clear cached direction (useful for testing or runtime locale changes).
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cachedDir = null;
        self::$cacheByLocale = [];
        self::$rtlDeprecationWarned = false;
    }
}
