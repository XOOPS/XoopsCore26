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

namespace Xmf\Security;

use JsonException;
use RuntimeException;
use UnexpectedValueException;

/**
 * Secure serialization toolkit for XOOPS/XMF
 *
 * Design principles:
 * - Explicit is better than implicit
 * - Secure by default (no objects unless explicitly allowed)
 * - Simple, focused API
 * - Compatible with PHP 7.4+
 *
 * @category  Xmf\Security
 * @package   Xmf
 * @author    MAMBA <mambax7@gmail.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
final class Serializer
{
    private const MAX_SIZE = 5000000; // 5MB
    private const JSON_DEPTH = 512;
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    /** @var \Closure|null Optional logger for legacy format detection */
    private static $legacyLogger = null;

    private static bool $debugMode = false;

    /** @var array<int, array{operation: string, format: string, time: float, memory: int, error: string|null, trace: array<int|string, mixed>|null}> */
    private static array $debugLog = [];

    private static ?float $startTime = null;

    // ═══════════════════════════════════════════════════════════
    // JSON Methods (Recommended for new code)
    // ═══════════════════════════════════════════════════════════

    /**
     * Serialize data to JSON
     *
     * @param mixed $data
     *
     * @return string
     *
     * @throws JsonException On encoding failure
     * @throws RuntimeException If encoded output exceeds MAX_SIZE
     */
    public static function toJson($data): string
    {
        $json = json_encode($data, self::JSON_FLAGS);

        if (\strlen($json) > self::MAX_SIZE) {
            throw new RuntimeException(
                sprintf('Serialized JSON exceeds maximum allowed size of %d bytes', self::MAX_SIZE)
            );
        }

        return $json;
    }

    /**
     * Deserialize JSON string
     *
     * @param string $json
     *
     * @return mixed
     *
     * @throws JsonException On invalid JSON
     * @throws UnexpectedValueException On empty input
     * @throws RuntimeException If payload exceeds MAX_SIZE
     */
    public static function fromJson(string $json)
    {
        if ($json === '') {
            throw new UnexpectedValueException('Cannot deserialize empty JSON');
        }

        if (\strlen($json) > self::MAX_SIZE) {
            throw new RuntimeException(
                sprintf('JSON payload exceeds maximum size of %d bytes', self::MAX_SIZE)
            );
        }

        return json_decode($json, true, self::JSON_DEPTH, JSON_THROW_ON_ERROR);
    }

    // ═══════════════════════════════════════════════════════════
    // PHP Serialize Methods (For complex data structures)
    // ═══════════════════════════════════════════════════════════

    /**
     * Serialize data using PHP's native format
     *
     * Note: only validates top-level types; nested resources or closures
     * within arrays/objects are not checked.
     *
     * @param mixed $data
     *
     * @return string
     *
     * @throws \InvalidArgumentException On unsupported types
     */
    public static function toPhp($data): string
    {
        if (is_resource($data)) {
            throw new \InvalidArgumentException('Cannot serialize resources');
        }
        if ($data instanceof \Closure) {
            throw new \InvalidArgumentException('Cannot serialize closures');
        }

        try {
            $serialized = serialize($data);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(
                'Failed to serialize data: contains unsupported type (possibly nested)',
                0,
                $e
            );
        }

        if (\strlen($serialized) > self::MAX_SIZE) {
            throw new RuntimeException(
                sprintf('Serialized PHP data exceeds maximum allowed size of %d bytes', self::MAX_SIZE)
            );
        }

        return $serialized;
    }

    // ═══════════════════════════════════════════════════════════
    //  Debug Mode for Serializer
    // ═══════════════════════════════════════════════════════════

    /**
     * Enable or disable debug mode
     *
     * @param bool $enable
     *
     * @return void
     */
    public static function enableDebug(bool $enable = true): void
    {
        self::$debugMode = $enable;
        if ($enable) {
            self::$startTime = microtime(true);
            self::$debugLog = [];
        } else {
            self::$startTime = null;
            self::$debugLog = [];
        }
    }

    /**
     * Get collected debug statistics
     *
     * @return array{total_operations: int, total_time: float, formats_detected: array<string, int>, slow_operations: array<int, array{operation: string, format: string, time: float, memory: int, error: string|null, trace: array<int|string, mixed>|null}>, errors: array<int, array{operation: string, format: string, time: float, memory: int, error: string, trace: array<int|string, mixed>|null}>}|array{}
     */
    public static function getDebugStats(): array
    {
        if (!self::$debugMode || self::$startTime === null) {
            return [];
        }

        $totalTime = microtime(true) - self::$startTime;
        $formats = [];
        foreach (array_count_values(array_column(self::$debugLog, 'format')) as $format => $count) {
            $formats[(string) $format] = $count;
        }

        $slowOperations = array_values(
            array_filter(self::$debugLog, static fn(array $log): bool => $log['time'] > 0.01)
        );
        /** @var array<int, array{operation: string, format: string, time: float, memory: int, error: string, trace: array<int|string, mixed>|null}> $errors */
        $errors = array_values(
            array_filter(self::$debugLog, static fn(array $log): bool => $log['error'] !== null)
        );

        return [
            'total_operations' => count(self::$debugLog),
            'total_time' => round($totalTime, 4),
            'formats_detected' => $formats,
            'slow_operations' => $slowOperations,
            'errors' => $errors
        ];
    }

    /**
     * Internal helper to log debug info
     *
     * @param string      $operation
     * @param string      $format
     * @param float       $time
     * @param string|null $error
     *
     * @return void
     */
    private static function debug(string $operation, string $format, float $time, ?string $error = null): void
    {
        if (!self::$debugMode) {
            return;
        }

        // Only capture backtrace for errors to avoid performance overhead
        $trace = null;
        if ($error !== null) {
            // Capture a short backtrace instead of relying on a fixed stack depth
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        }

        self::$debugLog[] = [
            'operation' => $operation,
            'format' => $format,
            'time' => round($time, 6),
            'memory' => memory_get_usage(true),
            'error' => $error,
            'trace' => $trace
        ];
    }

    /**
     * Deserialize PHP serialized string (secure by default)
     *
     * @param string                   $payload        The serialized string
     * @param array<int, string> $allowedClasses Whitelist of allowed classes (empty = no objects)
     *
     * @return mixed
     *
     * @throws RuntimeException On security violation
     * @throws UnexpectedValueException On deserialization failure
     */
    public static function fromPhp(string $payload, array $allowedClasses = [])
    {
        $start = self::$debugMode ? microtime(true) : 0;

        try {
            self::validateInput($payload);
            self::validateSecurity($payload, empty($allowedClasses));
            $result = self::unserialize($payload, $allowedClasses);

            if (self::$debugMode) {
                self::debug('fromPhp', Format::PHP, microtime(true) - $start);
            }

            return $result;
        } catch (\Throwable $e) {
            if (self::$debugMode) {
                self::debug('fromPhp', Format::PHP, microtime(true) - $start, $e->getMessage());
            }
            throw $e;
        }
    }

    // ═══════════════════════════════════════════════════════════
    // Legacy Support (For migration from old XOOPS data)
    // ═══════════════════════════════════════════════════════════

    /**
     * Create legacy format (base64-encoded serialized data)
     *
     * @deprecated Use toJson() for new code
     *
     * @param mixed $data
     *
     * @return string
     *
     * @throws \InvalidArgumentException On unsupported types (resource, closure)
     */
    public static function toLegacy($data): string
    {
        return base64_encode(self::toPhp($data));
    }

    /**
     * Deserialize legacy format (handles plain, base64, and optional gzip)
     *
     * @param string                   $payload
     * @param array<int, string> $allowedClasses Whitelist of allowed classes
     *
     * @return mixed
     *
     * @throws RuntimeException On security or format violation
     * @throws UnexpectedValueException On deserialization failure
     */
    public static function fromLegacy(string $payload, array $allowedClasses = [])
    {
        self::validateInput($payload);
        self::validateSecurity($payload, empty($allowedClasses));

        // Try plain PHP serialize first
        $result = self::tryUnserialize($payload, $allowedClasses);
        if ($result !== null) {
            self::logLegacy($payload);
            return $result;
        }

        // Check if it looks like base64 before attempting decode
        if (!self::isLikelyBase64($payload)) {
            throw new RuntimeException('Invalid legacy format: not serialized or base64');
        }

        // Try base64-decoded
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            throw new RuntimeException('Invalid legacy format: base64 decode failed');
        }

        // Validate size after decoding
        self::validateSize($decoded);

        // Check for optional gzip compression — stream-decompress to enforce size cap
        if (self::isGzip($decoded)) {
            $decoded = self::safeGzipDecode($decoded);
        }

        self::logLegacy($payload);
        self::validateSecurity($decoded, empty($allowedClasses));

        return self::unserialize($decoded, $allowedClasses);
    }

    // ═══════════════════════════════════════════════════════════
    // Smart Methods (Format detection and conversion)
    // ═══════════════════════════════════════════════════════════

    /**
     * Deserialize with automatic format detection
     *
     * @param string                   $payload
     * @param array<int, string> $allowedClasses For PHP/legacy formats
     *
     * @return mixed
     *
     * @throws JsonException On JSON parse failure
     * @throws RuntimeException On security or format violation
     * @throws UnexpectedValueException On deserialization failure
     */
    public static function from(string $payload, array $allowedClasses = [])
    {
        $format = self::detect($payload);

        // Use detected format if confident
        if ($format === Format::JSON) {
            return self::fromJson($payload);
        } elseif ($format === Format::PHP) {
            return self::fromPhp($payload, $allowedClasses);
        } elseif ($format === Format::LEGACY) {
            return self::fromLegacy($payload, $allowedClasses);
        }

        // Format::AUTO - try sequence for migrations
        try {
            return self::fromJson($payload);
        } catch (JsonException $e) {
            // Not JSON, try other formats
        }

        if (self::looksLikeSerialized(ltrim($payload))) {
            return self::fromPhp($payload, $allowedClasses);
        }

        return self::fromLegacy($payload, $allowedClasses);
    }

    /**
     * Detect the serialization format without deserializing
     *
     * @param string $payload
     *
     * @return string One of Format::* constants
     */
    public static function detect(string $payload): string
    {
        if ($payload === '') {
            return Format::AUTO;
        }

        $trimmed = ltrim($payload);

        // Check JSON (use trimmed for consistency with the leading-char check)
        if (isset($trimmed[0]) && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            if (self::isValidJson($trimmed)) {
                return Format::JSON;
            }
        }

        // Check PHP serialized
        if (self::looksLikeSerialized($trimmed)) {
            return Format::PHP;
        }

        // Check base64-encoded serialized
        if (self::isLikelyBase64($payload)) {
            $decoded = base64_decode($payload, true);
            if (
                $decoded !== false
                && \strlen($decoded) <= self::MAX_SIZE
                && self::looksLikeSerialized($decoded)
            ) {
                return Format::LEGACY;
            }
        }

        return Format::AUTO;
    }

    // ═══════════════════════════════════════════════════════════
    // Convenience Methods (Type-safe helpers)
    // ═══════════════════════════════════════════════════════════

    /**
     * Deserialize expecting an array result
     *
     * @param string $payload
     * @param string $format
     *
     * @return array<mixed>
     *
     * @throws UnexpectedValueException When result is not an array
     * @throws JsonException On JSON parse failure
     * @throws \InvalidArgumentException On unsupported format
     */
    public static function toArray(string $payload, string $format = Format::AUTO): array
    {
        if ($format === Format::JSON) {
            $data = self::fromJson($payload);
        } elseif ($format === Format::PHP) {
            $data = self::fromPhp($payload);
        } elseif ($format === Format::LEGACY) {
            $data = self::fromLegacy($payload);
        } elseif ($format === Format::AUTO) {
            $data = self::from($payload);
        } else {
            throw new \InvalidArgumentException(
                sprintf('Unsupported format "%s"', $format)
            );
        }

        if (!is_array($data)) {
            throw new UnexpectedValueException(
                sprintf('Expected array, got %s', self::getDebugType($data))
            );
        }

        return $data;
    }

    /**
     * Deserialize expecting a specific class instance
     *
     * @param string       $payload
     * @param string       $className Fully-qualified class name
     * @param string       $format
     *
     * @return object
     *
     * @throws \InvalidArgumentException When $className does not exist
     * @throws RuntimeException On unsupported format
     * @throws UnexpectedValueException On type mismatch
     */
    public static function toObject(string $payload, string $className, string $format = Format::PHP): object
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException(
                sprintf('Class "%s" does not exist', $className)
            );
        }

        /** @var array<int, class-string> $allowed */
        $allowed = [$className];

        if ($format !== Format::PHP && $format !== Format::LEGACY) {
            throw new RuntimeException('Objects only supported in PHP/Legacy formats');
        }

        $data = ($format === Format::PHP)
            ? self::fromPhp($payload, $allowed)
            : self::fromLegacy($payload, $allowed);

        if (!$data instanceof $className) {
            throw new UnexpectedValueException(
                sprintf('Expected %s, got %s', $className, self::getDebugType($data))
            );
        }

        return $data;
    }

    /**
     * Safe deserialization with fallback
     *
     * @param string                   $payload
     * @param mixed                    $default
     * @param string                   $format
     * @param array<int, string> $allowedClasses
     *
     * @return mixed
     */
    public static function tryFrom(string $payload, $default = null, string $format = Format::AUTO, array $allowedClasses = [])
    {
        try {
            if ($format === Format::JSON) {
                return self::fromJson($payload);
            } elseif ($format === Format::PHP) {
                return self::fromPhp($payload, $allowedClasses);
            } elseif ($format === Format::LEGACY) {
                return self::fromLegacy($payload, $allowedClasses);
            } elseif ($format === Format::AUTO) {
                return self::from($payload, $allowedClasses);
            }

            return $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Try JSON-only deserialization (for mixed-field migrations)
     *
     * @param string $payload
     *
     * @return mixed|null Returns null on any failure
     */
    public static function jsonOnly(string $payload)
    {
        if ($payload === '' || !isset(ltrim($payload)[0])) {
            return null;
        }

        // Single json_decode with try-catch avoids redundant double-parsing
        // that would occur if isValidJson() were called first
        try {
            return json_decode($payload, true, self::JSON_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return null;
        }
    }

    /**
     * Deserialize expecting scalar or null
     *
     * @param string $payload
     * @param string $format
     *
     * @return string|int|float|bool|null
     *
     * @throws UnexpectedValueException When result is not scalar or null
     * @throws JsonException On JSON parse failure
     * @throws RuntimeException On security or format violation
     * @throws \InvalidArgumentException On unsupported format
     */
    public static function scalarsOnly(string $payload, string $format = Format::AUTO)
    {
        if ($format === Format::JSON) {
            $v = self::fromJson($payload);
        } elseif ($format === Format::PHP) {
            $v = self::fromPhp($payload);
        } elseif ($format === Format::LEGACY) {
            $v = self::fromLegacy($payload);
        } elseif ($format === Format::AUTO) {
            $v = self::from($payload);
        } else {
            throw new \InvalidArgumentException(
                sprintf('Unsupported format "%s"', $format)
            );
        }

        if (!is_scalar($v) && $v !== null) {
            throw new UnexpectedValueException('Expected scalar or null');
        }

        return $v;
    }

    // ═══════════════════════════════════════════════════════════
    // Configuration
    // ═══════════════════════════════════════════════════════════

    /**
     * Set a logger to track legacy format usage (for migration tracking)
     *
     * @param callable|null $logger fn(string $file, int $line, string $preview): void
     *
     * @return void
     */
    public static function setLegacyLogger(?callable $logger): void
    {
        self::$legacyLogger = $logger ? \Closure::fromCallable($logger) : null;
    }

    // ═══════════════════════════════════════════════════════════
    // Private Helpers
    // ═══════════════════════════════════════════════════════════

    /**
     * @param string $payload
     *
     * @return void
     *
     * @throws UnexpectedValueException
     * @throws RuntimeException
     */
    private static function validateInput(string $payload): void
    {
        if ($payload === '') {
            throw new UnexpectedValueException('Cannot deserialize empty string');
        }

        if (\strlen($payload) > self::MAX_SIZE) {
            throw new RuntimeException(
                sprintf('Payload exceeds maximum size of %d bytes', self::MAX_SIZE)
            );
        }
    }

    /**
     * @param string $payload
     *
     * @return void
     *
     * @throws RuntimeException
     */
    private static function validateSize(string $payload): void
    {
        if (\strlen($payload) > self::MAX_SIZE) {
            throw new RuntimeException(
                sprintf('Decoded payload exceeds %d bytes', self::MAX_SIZE)
            );
        }
    }

    /**
     * Decompress gzip data with streaming size enforcement.
     *
     * Uses inflate_init()/inflate_add() to decompress in chunks, aborting
     * as soon as output exceeds MAX_SIZE. This prevents a small compressed
     * payload from consuming unbounded memory during decompression.
     *
     * @param string $data gzip-compressed data
     *
     * @return string decompressed data
     *
     * @throws DecompressionException on decompression failure or size exceeded
     */
    private static function safeGzipDecode(string $data): string
    {
        $context = inflate_init(ZLIB_ENCODING_GZIP);
        if ($context === false) {
            throw new DecompressionException('Failed to initialize gzip decompression');
        }

        $output = '';
        $offset = 0;
        $chunkSize = 8192;
        $maxSize = self::MAX_SIZE;

        while ($offset < \strlen($data)) {
            $chunk = substr($data, $offset, $chunkSize);
            $offset += \strlen($chunk);

            $inflated = inflate_add($context, $chunk);
            if ($inflated === false) {
                throw new DecompressionException('Gzip decompression failed');
            }
            $output .= $inflated;

            if (\strlen($output) > $maxSize) {
                throw new DecompressionException(
                    sprintf('Decompressed payload exceeds %d bytes', $maxSize)
                );
            }
        }

        // Flush remaining and verify stream completed
        $inflated = inflate_add($context, '', ZLIB_FINISH);
        if ($inflated === false) {
            throw new DecompressionException('Gzip decompression failed during final flush');
        }
        if (inflate_get_status($context) !== ZLIB_STREAM_END) {
            throw new DecompressionException('Gzip stream is incomplete or truncated');
        }
        if ($inflated !== '') {
            $output .= $inflated;
            if (\strlen($output) > $maxSize) {
                throw new DecompressionException(
                    sprintf('Decompressed payload exceeds %d bytes', $maxSize)
                );
            }
        }

        return $output;
    }

    /**
     * Secondary defense against object injection in scalar/array payloads.
     *
     * PHP serialized objects use NUL bytes to mark private/protected
     * property visibility boundaries. Legitimate scalar/array payloads
     * should never contain them. This complements allowed_classes=false
     * set in unserialize().
     *
     * @param string $payload
     * @param bool   $noObjects
     *
     * @return void
     *
     * @throws RuntimeException
     */
    private static function validateSecurity(string $payload, bool $noObjects): void
    {
        if ($noObjects && strpos($payload, "\0") !== false) {
            throw new RuntimeException('NUL bytes detected in scalar/array payload');
        }
    }

    /**
     * Core unserialize with error handling
     *
     * @param string                   $payload
     * @param array<int, string> $allowedClasses
     *
     * @return mixed
     *
     * @throws UnexpectedValueException
     */
    private static function unserialize(string $payload, array $allowedClasses)
    {
        /** @var array{allowed_classes: array<int, class-string>|false} $options */
        $options = ['allowed_classes' => $allowedClasses !== [] ? array_values($allowedClasses) : false];

        set_error_handler(
            static function (int $severity, string $message): bool {
                if ($severity !== E_WARNING && $severity !== E_NOTICE) {
                    return false;
                }
                // Only suppress warnings/notices that originate from unserialize()
                return strpos($message, 'unserialize():') === 0;
            },
            E_WARNING | E_NOTICE
        );

        try {
            $result = \unserialize($payload, $options);
        } finally {
            restore_error_handler();
        }

        if ($result === false && $payload !== 'b:0;') {
            throw new UnexpectedValueException('Failed to unserialize payload');
        }

        return $result;
    }

    /**
     * Attempt unserialization, returning null on failure instead of throwing.
     *
     * @param string                   $payload
     * @param array<int, string> $allowedClasses
     *
     * @return mixed|null
     */
    private static function tryUnserialize(string $payload, array $allowedClasses)
    {
        try {
            return self::unserialize($payload, $allowedClasses);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Check if a string looks like PHP serialized data
     *
     * @param string $str
     *
     * @return bool
     */
    private static function looksLikeSerialized(string $str): bool
    {
        if (\strlen($str) < 2) {
            return false;
        }

        switch ($str[0]) {
            case 'N':
                return \strpos($str, 'N;') === 0;

            case 'i':
            case 'b':
            case 'r':
            case 'R':
                return (bool) preg_match('/^[ibrR]:-?\d+;/', $str);

            case 'd':
                return (bool) preg_match('/^d:-?(?:\d+\.?\d*|\d*\.?\d+)(?:[eE][+-]?\d+)?;/', $str);

            case 's':
                return (bool) preg_match('/^s:\d+:"/', $str);

            case 'a':
                return (bool) preg_match('/^a:\d+:\{/', $str);

            case 'O':
            case 'C':
                return (bool) preg_match('/^[OC]:\d+:"/', $str);

            default:
                return false;
        }
    }

    /**
     * Check if data starts with gzip magic bytes
     *
     * @param string $bin
     *
     * @return bool
     */
    private static function isGzip(string $bin): bool
    {
        return isset($bin[0], $bin[1]) && $bin[0] === "\x1f" && $bin[1] === "\x8b";
    }

    /**
     * Heuristic check for base64-encoded data
     *
     * @param string $s
     *
     * @return bool
     */
    private static function isLikelyBase64(string $s): bool
    {
        $len = \strlen($s);

        // Require reasonable minimum length and proper block alignment
        if ($len < 16 || ($len % 4) !== 0) {
            return false;
        }

        return preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $s) === 1;
    }

    /**
     * Validate JSON string
     *
     * @param string $s
     *
     * @return bool
     */
    private static function isValidJson(string $s): bool
    {
        // Use json_validate if available (PHP 8.3+)
        if (function_exists('json_validate')) {
            return json_validate($s, self::JSON_DEPTH) === true;
        }

        // Fallback for older PHP versions
        try {
            json_decode($s, true, self::JSON_DEPTH, JSON_THROW_ON_ERROR);
            return true;
        } catch (JsonException $e) {
            return false;
        }
    }

    /**
     * Log legacy format detection
     *
     * @param string $payload
     *
     * @return void
     */
    private static function logLegacy(string $payload): void
    {
        if (self::$legacyLogger === null) {
            return;
        }

        $preview = \substr($payload, 0, 50) . (\strlen($payload) > 50 ? '...' : '');
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $caller = ['file' => 'unknown', 'line' => 0];
        foreach ($trace as $frame) {
            if (!isset($frame['file'], $frame['line'])) {
                continue;
            }
            if (isset($frame['class']) && $frame['class'] === self::class) {
                continue;
            }
            $caller = $frame;
            break;
        }

        (self::$legacyLogger)(
            $caller['file'],
            $caller['line'],
            $preview
        );
    }

    /**
     * PHP 7.4 compatible version of get_debug_type()
     *
     * @param mixed $value
     *
     * @return string
     */
    private static function getDebugType($value): string
    {
        if (function_exists('get_debug_type')) {
            return get_debug_type($value);
        }

        // PHP 7.4 fallback
        return is_object($value) ? get_class($value) : gettype($value);
    }
}
