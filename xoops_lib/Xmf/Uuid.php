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
 * Generate UUID
 *
 * @category  Xmf\Uuid
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class Uuid
{
    // match spec for version 4 UUID as per rfc4122
    public const UUID_REGEX = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    /**
     * generate - generate a version 4 (random) UUID
     *
     * Based on comment by pavel.volyntsev(at)gmail at https://php.net/manual/en/function.com-create-guid.php
     *
     * @return string UUID
     *
     * @throws \Exception on insufficient entropy
     */
    public static function generate()
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Pack a UUID into a binary string
     *
     * @param string $uuid a valid UUID
     *
     * @return string packed UUID as a binary string
     *
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public static function packAsBinary($uuid)
    {
        if (!preg_match(static::UUID_REGEX, $uuid)) {
            throw new \InvalidArgumentException('Invalid UUID');
        }
        $return = pack("H*", str_replace('-', '', $uuid));
        if (false === $return) {
            throw new \UnexpectedValueException('Packing UUID Failed');
        }
        return $return;
    }

    /**
     * Unpack a UUID stored as a binary string
     *
     * @param string $packedUuid a packed UUID as returned by packAsBinary()
     *
     * @return string unpacked UUID
     *
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public static function unpackBinary($packedUuid)
    {
        if (16 !== strlen($packedUuid)) {
            throw new \InvalidArgumentException('Invalid packed UUID');
        }
        $return = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($packedUuid), 4));
        if (!preg_match(static::UUID_REGEX, $return)) {
            throw new \UnexpectedValueException('Unpacking UUID Failed');
        }
        return $return;
    }
}
