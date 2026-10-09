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
 * ProxyCheck
 *
 * @category  Xmf\ProxyCheck
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class ProxyCheck
{
    public const PROXY_ENVIRONMENT_VARIABLE = 'proxy_env';

    public const FORWARDED = 'HTTP_FORWARDED';

    /** @var string|false header name determines how to process */
    protected $proxyHeaderName = false;

    /** @var string|false header data to process */
    protected $proxyHeader = false;

    /**
     * ProxyCheck constructor.
     */
    public function __construct()
    {
        /* must declare expected proxy in $xoopsConfig['proxy_env'] */
        $this->proxyHeaderName = $this->getProxyEnvConfig();
        $this->proxyHeader = $this->getProxyHeader();
    }

    /**
     * Get IP address from proxy header specified in $xoopsConfig['proxy_env']
     *
     * Returns proxy revealed valid client address, or false if such address was
     * not found.
     *
     * @return string|false
     */
    public function get()
    {
        if (false === $this->proxyHeaderName || false === $this->proxyHeader) {
            return false;
        }
        $proxyVars = $this->splitOnComma($this->proxyHeader);
        // only consider the first (left most) value
        $header = reset($proxyVars);
        $ip = false;
        switch ($this->proxyHeaderName) {
            case static::FORWARDED:
                $ip = $this->getFor($header);
                break;
            default:
                $ip = $this->getXForwardedFor($header);
                break;
        }

        return $ip;
    }

    /**
     * Split comma delimited string
     *
     * @param string $header
     *
     * @return string[]
     */
    protected function splitOnComma($header)
    {
        $parts = explode(',', $header);
        return array_map('trim', $parts);
    }

    /**
     * get configured proxy environment variable
     *
     * @return string|bool
     */
    protected function getProxyEnvConfig()
    {
        global $xoopsConfig;

        /* must declare expected proxy in $xoopsConfig['proxy_env'] */
        if (
            !isset($xoopsConfig[static::PROXY_ENVIRONMENT_VARIABLE])
            || empty($xoopsConfig[static::PROXY_ENVIRONMENT_VARIABLE])
        ) {
            return false;
        }
        return trim($xoopsConfig[static::PROXY_ENVIRONMENT_VARIABLE]);
    }

    /**
     * get the configured proxy header
     *
     * @return string|false
     */
    protected function getProxyHeader()
    {
        if (false === $this->proxyHeaderName || empty($_SERVER[$this->proxyHeaderName])) {
            return false;
        }

        // Use PHP 5.3 compatible type casting
        return (string)$_SERVER[$this->proxyHeaderName];
    }

    /**
     * Extract 'for' IP address in FORWARDED header as in RFC 7239
     *
     * @param string $header
     *
     * @return string|false IP address, or false if invalid
     */
    protected function getFor($header)
    {
        $start = strpos($header, 'for=');
        if ($start === false) {
            return false;
        }
        $ip = substr($header, $start + 4);
        $end = strpos($ip, ';');
        if ($end !== false) {
            $ip = substr($ip, 0, $end);
        }
        $ip = trim($ip, '"[] ');

        return $this->validateRoutableIP($ip);
    }

    /**
     * Process an X-Forwarded-For or Client-IP style header
     *
     * @param string $ip expected to be an IP address
     *
     * @return string|false IP address, or false if invalid
     */
    protected function getXForwardedFor($ip)
    {
        return $this->validateRoutableIP($ip);
    }

    /**
     * Validate that an IP address is routable
     *
     * @param string $ip an IP address to validate
     *
     * @return string|false IP address or false if invalid
     */
    protected function validateRoutableIP($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        return $ip;
    }
}
