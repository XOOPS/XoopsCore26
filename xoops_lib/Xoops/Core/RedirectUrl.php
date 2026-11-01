<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xoops\Core;

/**
 * Same-site checks for redirect targets.
 *
 * sanitize() is the gate in Xoops::redirect(); validate() and afterLogin() are
 * for targets taken from the request, such as xoops_redirect.
 *
 * @category  Xoops\Core
 * @package   RedirectUrl
 * @author    XOOPS Development Team
 * @copyright 2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
final class RedirectUrl
{
    private const HISTORY_BACK = '/^\b(java)?script:(\s*)history\.go\(-\d*\)(\s*;*\s*)$/si';

    /**
     * Make a redirect target safe to send.
     *
     * A target with control characters, "about:" or "script:" becomes the base
     * URL, except "javascript:history.go(-n)". Unless external links are
     * allowed, a target with a scheme or a leading slash must also pass
     * isLocal(). Bare relative paths ("index.php") are left alone.
     *
     * @param string $url           redirect target
     * @param string $baseUrl       site base URL (XOOPS_URL)
     * @param bool   $allowExternal allow targets on other sites
     *
     * @return string the target, or $baseUrl
     */
    public static function sanitize(string $url, string $baseUrl, bool $allowExternal = false): string
    {
        if (1 === preg_match(self::HISTORY_BACK, $url)) {
            return $url;
        }
        $decoded = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (1 === preg_match('/[\x00-\x1F\x7F]|about:|script:/i', $decoded)) {
            return $baseUrl;
        }
        if ($allowExternal) {
            return $url;
        }
        // Browsers read "\" as "/", so "/\host" is scheme-relative.
        $probe = ltrim(str_replace('\\', '/', $decoded));
        if ((null !== parse_url($probe, PHP_URL_SCHEME) || str_starts_with($probe, '/'))
            && !self::isLocal($url, $baseUrl)
        ) {
            return $baseUrl;
        }

        return $url;
    }

    /**
     * Decide whether an absolute, scheme-relative or root-relative URL points at this site.
     *
     * Scheme, host and effective port must equal those of $baseUrl. A look-alike
     * host ("site.test.evil.test"), userinfo ("site.test@evil.test") and "//host"
     * do not match. A root-relative path ("/index.php") is local.
     *
     * @param string $url     candidate URL
     * @param string $baseUrl site base URL
     *
     * @return bool
     */
    public static function isLocal(string $url, string $baseUrl): bool
    {
        // A Location header reads the raw value, an HTML page the decoded one:
        // "https://site.test&quest;@evil.test/" has host site.test only once decoded.
        $decoded = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return self::readingIsLocal($decoded, $baseUrl)
            && ($decoded === $url || self::readingIsLocal($url, $baseUrl));
    }

    /**
     * isLocal() for one reading (raw or entity-decoded) of a URL.
     *
     * @param string $url     URL as one client reads it
     * @param string $baseUrl site base URL
     *
     * @return bool
     */
    private static function readingIsLocal(string $url, string $baseUrl): bool
    {
        if (1 === preg_match('/[\x00-\x1F\x7F]/', $url) || self::headIsAmbiguous($url)) {
            return false;
        }
        $parts = parse_url($url);
        if (false === $parts) {
            return false;
        }
        if (!isset($parts['host'])) {
            // "http:/evil.test" has a scheme but no host; browsers may still leave the site.
            return !isset($parts['scheme'])
                && str_starts_with($parts['path'] ?? '', '/')
                && !str_starts_with($url, '//');
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }
        $base = parse_url($baseUrl) ?: [];
        if (!isset($base['host'])) {
            return false;
        }
        $baseScheme = strtolower($base['scheme'] ?? 'http');
        $scheme = strtolower($parts['scheme'] ?? $baseScheme);

        return 0 === strcasecmp($parts['host'], $base['host'])
            && $scheme === $baseScheme
            && self::port($scheme, $parts['port'] ?? null) === self::port($baseScheme, $base['port'] ?? null);
    }

    /**
     * Validate a redirect target taken from the request.
     *
     * Accepts a root-relative path or an absolute URL on this site, inside the
     * base path, with no ".." segment (literal or encoded). Rejects bare
     * relative paths, which resolve against the current page.
     *
     * @param string $redirect untrusted target
     * @param string $baseUrl  site base URL
     *
     * @return string the trimmed target, or '' when it is not safe
     */
    public static function validate(string $redirect, string $baseUrl): string
    {
        $redirect = trim($redirect);
        if ('' === $redirect || !self::isLocal($redirect, $baseUrl)) {
            return '';
        }
        $basePath = rtrim((string) (parse_url($baseUrl, PHP_URL_PATH) ?? ''), '/');
        // Check the raw and the entity-decoded reading, as isLocal() does.
        foreach ([$redirect, html_entity_decode($redirect, ENT_QUOTES | ENT_HTML5, 'UTF-8')] as $reading) {
            if (!str_starts_with($reading, '/') && null === parse_url($reading, PHP_URL_SCHEME)) {
                return '';
            }
            $path = (string) (parse_url($reading, PHP_URL_PATH) ?? '/');
            if (in_array('..', explode('/', rawurldecode($path)), true)) {
                return '';
            }
            if ('' !== $basePath && $path !== $basePath && !str_starts_with($path, $basePath . '/')) {
                return '';
            }
        }

        return $redirect;
    }

    /**
     * Build the absolute URL to send a user to after login.
     *
     * $redirect is the posted xoops_redirect value, normally the URL-encoded
     * REQUEST_URI of the page that showed the login form. A root-relative path
     * without the base path of a subdirectory install gets it prepended. An
     * empty value, a registration page or a target validate() refuses gives
     * the site's index.php.
     *
     * @param string $redirect posted xoops_redirect value
     * @param string $baseUrl  site base URL
     *
     * @return string absolute URL on this site
     */
    public static function afterLogin(string $redirect, string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        $fallback = $baseUrl . '/index.php';
        $target = rawurldecode($redirect);
        if ('' === trim($target)) {
            return $fallback;
        }

        // Judge the path the browser will request: strip up to three layers of
        // entities and percent-encoding, then cut the query ("&#x72;" contains a "#").
        $probe = $target;
        for ($i = 0; $i < 3; ++$i) {
            $next = rawurldecode(html_entity_decode($probe, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($next === $probe) {
                break;
            }
            $probe = $next;
        }
        if (str_contains(strtolower(preg_split('/[?#]/', $probe, 2)[0]), 'register')) {
            return $fallback;
        }

        $base = parse_url($baseUrl) ?: [];
        $basePath = rtrim($base['path'] ?? '', '/');
        $path = preg_split('/[?#]/', $target, 2)[0];
        if ('' !== $basePath
            && str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && $path !== $basePath
            && !str_starts_with($path, $basePath . '/')
        ) {
            $target = $basePath . $target;
        }

        $target = self::validate($target, $baseUrl);
        if ('' === $target) {
            return $fallback;
        }
        if (!str_starts_with($target, '/')) {
            return $target;
        }

        return strtolower($base['scheme'] ?? 'http') . '://' . ($base['host'] ?? '')
            . (isset($base['port']) ? ':' . (int) $base['port'] : '') . $target;
    }

    /**
     * Browsers treat "\" as "/" in the scheme, authority and path, and clients may
     * decode "%2f"/"%5c" there, so parse_url() and the browser can disagree.
     * The query and fragment are data and may contain them.
     *
     * @param string $url entity-decoded URL
     *
     * @return bool
     */
    private static function headIsAmbiguous(string $url): bool
    {
        $head = preg_split('/[?#]/', $url, 2)[0];

        return str_contains($head, '\\') || 1 === preg_match('/%(?:2f|5c)/i', $head);
    }

    /**
     * @param string          $scheme lower-case scheme
     * @param int|string|null $port   explicit port
     *
     * @return int|null
     */
    private static function port(string $scheme, $port): ?int
    {
        if (null !== $port) {
            return (int) $port;
        }

        return ['http' => 80, 'https' => 443][$scheme] ?? null;
    }
}
