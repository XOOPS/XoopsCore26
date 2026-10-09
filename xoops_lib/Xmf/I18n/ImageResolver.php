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
 * Locale-aware image path resolver with direction fallbacks.
 *
 * @category  Xmf\I18n\ImageResolver
 * @package   Xmf
 * @author    MAMBA <mambax7@gmail.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
final class ImageResolver
{
    /** @var array<string, string> */
    private static array $cache = [];

    private const MAX_CACHE_SIZE = 200;

    /**
     * Resolve an image path with language/direction fallbacks.
     *
     * Order (base = images/arrow.png, lang = pt-BR, dir = rtl):
     *   images/arrow.pt-br.png
     *   images/arrow.pt.png
     *   images/arrow.rtl.png
     *   images/arrow.ltr.png
     *   images/arrow.png
     *
     * @param string      $basePath Base image path (relative or absolute URL)
     * @param string|null $lang     Locale code, or null for global locale
     * @param string|null $dir      'ltr' or 'rtl', or null for auto-detection
     *
     * @return string Resolved image path
     */
    public static function resolve(string $basePath, ?string $lang = null, ?string $dir = null): string
    {
        if ($basePath === '') {
            return '';
        }

        // Absolute URL? Return as-is
        if (\preg_match('#^(https?:)?//#i', $basePath) === 1) {
            return $basePath;
        }

        // Reject path traversal attempts
        if (\strpos($basePath, '..') !== false) {
            return $basePath;
        }

        $isLegacy = !\class_exists('Xoops', false);

        if ($lang === null && $isLegacy && \defined('_LANGCODE')) {
            /** @var mixed $langCode */
            $langCode = \constant('_LANGCODE');
            if (\is_string($langCode)) {
                $lang = $langCode;
            }
        }
        if ($lang === null) {
            $lang = 'en';
        }

        // Reject lang values containing path traversal sequences
        if (\strpos($lang, '..') !== false || \strpos($lang, '/') !== false) {
            return $basePath;
        }

        $dir = $dir ?? Direction::dir($lang);
        if ($dir !== Direction::LTR && $dir !== Direction::RTL) {
            $dir = Direction::dir($lang);
        }

        $cacheKey = \strtolower($lang) . "\0" . $dir . "\0" . $basePath;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $parts     = \pathinfo($basePath);
        $dirname   = $parts['dirname'] === '.' ? '' : $parts['dirname'];
        $filename  = $parts['filename'];
        $extension = $parts['extension'] ?? '';
        if ($filename === '' || $extension === '') {
            return $basePath; // malformed path
        }

        $prefix = $dirname === '' ? '' : ($dirname === '/' ? '/' : $dirname . '/');

        $otherDir = ($dir === Direction::RTL) ? Direction::LTR : Direction::RTL;

        $candidates = [];
        foreach (self::expandLang($lang) as $l) {
            $candidates[] = $prefix . "{$filename}.{$l}.{$extension}";
        }
        $candidates[] = $prefix . "{$filename}.{$dir}.{$extension}";
        $candidates[] = $prefix . "{$filename}.{$otherDir}.{$extension}";
        $candidates[] = $basePath;

        // Resolve XOOPS_ROOT_PATH via constant() to avoid PHPStan stub narrowing
        $root = '';
        if (\defined('XOOPS_ROOT_PATH')) {
            /** @var mixed $rootValue */
            $rootValue = \constant('XOOPS_ROOT_PATH');
            if (\is_string($rootValue) && $rootValue !== '') {
                $root = \rtrim($rootValue, '/');
            }
        }

        $result = $basePath;
        if ($root !== '') {
            foreach ($candidates as $rel) {
                $full = $root . '/' . \ltrim($rel, '/');
                if (\is_file($full)) {
                    $result = $rel;
                    break;
                }
            }
        }

        self::remember($cacheKey, $result);
        return $result;
    }

    /**
     * Expand a locale code into candidate suffixes.
     *
     * @param string $lang Locale code (e.g. 'pt-BR')
     *
     * @return string[] e.g. ['pt-br', 'pt']
     */
    private static function expandLang(string $lang): array
    {
        $lang = \strtolower(\str_replace('_', '-', \trim($lang)));
        if ($lang === '') {
            return [];
        }
        $parts = \explode('-', $lang, 2);
        return isset($parts[1]) ? [$lang, $parts[0]] : [$lang];
    }

    /**
     * Store a resolved path in the cache.
     *
     * @param string $key   Cache key
     * @param string $value Resolved path
     *
     * @return void
     */
    private static function remember(string $key, string $value): void
    {
        if (\count(self::$cache) >= self::MAX_CACHE_SIZE) {
            \array_shift(self::$cache);
        }
        self::$cache[$key] = $value;
    }

    /**
     * Clear the resolution cache (useful for testing).
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
