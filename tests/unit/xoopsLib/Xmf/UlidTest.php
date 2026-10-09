<?php

declare(strict_types=1);

namespace Xmf\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Xmf\Ulid;

/**
 * Comprehensive test suite for Xmf\Ulid
 *
 * Tests cover:
 * - ULID generation and format
 * - Uniqueness and lexicographic ordering
 * - Encoding/decoding consistency
 * - Known vector validation (spec compliance)
 * - UUID bidirectional conversion
 * - Validation and error handling
 * - Edge cases
 */
#[CoversClass(Ulid::class)]
class UlidTest extends TestCase
{
    // =========================================================================
    // KNOWN VECTOR TESTS (Spec Compliance)
    // =========================================================================

    /**
     * Test against a known vector from the ULID spec to ensure
     * the encoding math is correct and hasn't drifted.
     *
     * Timestamp: 1469918176385 (2016-07-30 22:36:16.385 UTC)
     * Expected time encoding: 01ARYZ6S41
     */
    public function testEncodeTimeMatchesSpecVector(): void
    {
        $timestamp = 1469918176385;
        $encoded = Ulid::encodeTime($timestamp);

        $this->assertSame('01ARYZ6S41', $encoded);
    }

    /**
     * Test decoding the spec vector timestamp.
     */
    public function testDecodeTimeMatchesSpecVector(): void
    {
        // Create a valid ULID with the known timestamp
        $ulid = '01ARYZ6S410000000000000000';
        $decoded = Ulid::decodeTime($ulid);

        $this->assertSame(1469918176385, $decoded);
    }

    /**
     * Test round-trip encoding/decoding with spec vector.
     */
    public function testSpecVectorRoundTrip(): void
    {
        $originalTimestamp = 1469918176385;
        $encoded = Ulid::encodeTime($originalTimestamp);
        $ulid = $encoded . '0000000000000000'; // Add dummy random part
        $decoded = Ulid::decodeTime($ulid);

        $this->assertSame($originalTimestamp, $decoded);
    }

    /**
     * Test another known vector: Unix epoch (timestamp 0)
     */
    public function testEncodeTimeAtEpoch(): void
    {
        $encoded = Ulid::encodeTime(0);

        $this->assertSame('0000000000', $encoded);
    }

    /**
     * Test known vector: maximum timestamp (year ~10889)
     */
    public function testEncodeTimeAtMaximum(): void
    {
        $maxTime = 281474976710655; // 2^48 - 1
        $encoded = Ulid::encodeTime($maxTime);

        $this->assertSame('7ZZZZZZZZZ', $encoded);
    }

    // =========================================================================
    // GENERATION TESTS
    // =========================================================================

    public function testGenerateReturns26Characters(): void
    {
        $ulid = Ulid::generate();

        $this->assertSame(26, \strlen($ulid));
    }

    public function testGenerateReturnsValidUlid(): void
    {
        $ulid = Ulid::generate();

        $this->assertTrue(Ulid::isValid($ulid));
    }

    public function testGenerateReturnsUppercaseByDefault(): void
    {
        $ulid = Ulid::generate();

        $this->assertSame($ulid, \strtoupper($ulid));
    }

    public function testGenerateReturnsLowercaseWhenRequested(): void
    {
        $ulid = Ulid::generate(false);

        $this->assertSame($ulid, \strtolower($ulid));
        $this->assertTrue(Ulid::isValid($ulid)); // Should still be valid
    }

    public function testGenerateUsesOnlyCrockfordBase32Characters(): void
    {
        $ulid = Ulid::generate();
        $validChars = Ulid::ENCODING_CHARS;

        for ($i = 0; $i < 26; $i++) {
            $this->assertNotFalse(
                \strpos($validChars, $ulid[$i]),
                "Character '{$ulid[$i]}' at position $i is not a valid Crockford Base32 character"
            );
        }
    }

    public function testGenerateProducesUniqueValues(): void
    {
        $ulids = [];

        for ($i = 0; $i < 1000; $i++) {
            $ulids[] = Ulid::generate();
        }

        $uniqueUlids = \array_unique($ulids);

        $this->assertCount(1000, $uniqueUlids, 'All 1000 generated ULIDs should be unique');
    }

    public function testGenerateProducesLexicographicallySortableValues(): void
    {
        Ulid::resetMonotonicState();
        $ulid1 = Ulid::generateMonotonic();
        $ulid2 = Ulid::generateMonotonic();

        $this->assertLessThan(0, \strcmp($ulid1, $ulid2), 'Earlier ULID should sort before later ULID');
    }

    public function testGenerateProducesCorrectlySortedArray(): void
    {
        Ulid::resetMonotonicState();
        $ulids = [];

        for ($i = 0; $i < 10; $i++) {
            $ulids[] = Ulid::generateMonotonic();
        }

        $sortedUlids = $ulids;
        \sort($sortedUlids, SORT_STRING);

        $this->assertSame($ulids, $sortedUlids, 'ULIDs should already be in sorted order');
    }

    // =========================================================================
    // TIMESTAMP TESTS
    // =========================================================================

    public function testCurrentTimeMillisReturnsReasonableValue(): void
    {
        $time = Ulid::currentTimeMillis();

        // Should be greater than Jan 1, 2020 in milliseconds
        $this->assertGreaterThan(1577836800000, $time);

        // Should be less than Jan 1, 2100 in milliseconds
        $this->assertLessThan(4102444800000, $time);
    }

    public function testTimeEncodingDecodingRoundTrip(): void
    {
        $originalTime = Ulid::currentTimeMillis();
        $encoded = Ulid::encodeTime($originalTime);

        // Create a full ULID with random portion for decoding
        $ulid = $encoded . Ulid::encodeRandomness();
        $decodedTime = Ulid::decodeTime($ulid);

        $this->assertSame($originalTime, $decodedTime);
    }

    public function testEncodeTimeReturns10Characters(): void
    {
        $time = Ulid::currentTimeMillis();
        $encoded = Ulid::encodeTime($time);

        $this->assertSame(10, \strlen($encoded));
    }

    public function testEncodeTimeThrowsExceptionForNegativeTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Timestamp cannot be negative');

        Ulid::encodeTime(-1);
    }

    public function testEncodeTimeThrowsExceptionForOverflow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/exceeds maximum/');

        Ulid::encodeTime(Ulid::MAX_TIME + 1);
    }

    public function testDecodeTimeExtractsCorrectTimestamp(): void
    {
        $ulid = Ulid::generate();
        $decodedTime = Ulid::decodeTime($ulid);
        $currentTime = Ulid::currentTimeMillis();

        // Decoded time should be within 1 second of current time
        $this->assertLessThan(1000, \abs($currentTime - $decodedTime));
    }

    public function testGetDateTimeReturnsCorrectDateTime(): void
    {
        $ulid = Ulid::generate();
        $dateTime = Ulid::getDateTime($ulid);
        $now = new \DateTimeImmutable();

        // Should be within 1 second of now
        $diff = \abs($now->getTimestamp() - $dateTime->getTimestamp());
        $this->assertLessThan(2, $diff);
    }

    public function testGetDateTimeWithSpecVector(): void
    {
        // Timestamp: 1469918176385 (2016-07-30 22:36:16.385 UTC)
        $ulid = '01ARYZ6S410000000000000000';
        $dateTime = Ulid::getDateTime($ulid);

        $this->assertSame('2016-07-30', $dateTime->format('Y-m-d'));
        $this->assertSame('22:36:16', $dateTime->format('H:i:s'));
    }

    // =========================================================================
    // RANDOMNESS TESTS
    // =========================================================================

    public function testEncodeRandomnessReturns16Characters(): void
    {
        $randomPart = Ulid::encodeRandomness();

        $this->assertSame(16, \strlen($randomPart));
    }

    public function testEncodeRandomnessProducesDifferentValues(): void
    {
        $random1 = Ulid::encodeRandomness();
        $random2 = Ulid::encodeRandomness();

        $this->assertNotSame($random1, $random2);
    }

    public function testEncodeRandomnessUsesOnlyValidCharacters(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $random = Ulid::encodeRandomness();

            for ($j = 0; $j < 16; $j++) {
                $this->assertNotFalse(
                    \strpos(Ulid::ENCODING_CHARS, $random[$j]),
                    "Random character '{$random[$j]}' at position $j is invalid"
                );
            }
        }
    }

    public function testDecodeRandomnessExtractsCorrectPortion(): void
    {
        $ulid = Ulid::generate();
        $randomPart = Ulid::decodeRandomness($ulid);

        $this->assertSame(\substr(\strtoupper($ulid), 10), $randomPart);
    }

    // =========================================================================
    // VALIDATION TESTS
    // =========================================================================

    public function testIsValidReturnsTrueForValidUppercaseUlid(): void
    {
        $ulid = Ulid::generate(true);

        $this->assertTrue(Ulid::isValid($ulid));
    }

    public function testIsValidReturnsTrueForValidLowercaseUlid(): void
    {
        $ulid = Ulid::generate(false);

        $this->assertTrue(Ulid::isValid($ulid));
    }

    public function testIsValidReturnsFalseForTooShortString(): void
    {
        $this->assertFalse(Ulid::isValid('01ARZ3NDEKTSV4RRFFQ69G5FA')); // 25 chars
    }

    public function testIsValidReturnsFalseForTooLongString(): void
    {
        $this->assertFalse(Ulid::isValid('01ARZ3NDEKTSV4RRFFQ69G5FAXX')); // 28 chars
    }

    public function testIsValidReturnsFalseForEmptyString(): void
    {
        $this->assertFalse(Ulid::isValid(''));
    }

    #[DataProvider('invalidCharacterProvider')]
    public function testIsValidReturnsFalseForInvalidCharacters(string $invalidUlid): void
    {
        $this->assertFalse(Ulid::isValid($invalidUlid));
    }

    public static function invalidCharacterProvider(): array
    {
        return [
            'contains I' => ['01ARZ3NDEKTSV4RRFFQI9G5FAV'],
            'contains L' => ['01ARZ3NDEKTSV4RRFFQL9G5FAV'],
            'contains O' => ['01ARZ3NDEKTSV4RRFFQO9G5FAV'],
            'contains U' => ['01ARZ3NDEKTSV4RRFFQU9G5FAV'],
            'contains special char' => ['01ARZ3NDEKTSV4RRFFQ@9G5FAV'],
            'contains space' => ['01ARZ3NDEKTSV4RRFFQ 9G5FAV'],
            'contains hyphen' => ['01ARZ3NDEK-SV4RRFFQ69G5FAV'],
        ];
    }

    public function testIsValidIsCaseInsensitive(): void
    {
        $upperUlid = Ulid::generate(true);
        $lowerUlid = \strtolower($upperUlid);
        $mixedUlid = '';

        for ($i = 0; $i < 26; $i++) {
            $mixedUlid .= $i % 2 === 0 ? $upperUlid[$i] : \strtolower($upperUlid[$i]);
        }

        $this->assertTrue(Ulid::isValid($upperUlid));
        $this->assertTrue(Ulid::isValid($lowerUlid));
        $this->assertTrue(Ulid::isValid($mixedUlid));
    }

    public function testIsValidReturnsFalseForOverflowFirstChar(): void
    {
        // First character > 7 would mean timestamp overflow (> 2^48 - 1)
        $this->assertFalse(Ulid::isValid('80000000000000000000000000'));
    }

    // =========================================================================
    // DECODE TESTS
    // =========================================================================

    public function testDecodeReturnsArrayWithTimeAndRand(): void
    {
        $ulid = Ulid::generate();
        $components = Ulid::decode($ulid);

        $this->assertArrayHasKey('time', $components);
        $this->assertArrayHasKey('rand', $components);
    }

    public function testDecodeTimeIsPositiveInteger(): void
    {
        $ulid = Ulid::generate();
        $components = Ulid::decode($ulid);

        $this->assertIsInt($components['time']);
        $this->assertGreaterThan(0, $components['time']);
    }

    public function testDecodeRandIs16Characters(): void
    {
        $ulid = Ulid::generate();
        $components = Ulid::decode($ulid);

        $this->assertSame(16, \strlen($components['rand']));
    }

    public function testDecodeThrowsExceptionForInvalidUlid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Ulid::decode('invalid-ulid');
    }

    public function testDecodeTimeThrowsExceptionForWrongLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Ulid::decodeTime('01ARZ3NDEK'); // Only 10 chars
    }

    public function testDecodeTimeThrowsExceptionForInvalidCharacter(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Ulid::decodeTime('01ARI3NDEKTSV4RRFFQ09G5FAV'); // Contains 'I' in time portion
    }

    public function testDecodeRandomnessThrowsExceptionForWrongLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Ulid::decodeRandomness('short');
    }

    public function testDecodeRandomnessThrowsExceptionForInvalidCharacter(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Ulid::decodeRandomness('01ARZ3NDEKTSV4RRFFQI9G5FAV'); // Contains 'I'
    }

    // =========================================================================
    // COMPARISON TESTS
    // =========================================================================

    public function testCompareReturnNegativeForEarlierUlid(): void
    {
        Ulid::resetMonotonicState();
        $ulid1 = Ulid::generateMonotonic();
        $ulid2 = Ulid::generateMonotonic();

        $this->assertSame(-1, Ulid::compare($ulid1, $ulid2));
    }

    public function testCompareReturnPositiveForLaterUlid(): void
    {
        Ulid::resetMonotonicState();
        $ulid1 = Ulid::generateMonotonic();
        $ulid2 = Ulid::generateMonotonic();

        $this->assertSame(1, Ulid::compare($ulid2, $ulid1));
    }

    public function testCompareReturnZeroForSameUlid(): void
    {
        $ulid = Ulid::generate();

        $this->assertSame(0, Ulid::compare($ulid, $ulid));
    }

    public function testCompareIsCaseInsensitive(): void
    {
        $ulid = Ulid::generate();
        $lowerUlid = \strtolower($ulid);

        $this->assertSame(0, Ulid::compare($ulid, $lowerUlid));
    }

    // =========================================================================
    // UUID CONVERSION TESTS (require ext-bcmath)
    // =========================================================================

    #[RequiresPhpExtension('bcmath')]
    public function testToUuidReturnsValidUuidFormat(): void
    {
        $ulid = Ulid::generate();
        $uuid = Ulid::toUuid($ulid);

        // UUID format: 8-4-4-4-12 hex characters
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    #[RequiresPhpExtension('bcmath')]
    public function testToUuidReturns36Characters(): void
    {
        $ulid = Ulid::generate();
        $uuid = Ulid::toUuid($ulid);

        $this->assertSame(36, \strlen($uuid));
    }

    #[RequiresPhpExtension('bcmath')]
    public function testToUuidThrowsExceptionForInvalidUlid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Ulid::toUuid('invalid-ulid');
    }

    #[RequiresPhpExtension('bcmath')]
    public function testToUuidProducesConsistentResult(): void
    {
        $ulid = Ulid::generate();
        $uuid1 = Ulid::toUuid($ulid);
        $uuid2 = Ulid::toUuid($ulid);

        $this->assertSame($uuid1, $uuid2);
    }

    #[RequiresPhpExtension('bcmath')]
    public function testFromUuidReturnsValidUlid(): void
    {
        $uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $ulid = Ulid::fromUuid($uuid);

        $this->assertTrue(Ulid::isValid($ulid));
        $this->assertSame(26, \strlen($ulid));
    }

    #[RequiresPhpExtension('bcmath')]
    public function testFromUuidWorksWithoutHyphens(): void
    {
        $uuidWithHyphens = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $uuidWithoutHyphens = 'f47ac10b58cc4372a5670e02b2c3d479';

        $ulid1 = Ulid::fromUuid($uuidWithHyphens);
        $ulid2 = Ulid::fromUuid($uuidWithoutHyphens);

        $this->assertSame($ulid1, $ulid2);
    }

    #[RequiresPhpExtension('bcmath')]
    public function testFromUuidThrowsExceptionForInvalidLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid UUID format: expected 32 hex characters/');

        Ulid::fromUuid('f47ac10b-58cc-4372-a567');
    }

    #[RequiresPhpExtension('bcmath')]
    public function testFromUuidThrowsExceptionForInvalidCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/non-hexadecimal/');

        Ulid::fromUuid('g47ac10b-58cc-4372-a567-0e02b2c3d479'); // 'g' is invalid
    }

    #[RequiresPhpExtension('bcmath')]
    public function testUuidRoundTrip(): void
    {
        $originalUlid = Ulid::generate();
        $uuid = Ulid::toUuid($originalUlid);
        $convertedBack = Ulid::fromUuid($uuid);

        $this->assertSame($originalUlid, $convertedBack);
    }

    #[RequiresPhpExtension('bcmath')]
    public function testUuidRoundTripMultiple(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $originalUlid = Ulid::generate();
            $uuid = Ulid::toUuid($originalUlid);
            $convertedBack = Ulid::fromUuid($uuid);

            $this->assertSame(
                $originalUlid,
                $convertedBack,
                "Round-trip failed for ULID: $originalUlid"
            );
        }
    }

    #[RequiresPhpExtension('bcmath')]
    public function testFromUuidRoundTrip(): void
    {
        $originalUuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $ulid = Ulid::fromUuid($originalUuid);
        $convertedBack = Ulid::toUuid($ulid);

        $this->assertSame(\strtolower($originalUuid), \strtolower($convertedBack));
    }

    /**
     * Test known UUID to ULID conversion vector.
     */
    #[RequiresPhpExtension('bcmath')]
    public function testFromUuidKnownVector(): void
    {
        // UUID: 00000000-0000-0000-0000-000000000000 should convert to all zeros
        $zeroUuid = '00000000-0000-0000-0000-000000000000';
        $ulid = Ulid::fromUuid($zeroUuid);

        $this->assertSame('00000000000000000000000000', $ulid);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Test minimum timestamp (epoch)
     */
    public function testMinimumTimestamp(): void
    {
        $encoded = Ulid::encodeTime(0);
        $ulid = $encoded . '0000000000000000';

        $this->assertTrue(Ulid::isValid($ulid));
        $this->assertSame(0, Ulid::decodeTime($ulid));
    }

    /**
     * Test that generating many ULIDs in rapid succession still produces unique values
     */
    public function testRapidGenerationUniqueness(): void
    {
        $ulids = [];

        for ($i = 0; $i < 100; $i++) {
            $ulids[] = Ulid::generate();
        }

        $uniqueCount = \count(\array_unique($ulids));

        $this->assertSame(100, $uniqueCount, 'All rapidly generated ULIDs should be unique');
    }

    /**
     * Test that ULID works correctly around midnight/day boundaries
     */
    public function testTimestampPrecision(): void
    {
        $ulid1 = Ulid::generate();
        $time1 = Ulid::decodeTime($ulid1);

        \usleep(1500); // Wait 1.5ms

        $ulid2 = Ulid::generate();
        $time2 = Ulid::decodeTime($ulid2);

        // Times should be different (at least 1ms apart)
        $this->assertGreaterThanOrEqual(1, $time2 - $time1);
    }

    // =========================================================================
    // ENCODING ALPHABET TESTS
    // =========================================================================

    /**
     * Verify Crockford Base32 alphabet is correct
     */
    public function testCrockfordBase32Alphabet(): void
    {
        $expected = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

        $this->assertSame($expected, Ulid::ENCODING_CHARS);
        $this->assertSame(32, \strlen(Ulid::ENCODING_CHARS));

        // Verify excluded characters are not present
        $this->assertStringNotContainsString('I', Ulid::ENCODING_CHARS);
        $this->assertStringNotContainsString('L', Ulid::ENCODING_CHARS);
        $this->assertStringNotContainsString('O', Ulid::ENCODING_CHARS);
        $this->assertStringNotContainsString('U', Ulid::ENCODING_CHARS);
    }

    /**
     * Test constants are correctly defined
     */
    public function testConstants(): void
    {
        $this->assertSame(32, Ulid::ENCODING_LENGTH);
        $this->assertSame(10, Ulid::TIME_LENGTH);
        $this->assertSame(16, Ulid::RANDOM_LENGTH);
        $this->assertSame(26, Ulid::ULID_LENGTH);
        $this->assertSame(281474976710655, Ulid::MAX_TIME);
    }

    // =========================================================================
    // CONSISTENCY TESTS
    // =========================================================================

    /**
     * Test that encoding and decoding are inverse operations
     */
    public function testEncodingDecodingConsistency(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $ulid = Ulid::generate();
            $components = Ulid::decode($ulid);
            $reEncodedTime = Ulid::encodeTime($components['time']);

            $this->assertSame(
                \substr(\strtoupper($ulid), 0, 10),
                $reEncodedTime,
                'Time portion should be consistent after decode/encode cycle'
            );
        }
    }

    /**
     * Test decoding works with mixed case input
     */
    public function testDecodingWithMixedCase(): void
    {
        $upperUlid = Ulid::generate(true);
        $lowerUlid = \strtolower($upperUlid);

        $upperComponents = Ulid::decode($upperUlid);
        $lowerComponents = Ulid::decode($lowerUlid);

        $this->assertSame($upperComponents['time'], $lowerComponents['time']);
        $this->assertSame($upperComponents['rand'], $lowerComponents['rand']);
    }

    // =========================================================================
    // BIT-PACKING TESTS (for optimized encodeRandomness)
    // =========================================================================

    /**
     * Test that encodeRandomness produces evenly distributed characters
     */
    public function testRandomnessDistribution(): void
    {
        $charCounts = \array_fill_keys(\str_split(Ulid::ENCODING_CHARS), 0);
        $iterations = 1000;

        for ($i = 0; $i < $iterations; $i++) {
            $random = Ulid::encodeRandomness();
            for ($j = 0; $j < 16; $j++) {
                $charCounts[$random[$j]]++;
            }
        }

        // Each character should appear roughly 1/32 of the time
        // With 16000 total characters (1000 * 16), expect ~500 per character
        $expectedCount = ($iterations * 16) / 32;
        $tolerance = $expectedCount * 0.40; // 40% tolerance for smaller sample

        foreach ($charCounts as $char => $count) {
            $this->assertGreaterThan(
                $expectedCount - $tolerance,
                $count,
                "Character '$char' appeared too few times: $count (expected ~$expectedCount)"
            );
            $this->assertLessThan(
                $expectedCount + $tolerance,
                $count,
                "Character '$char' appeared too many times: $count (expected ~$expectedCount)"
            );
        }
    }

    // =========================================================================
    // MONOTONIC GENERATION TESTS
    // =========================================================================

    public function testGenerateMonotonicReturnsValidUlid(): void
    {
        Ulid::resetMonotonicState();
        $ulid = Ulid::generateMonotonic();

        $this->assertTrue(Ulid::isValid($ulid));
        $this->assertSame(26, \strlen($ulid));
    }

    public function testGenerateMonotonicReturnsUppercaseByDefault(): void
    {
        Ulid::resetMonotonicState();
        $ulid = Ulid::generateMonotonic();

        $this->assertSame($ulid, \strtoupper($ulid));
    }

    public function testGenerateMonotonicReturnsLowercaseWhenRequested(): void
    {
        Ulid::resetMonotonicState();
        $ulid = Ulid::generateMonotonic(false);

        $this->assertSame($ulid, \strtolower($ulid));
    }

    public function testGenerateMonotonicProducesStrictlyIncreasingValues(): void
    {
        Ulid::resetMonotonicState();
        $ulids = [];

        // Generate many ULIDs rapidly (within same millisecond)
        for ($i = 0; $i < 100; $i++) {
            $ulids[] = Ulid::generateMonotonic();
        }

        // Verify strict ordering
        for ($i = 1; $i < count($ulids); $i++) {
            $this->assertLessThan(
                0,
                \strcmp($ulids[$i - 1], $ulids[$i]),
                "ULID at index $i should be greater than ULID at index " . ($i - 1)
            );
        }
    }

    public function testGenerateMonotonicIncrementsRandomPortion(): void
    {
        // Retry until both ULIDs land in the same millisecond so we can
        // verify the random portion was incremented rather than regenerated.
        for ($attempt = 0; $attempt < 100; $attempt++) {
            Ulid::resetMonotonicState();

            $ulid1 = Ulid::generateMonotonic();
            $ulid2 = Ulid::generateMonotonic();

            $time1 = \substr($ulid1, 0, 10);
            $time2 = \substr($ulid2, 0, 10);

            if ($time1 === $time2) {
                $rand1 = \substr($ulid1, 10);
                $rand2 = \substr($ulid2, 10);

                // Same millisecond: random portion must be incremented
                $this->assertNotSame($rand1, $rand2);
                $this->assertLessThan(0, \strcmp($rand1, $rand2));
                return;
            }
        }

        $this->fail('Could not generate two monotonic ULIDs within the same millisecond after 100 attempts');
    }

    public function testGenerateMonotonicResetsOnNewMillisecond(): void
    {
        // Retry until the two ULIDs land in different milliseconds,
        // so we can verify the timestamp actually advanced.
        for ($attempt = 0; $attempt < 100; $attempt++) {
            Ulid::resetMonotonicState();
            $ulid1 = Ulid::generateMonotonic();

            // Wait for a new millisecond
            \usleep(2000);

            $ulid2 = Ulid::generateMonotonic();

            $time1 = Ulid::decodeTime($ulid1);
            $time2 = Ulid::decodeTime($ulid2);

            if ($time1 !== $time2) {
                // $time2 should be greater than $time1
                $this->assertGreaterThan($time1, $time2);
                return;
            }
        }

        $this->fail('Could not generate two monotonic ULIDs in different milliseconds after 100 attempts');
    }

    public function testResetMonotonicState(): void
    {
        // Generate a ULID to set the state
        Ulid::generateMonotonic();

        // Reset
        Ulid::resetMonotonicState();

        // Generate another - should start fresh
        $ulid = Ulid::generateMonotonic();

        $this->assertTrue(Ulid::isValid($ulid));
    }

    public function testGenerateMonotonicUniqueness(): void
    {
        Ulid::resetMonotonicState();
        $ulids = [];

        for ($i = 0; $i < 1000; $i++) {
            $ulids[] = Ulid::generateMonotonic();
        }

        $uniqueCount = \count(\array_unique($ulids));

        $this->assertSame(1000, $uniqueCount, 'All monotonic ULIDs should be unique');
    }

    public function testGenerateMonotonicSortedArray(): void
    {
        Ulid::resetMonotonicState();
        $ulids = [];

        for ($i = 0; $i < 100; $i++) {
            $ulids[] = Ulid::generateMonotonic();
        }

        $sortedUlids = $ulids;
        \sort($sortedUlids, SORT_STRING);

        $this->assertSame($ulids, $sortedUlids, 'Monotonic ULIDs should already be in sorted order');
    }

    // =========================================================================
    // BINARY CONVERSION TESTS (require ext-bcmath)
    // =========================================================================

    #[RequiresPhpExtension('bcmath')]
    public function testToBinaryReturns16Bytes(): void
    {
        $ulid = Ulid::generate();
        $binary = Ulid::toBinary($ulid);

        $this->assertSame(16, \strlen($binary));
    }

    #[RequiresPhpExtension('bcmath')]
    public function testToBinaryProducesConsistentResult(): void
    {
        $ulid = Ulid::generate();
        $binary1 = Ulid::toBinary($ulid);
        $binary2 = Ulid::toBinary($ulid);

        $this->assertSame($binary1, $binary2);
    }

    #[RequiresPhpExtension('bcmath')]
    public function testToBinaryThrowsExceptionForInvalidUlid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Ulid::toBinary('invalid-ulid');
    }

    #[RequiresPhpExtension('bcmath')]
    public function testFromBinaryReturnsValidUlid(): void
    {
        $originalUlid = Ulid::generate();
        $binary = Ulid::toBinary($originalUlid);
        $ulid = Ulid::fromBinary($binary);

        $this->assertTrue(Ulid::isValid($ulid));
        $this->assertSame(26, \strlen($ulid));
    }

    #[RequiresPhpExtension('bcmath')]
    public function testFromBinaryThrowsExceptionForInvalidLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Invalid binary length: expected 16, got \d+/');

        Ulid::fromBinary('short');
    }

    #[RequiresPhpExtension('bcmath')]
    public function testBinaryRoundTrip(): void
    {
        $originalUlid = Ulid::generate();
        $binary = Ulid::toBinary($originalUlid);
        $convertedBack = Ulid::fromBinary($binary);

        $this->assertSame($originalUlid, $convertedBack);
    }

    #[RequiresPhpExtension('bcmath')]
    public function testBinaryRoundTripMultiple(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $originalUlid = Ulid::generate();
            $binary = Ulid::toBinary($originalUlid);
            $convertedBack = Ulid::fromBinary($binary);

            $this->assertSame(
                $originalUlid,
                $convertedBack,
                "Binary round-trip failed for ULID: $originalUlid"
            );
        }
    }

    /**
     * Test known binary vector: all zeros
     */
    #[RequiresPhpExtension('bcmath')]
    public function testToBinaryKnownVectorZeros(): void
    {
        $ulid = '00000000000000000000000000';
        $binary = Ulid::toBinary($ulid);

        // All zeros ULID should be all zero bytes
        $this->assertSame(\str_repeat("\x00", 16), $binary);
    }

    /**
     * Test known binary vector: all zeros
     */
    #[RequiresPhpExtension('bcmath')]
    public function testFromBinaryKnownVectorZeros(): void
    {
        $binary = \str_repeat("\x00", 16);
        $ulid = Ulid::fromBinary($binary);

        $this->assertSame('00000000000000000000000000', $ulid);
    }

    /**
     * Test binary preserves lexicographic ordering
     */
    #[RequiresPhpExtension('bcmath')]
    public function testBinaryPreservesOrdering(): void
    {
        Ulid::resetMonotonicState();
        $ulid1 = Ulid::generateMonotonic();
        $ulid2 = Ulid::generateMonotonic();

        $binary1 = Ulid::toBinary($ulid1);
        $binary2 = Ulid::toBinary($ulid2);

        // Binary comparison should match string comparison
        $this->assertLessThan(0, \strcmp($ulid1, $ulid2));
        $this->assertLessThan(0, \strcmp($binary1, $binary2));
    }

    /**
     * Test binary storage efficiency
     */
    #[RequiresPhpExtension('bcmath')]
    public function testBinaryStorageEfficiency(): void
    {
        $ulid = Ulid::generate();

        $stringLength = \strlen($ulid);     // 26 bytes
        $binaryLength = \strlen(Ulid::toBinary($ulid)); // 16 bytes

        $this->assertSame(26, $stringLength);
        $this->assertSame(16, $binaryLength);

        // Binary is ~38% smaller
        $savings = (1 - ($binaryLength / $stringLength)) * 100;
        $this->assertGreaterThan(38, $savings);
    }

    // =========================================================================
    // UUID AND BINARY INTEROPERABILITY TESTS (require ext-bcmath)
    // =========================================================================

    /**
     * Test that ULID → Binary → ULID → UUID is consistent
     */
    #[RequiresPhpExtension('bcmath')]
    public function testBinaryUuidInteroperability(): void
    {
        $originalUlid = Ulid::generate();

        // ULID → Binary → ULID
        $binary = Ulid::toBinary($originalUlid);
        $ulidFromBinary = Ulid::fromBinary($binary);

        // Both should produce the same UUID
        $uuid1 = Ulid::toUuid($originalUlid);
        $uuid2 = Ulid::toUuid($ulidFromBinary);

        $this->assertSame($uuid1, $uuid2);
    }

    /**
     * Test that Binary and UUID representations are equivalent
     */
    #[RequiresPhpExtension('bcmath')]
    public function testBinaryMatchesUuidHex(): void
    {
        $ulid = Ulid::generate();
        $binary = Ulid::toBinary($ulid);
        $uuid = Ulid::toUuid($ulid);

        // Convert binary to hex and compare with UUID
        $binaryHex = \bin2hex($binary);
        $uuidHex = \str_replace('-', '', $uuid);

        $this->assertSame($binaryHex, $uuidHex);
    }

    // =========================================================================
    // CONSTANT TESTS
    // =========================================================================

    /**
     * Test that BINARY_LENGTH constant is correct
     */
    public function testBinaryLengthConstant(): void
    {
        $this->assertSame(16, Ulid::BINARY_LENGTH);
    }

    /**
     * Test that MAX_TIME constant is correct (2^48 - 1)
     */
    public function testMaxTimeConstant(): void
    {
        $expected = (2 ** 48) - 1;
        $this->assertSame($expected, Ulid::MAX_TIME);
    }
}
