<?php

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Jwt;

use Xmf\Request;
use Xmf\Jwt\KeyFactory;
use Xmf\Key\KeyAbstract;

/**
 * Validate and get payload from a token string
 *
 * @category  Xmf\Jwt\TokenReader
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class TokenReader
{
    /**
     * Validate and decode a JSON Web Token string
     *
     * @param KeyAbstract|string $key          the key to use to sign the token, or name of key to build
     * @param string             $token        the token string to validate and decode
     * @param array|\Traversable $assertClaims traversable set of claims, claim => value, to assert
     *
     * @return \stdClass|false payload as stdClass, or false if token was invalid
     *
     * @throws \InvalidArgumentException on unusable key name
     */
    public static function fromString($key, $token, $assertClaims = array())
    {
        $key = ($key instanceof KeyAbstract) ? $key : KeyFactory::build($key);
        $jwt = new JsonWebToken($key);
        return $jwt->decode($token, $assertClaims);
    }

    /**
     * Validate and decode a JSON Web Token string from a cookie
     *
     * @param KeyAbstract|string $key          the key to use to sign the token, or name of key to build
     * @param string             $cookieName   name of cookie that sources the token
     * @param array|\Traversable $assertClaims traversable set of claims, claim => value, to assert
     *
     * @return \stdClass|false payload as stdClass, or false if token was invalid
     *
     * @throws \InvalidArgumentException on unusable key name
     */
    public static function fromCookie($key, $cookieName, $assertClaims = array())
    {
        $token = Request::getString($cookieName, '', 'COOKIE');
        if (empty($token)) {
            return false;
        }
        return static::fromString($key, $token, $assertClaims);
    }

    /**
     * Validate and decode a JSON Web Token string from a request (i.e. POST body)
     *
     * @param KeyAbstract|string $key          the key to use to sign the token, or name of key to build
     * @param string             $attributeName name of cookie that sources the token
     * @param array|\Traversable $assertClaims  traversable set of claims, claim => value, to assert
     *
     * @return \stdClass|false payload as stdClass, or false if token was invalid
     *
     * @throws \InvalidArgumentException on unusable key name
     */
    public static function fromRequest($key, $attributeName, $assertClaims = array())
    {
        $token = Request::getString($attributeName, '');
        if (empty($token)) {
            return false;
        }
        return static::fromString($key, $token, $assertClaims);
    }

    /**
     * Validate and decode a JSON Web Token string from a header
     *
     * @param KeyAbstract|string $key          the key to use to sign the token, or name of key to build
     * @param array|\Traversable $assertClaims traversable set of claims, claim => value, to assert
     * @param string             $headerName   name of header that sources the token
     *
     * @return \stdClass|false payload as stdClass, or false if token was invalid
     *
     * @throws \InvalidArgumentException on unusable key name
     */
    public static function fromHeader($key, $assertClaims = array(), $headerName = 'Authorization')
    {
        $header = Request::getHeader($headerName, '');
        if (empty($header)) {
            return false;
        }
        $header = trim($header);
        if (strcasecmp($headerName, 'Authorization') === 0) {
            $parts = explode(' ', $header, 2);
            if (count($parts) !== 2 || strcasecmp($parts[0], 'Bearer') !== 0) {
                return false;
            }
            $token = trim($parts[1]);
        } else {
            $token = $header;
        }
        return static::fromString($key, $token, $assertClaims);
    }
}
