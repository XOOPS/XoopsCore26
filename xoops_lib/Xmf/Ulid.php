<?php

declare(strict_types=1);

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
 * Generate ULID (Universally Unique Lexicographically Sortable Identifier)
 *
 * ULID Specification: https://github.com/ulid/spec
 * - 128-bit compatibility with UUID
 * - 1.21e+24 unique ULIDs per millisecond
 * - Lexicographically sortable
 * - Canonically encoded as a 26 character string
 * - Uses Crockford's base32 for better efficiency and readability
 *
 * Structure:
 * - 10 characters: Timestamp (48 bits, milliseconds since Unix Epoch)
 * - 16 characters: Randomness (80 bits)
 *
 * Requirements:
 * - PHP 7.4+ (per composer.json requirement)
 * - ext-bcmath (for UUID and binary conversion methods)
 *
 * @category  Xmf\Ulid
 * @package   Xmf
 * @author    Michael Beck <mambax7@gmail.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class Ulid
{
    /**
     * Crockford's Base32 alphabet (excludes I, L, O, U to avoid confusion)
     */
    public const ENCODING_CHARS = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * Number of characters in the encoding alphabet
     */
    public const ENCODING_LENGTH = 32;

    /**
     * Length of the time portion in characters
     */
    public const TIME_LENGTH = 10;

    /**
     * Length of the random portion in characters
     */
    public const RANDOM_LENGTH = 16;

    /**
     * Total ULID length in characters
     */
    public const ULID_LENGTH = 26;

    /**
     * Maximum valid timestamp (2^48 - 1 milliseconds, year 10889)
     */
    public const MAX_TIME = 281474976710655;

    /**
     * Length of binary ULID representation in bytes (128 bits)
     */
    public const BINARY_LENGTH = 16;

    /**
     * @var int|null Last timestamp used for monotonic generation
     */
    private static $lastTime = null;

    /**
     * @var string|null Last randomness used for monotonic generation
     */
    private static $lastRandom = null;

    /**
     * Ensure we are running on a 64-bit PHP build.
     *
     * All ULID timestamp operations require 64-bit integers because
     * millisecond timestamps exceed PHP_INT_MAX on 32-bit builds.
     *
     * @throws \RuntimeException If PHP_INT_SIZE < 8
     */
    private static function require64Bit(): void
    {
        if (\PHP_INT_SIZE < 8) {
            throw new \RuntimeException(
                'ULID timestamp operations require a 64-bit PHP build.'
            );
        }
    }

    /**
     * Generate a new ULID.
     *
     * @param bool $upperCase Whether to return uppercase (default) or lowercase
     *
     * @return string The generated ULID (26 characters)
     * @throws \Exception If random_bytes() fails
     */
    public static function generate(bool $upperCase = true): string
    {
        $time = self::currentTimeMillis();
        $timeChars = self::encodeTime($time);
        $randChars = self::encodeRandomness();
        $ulid      = $timeChars . $randChars;

        return $upperCase ? $ulid : \strtolower($ulid);
    }

    /**
     * Get current time in milliseconds since Unix Epoch.
     *
     * @return int Milliseconds since 1970-01-01 00:00:00 UTC
     */
    public static function currentTimeMillis(): int
    {
        self::require64Bit();

        return (int) \floor(\microtime(true) * 1000);
    }

    /**
     * Encode a timestamp into a 10-character string.
     *
     * @param int $time Timestamp in milliseconds since Unix Epoch
     *
     * @return string 10-character encoded timestamp
     * @throws \InvalidArgumentException If timestamp is negative or exceeds maximum
     */
    public static function encodeTime(int $time): string
    {
        self::require64Bit();

        if ($time < 0) {
            throw new \InvalidArgumentException('Timestamp cannot be negative');
        }

        if ($time > self::MAX_TIME) {
            throw new \InvalidArgumentException(
                \sprintf('Timestamp %d exceeds maximum allowed value %d', $time, self::MAX_TIME)
            );
        }

        $chars = self::ENCODING_CHARS;
        $timeChars = '';

        for ($i = self::TIME_LENGTH - 1; $i >= 0; $i--) {
            $mod = $time % self::ENCODING_LENGTH;
            $timeChars = $chars[$mod] . $timeChars;
            $time = (int)(($time - $mod) / self::ENCODING_LENGTH);
        }
        return $timeChars;
    }

    /**
     * Encode 80 bits of randomness into a 16-character string.
     *
     * Uses an optimized bit-packing algorithm that extracts exactly 5 bits
     * at a time from 10 random bytes (80 bits total), producing 16 base32
     * characters with no wasted bits.
     *
     * Bit layout (10 bytes = 80 bits → 16 × 5-bit characters):
     * Byte 0: [4:0] → char 0, [7:5] → char 1 (partial)
     * Byte 1: [1:0] → char 1 (cont), [6:2] → char 2, [7] → char 3 (partial)
     * ... and so on
     *
     * @return string 16-character encoded randomness
     * @throws \Exception If random_bytes() fails
     */
    public static function encodeRandomness(): string
    {
        $chars = self::ENCODING_CHARS;
        $randomBytes = \random_bytes(10); // 80 bits of randomness
        $randChars   = '';

        // Convert bytes to an array of integers for bit manipulation
        /** @var string[] $byteChars */
        $byteChars = \str_split($randomBytes);
        $bytes = \array_map('ord', $byteChars);

        // Extract 16 groups of 5 bits from 80 bits (10 bytes)
        // Process two bytes at a time to avoid any integer overflow concerns
        // on 32-bit platforms. Maximum buffer size stays well within safe range.
        $bitBuffer = 0;
        $bitsInBuffer = 0;
        $byteIndex = 0;

        for ($i = 0; $i < self::RANDOM_LENGTH; $i++) {
            // Ensure we have at least 5 bits in the buffer
            while ($bitsInBuffer < 5 && $byteIndex < 10) {
                $bitBuffer = ($bitBuffer << 8) | $bytes[$byteIndex];
                $bitsInBuffer += 8;
                $byteIndex++;
            }

            // Extract the top 5 bits
            $bitsInBuffer -= 5;
            $value = ($bitBuffer >> $bitsInBuffer) & 0x1F;
            // Mask the buffer to prevent unbounded growth
            $bitBuffer &= (1 << $bitsInBuffer) - 1;
            $randChars .= $chars[$value];
        }
        return $randChars;
    }

    /**
     * Decode a ULID string into its components.
     *
     * @param string $ulid The ULID string to decode
     *
     * @return array{time: int, rand: string} Decoded components
     * @throws \InvalidArgumentException If the ULID is invalid
     */
    public static function decode(string $ulid): array
    {
        if (!self::isValid($ulid)) {
            throw new \InvalidArgumentException('Invalid ULID string');
        }

        $ulid = \strtoupper($ulid);

        return [
            'time' => self::decodeTime($ulid),
            'rand' => \substr($ulid, self::TIME_LENGTH),
        ];
    }

    /**
     * Decode the timestamp from a ULID string.
     *
     * @param string $ulid The ULID string
     *
     * @return int Timestamp in milliseconds since Unix Epoch
     * @throws \InvalidArgumentException If the ULID is invalid
     */
    public static function decodeTime(string $ulid): int
    {
        self::require64Bit();

        if (!self::isValid($ulid)) {
            throw new \InvalidArgumentException('Invalid ULID string');
        }

        $ulid = \strtoupper($ulid);
        $time = 0;

        for ($i = 0; $i < self::TIME_LENGTH; $i++) {
            $pos = \strpos(self::ENCODING_CHARS, $ulid[$i]);
            if ($pos === false) {
                // Callers validate via isValid() first, so this is an invariant guard;
                // it also keeps a stray false from being silently coerced to 0.
                throw new \InvalidArgumentException(
                    \sprintf('Invalid ULID character at position %d (0x%02X)', $i, \ord($ulid[$i]))
                );
            }
            $time = $time * self::ENCODING_LENGTH + $pos;
        }

        return $time;
    }

    /**
     * Decode the randomness portion from a ULID string.
     *
     * Returns the 16-character base32 string representation of the
     * random portion. Previous versions returned an integer, which is
     * a backward-incompatible change (see changelog for v1.2.32).
     *
     * @param string $ulid The ULID string
     *
     * @return string The 16-character randomness portion
     * @throws \InvalidArgumentException If the ULID is invalid
     *
     * @since 1.2.32 Return type changed from int to string
     */
    public static function decodeRandomness(string $ulid): string
    {
        if (!self::isValid($ulid)) {
            throw new \InvalidArgumentException('Invalid ULID string');
        }

        return \substr(\strtoupper($ulid), self::TIME_LENGTH);
    }

    /**
     * Validate a ULID string.
     *
     * @param string $ulid The string to validate
     *
     * @return bool True if valid, false otherwise
     */
    public static function isValid(string $ulid): bool
    {
        // Check length
        if (\strlen($ulid) !== self::ULID_LENGTH) {
            return false;
        }

        // Normalize to uppercase for validation
        $ulid = \strtoupper($ulid);

        // Check all characters are valid Crockford Base32
        for ($i = 0; $i < self::ULID_LENGTH; $i++) {
            if (\strpos(self::ENCODING_CHARS, $ulid[$i]) === false) {
                return false;
            }
        }

        // Per ULID spec, timestamp is 48 bits, so the first character
        // (most significant 5 bits) must encode a value in the range 0-7.
        $firstCharPos = \strpos(self::ENCODING_CHARS, $ulid[0]);
        if ($firstCharPos === false || $firstCharPos > 7) {
            return false;
        }

        return true;
    }

    /**
     * Convert a ULID to a UUID string format.
     *
     * Requires ext-bcmath.
     *
     * @param string $ulid The ULID to convert
     *
     * @return string UUID format (xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)
     * @throws \InvalidArgumentException If the ULID is invalid
     * @throws \RuntimeException If BCMath extension is not available
     */
    public static function toUuid(string $ulid): string
    {
        if (!\extension_loaded('bcmath')) {
            throw new \RuntimeException('BCMath extension is required for UUID conversion');
        }

        if (!self::isValid($ulid)) {
            throw new \InvalidArgumentException('Invalid ULID string');
        }

        $hex = self::toHex($ulid);

        return \sprintf(
            '%s-%s-%s-%s-%s',
            \substr($hex, 0, 8),
            \substr($hex, 8, 4),
            \substr($hex, 12, 4),
            \substr($hex, 16, 4),
            \substr($hex, 20, 12)
        );
    }

    /**
     * Create a ULID from a UUID string.
     *
     * Requires ext-bcmath.
     *
     * @param string $uuid The UUID to convert (with or without hyphens)
     *
     * @return string The ULID representation
     * @throws \InvalidArgumentException If the UUID is invalid
     * @throws \RuntimeException If BCMath extension is not available
     */
    public static function fromUuid(string $uuid): string
    {
        if (!\extension_loaded('bcmath')) {
            throw new \RuntimeException('BCMath extension is required for UUID conversion');
        }

        // Remove hyphens and validate
        $hex = \str_replace('-', '', $uuid);
        $hex = \strtolower($hex);

        if (\strlen($hex) !== 32) {
            throw new \InvalidArgumentException(
                'Invalid UUID format: expected 32 hex characters, got ' . \strlen($hex)
            );
        }

        if (!\ctype_xdigit($hex)) {
            throw new \InvalidArgumentException('Invalid UUID: contains non-hexadecimal characters');
        }

        // Convert hex to decimal using BCMath
        $decimal = '0';
        for ($i = 0; $i < 32; $i++) {
            $decimal = (string) \bcmul($decimal, '16');
            $decimal = (string) \bcadd($decimal, (string) \hexdec($hex[$i]));
        }

        // Convert decimal to base32 (ULID)
        $ulid = '';
        for ($i = 0; $i < self::ULID_LENGTH; $i++) {
            $remainder = (int) \bcmod($decimal, '32');
            $ulid = self::ENCODING_CHARS[$remainder] . $ulid;
            $decimal = (string) \bcdiv($decimal, '32', 0);
        }

        return $ulid;
    }

    /**
     * Convert a ULID to hexadecimal representation.
     *
     * @param string $ulid The ULID to convert
     *
     * @return string 32-character hexadecimal string
     *
     * @throws \InvalidArgumentException if $ulid contains a non-base32 character
     */
    private static function toHex(string $ulid): string
    {
        $ulid = \strtoupper($ulid);

        // Convert base32 to a big integer, then to hex
        // Using BCMath for arbitrary precision
        $decimal = '0';

        for ($i = 0; $i < self::ULID_LENGTH; $i++) {
            $value = \strpos(self::ENCODING_CHARS, $ulid[$i]);
            if ($value === false) {
                // Callers validate first (isValid()), so this is an invariant guard;
                // it also gives bcadd() a guaranteed numeric-string second argument.
                throw new \InvalidArgumentException(
                    \sprintf('Invalid ULID character at position %d (0x%02X)', $i, \ord($ulid[$i]))
                );
            }
            $decimal = \bcmul($decimal, '32');
            $decimal = \bcadd($decimal, (string) $value);
        }

        // Convert decimal to hex
        $hex = '';
        while (\bccomp($decimal, '0') > 0) {
            $remainder = (int) \bcmod($decimal, '16');
            $hex = \dechex($remainder) . $hex;
            $decimal = (string) \bcdiv($decimal, '16', 0);
        }

        // Pad to 32 characters
        return \str_pad($hex, 32, '0', STR_PAD_LEFT);
    }

    /**
     * Get the timestamp from a ULID as a DateTime object.
     *
     * @param string $ulid The ULID string
     *
     * @return \DateTimeImmutable The timestamp as a DateTime object
     * @throws \InvalidArgumentException If the ULID is invalid
     */
    public static function getDateTime(string $ulid): \DateTimeImmutable
    {
        $timestamp = self::decodeTime($ulid);
        $seconds = (int) ($timestamp / 1000);
        $microseconds = ($timestamp % 1000) * 1000;

        $dateTime = \DateTimeImmutable::createFromFormat(
            'U u',
            \sprintf('%d %06d', $seconds, $microseconds),
            new \DateTimeZone('UTC')
        );

        if ($dateTime === false) {
            throw new \RuntimeException('Failed to create DateTime from ULID timestamp');
        }

        return $dateTime->setTimezone(new \DateTimeZone('UTC'));
    }

    /**
     * Compare two ULIDs.
     *
     * @param string $ulid1 First ULID
     * @param string $ulid2 Second ULID
     *
     * @return int -1 if $ulid1 < $ulid2, 0 if equal, 1 if $ulid1 > $ulid2
     */
    public static function compare(string $ulid1, string $ulid2): int
    {
        return \strcmp(\strtoupper($ulid1), \strtoupper($ulid2)) <=> 0;
    }

    /**
     * Generate a monotonically increasing ULID.
     *
     * Within the same millisecond, the random portion is incremented
     * to guarantee strict ordering. When the timestamp advances, a
     * fresh random portion is generated.
     *
     * @param bool $upperCase Whether to return uppercase (default) or lowercase
     *
     * @return string The generated ULID (26 characters)
     * @throws \Exception If random_bytes() fails
     */
    public static function generateMonotonic(bool $upperCase = true): string
    {
        $time = self::currentTimeMillis();

        if (self::$lastTime !== null && $time <= self::$lastTime && self::$lastRandom !== null) {
            // Same or earlier millisecond — use logical time and increment the random portion
            $time = self::$lastTime;

            $currentRandom = self::$lastRandom;
            $nextRandom = self::incrementBase32($currentRandom);

            if (\strcmp($nextRandom, $currentRandom) > 0) {
                // No overflow: random portion increased, keep same logical time
                self::$lastRandom = $nextRandom;
            } else {
                // Overflow: advance logical time by 1 ms and reset randomness
                $time = self::$lastTime + 1;
                self::$lastTime = $time;
                self::$lastRandom = self::encodeRandomness();
            }
        } else {
            // New (later) millisecond — generate fresh randomness and update logical time
            self::$lastRandom = self::encodeRandomness();
            self::$lastTime = $time;
        }

        $ulid = self::encodeTime($time) . self::$lastRandom;

        return $upperCase ? $ulid : \strtolower($ulid);
    }

    /**
     * Reset the monotonic generation state.
     *
     * Useful for testing or when you want to start a fresh sequence.
     *
     * @return void
     */
    public static function resetMonotonicState(): void
    {
        self::$lastTime = null;
        self::$lastRandom = null;
    }

    /**
     * Convert a ULID string to a 16-byte binary representation.
     *
     * @param string $ulid The ULID to convert
     *
     * @return string 16-byte binary string
     * @throws \InvalidArgumentException If the ULID is invalid
     * @throws \RuntimeException If BCMath extension is not available
     */
    public static function toBinary(string $ulid): string
    {
        if (!\extension_loaded('bcmath')) {
            throw new \RuntimeException('BCMath extension is required for binary conversion');
        }

        if (!self::isValid($ulid)) {
            throw new \InvalidArgumentException('Invalid ULID string');
        }

        $hex = self::toHex($ulid);

        return \hex2bin($hex);
    }

    /**
     * Create a ULID from a 16-byte binary representation.
     *
     * @param string $binary 16-byte binary string
     *
     * @return string The ULID representation
     * @throws \InvalidArgumentException If the binary length is invalid
     * @throws \RuntimeException If BCMath extension is not available
     */
    public static function fromBinary(string $binary): string
    {
        if (!\extension_loaded('bcmath')) {
            throw new \RuntimeException('BCMath extension is required for binary conversion');
        }

        if (\strlen($binary) !== self::BINARY_LENGTH) {
            throw new \InvalidArgumentException(
                'Invalid binary length: expected 16, got ' . \strlen($binary)
            );
        }

        $hex = \bin2hex($binary);

        return self::fromUuid($hex);
    }

    /**
     * Increment a base32 string by 1.
     *
     * @param string $base32 The base32 string to increment
     *
     * @return string The incremented base32 string
     */
    private static function incrementBase32(string $base32): string
    {
        $chars = self::ENCODING_CHARS;
        /** @var string[] $result */
        $result = \str_split($base32);
        $carry = true;

        for ($i = \count($result) - 1; $i >= 0 && $carry; $i--) {
            $pos = (int) \strpos($chars, $result[$i]);
            $pos++;
            if ($pos >= self::ENCODING_LENGTH) {
                $result[$i] = $chars[0];
                // carry remains true
            } else {
                $result[$i] = $chars[$pos];
                $carry = false;
            }
        }

        return \implode('', $result);
    }

    /**
     * Convert microtime to ULID-compatible millisecond timestamp.
     *
     * @param float $microtime Microtime value (e.g. from microtime(true))
     *
     * @return int Milliseconds since Unix Epoch
     *
     * @deprecated Use Ulid::currentTimeMillis() instead. This method previously
     *             subtracted a Y2K epoch offset which does not match the ULID spec.
     *             It now returns standard Unix epoch milliseconds for correctness.
     */
    public static function microtimeToUlidTime($microtime): int
    {
        \trigger_error(
            'Ulid::microtimeToUlidTime() is deprecated. Use Ulid::currentTimeMillis() instead.',
            \E_USER_DEPRECATED
        );

        return (int) \floor($microtime * 1000);
    }
}
