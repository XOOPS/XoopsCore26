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
 * @category  Xmf\IPAddress
 * @package   Xmf
 * @author    trabis <lusopoemas@gmail.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class IPAddress
{
    /** @var false|string presentation form of ip address, or false if invalid */
    protected $ip;

    /**
     * IPAddress constructor.
     * @param string $ip IP address
     */
    public function __construct($ip)
    {
        if (!filter_var((string) $ip, FILTER_VALIDATE_IP)) {
            $this->ip = false;
        } else {
            $this->ip = $this->normalize($ip);
        }
    }

    /**
     * Get IP address from the request server data
     *
     * @return IPAddress
     */
    public static function fromRequest()
    {
        $ip = (array_key_exists('REMOTE_ADDR', $_SERVER) && is_string($_SERVER['REMOTE_ADDR']))
            ? $_SERVER['REMOTE_ADDR']
            : '0.0.0.0';
        $class = get_called_class();
        $proxyCheck = new ProxyCheck();
        $proxyIP = $proxyCheck->get();
        $ip = (false === $proxyIP) ? $ip : $proxyIP;
        return new $class($ip);
    }

    /**
     * convert IP address into a normalized condensed notation
     *
     * @param string $ip ip address to normalize
     *
     * @return string|false normalized address or false on failure
     */
    protected function normalize($ip)
    {
        $packed = inet_pton($ip);
        if ($packed === false) {
            return false;
        }
        return inet_ntop($packed);
    }

    /**
     * return presentation form of address
     *
     * @return string|false
     */
    public function asReadable()
    {
        return $this->ip;
    }

    /**
     * get network (binary) form of address
     *
     * @return string|false
     */
    public function asBinary()
    {
        if (false === $this->ip) {
            return false;
        }
        return inet_pton($this->ip);
    }

    /**
     * get the ip version, 4 or 6, of address
     *
     * @return int|false integer 4 for IPV4, 6 for IPV6, or false if invalid
     */
    public function ipVersion()
    {
        $return = false;
        if (false === $this->ip) {
            $return = false;
        } elseif (false !== filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $return = 4;
        } elseif (false !== filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $return = 6;
        }
        return $return;
    }

    /**
     * Is this IP in the same subnet as the supplied address?
     *
     * Accepts net masks for both IPV4 and IPV6 and will select the appropriate one, to
     * allow checking policy against request input with minimal method calls.
     *
     * @param string $matchIp  presentation form ip address to compare
     * @param int    $netMask4 network mask, bits to match <= 32 for IPV4
     * @param int    $netMask6 network mask, bits to match <=128 for IPV6
     *
     * @return bool true if $this->ip and $matchIp are both in the specified subnet
     */
    public function sameSubnet($matchIp, $netMask4, $netMask6)
    {
        $match = new IPAddress($matchIp);
        if (false !== $this->ipVersion() && ($this->ipVersion() === $match->ipVersion())) {
            switch ($this->ipVersion()) {
                case 4:
                    $mask = (-1) << (32 - $netMask4);
                    return ((ip2long($this->ip) & $mask) === (ip2long($match->asReadable()) & $mask));
                case 6:
                    $ipBits = $this->asBinaryString($this);
                    $matchBits = $this->asBinaryString($match);
                    return (0 === strncmp($ipBits, $matchBits, $netMask6));
                default:
                    break;
            }
        }
        return false;
    }

    /**
     * Convert an IP address to a binary character string (i.e. "01111111000000000000000000000001")
     *
     * @param IPAddress $ip address object
     *
     * @return string
     */
    protected function asBinaryString(IPAddress $ip)
    {
        $length = (4 === $ip->ipVersion()) ? 4 : 16;
        $binaryIp = $ip->asBinary();
        $bits = '';
        for ($i = 0; $i < $length; ++$i) {
            $byte = decbin(ord($binaryIp[$i]));
            $bits .= substr("00000000" . $byte, -8);
        }
        return $bits;
    }
}
