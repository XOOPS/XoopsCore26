<?php

declare(strict_types=1);

namespace Xmf\Test\Security;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Xmf\Security\DecompressionException;
use Xmf\Security\Format;
use Xmf\Security\Serializer;

#[CoversClass(Serializer::class)]
class SerializerTest extends \PHPUnit\Framework\TestCase
{
    private const MAX_SIZE = 5000000;

    protected function tearDown(): void
    {
        Serializer::enableDebug(false);
        Serializer::setLegacyLogger(null);
    }

    // ---------------------------------------------------------------- JSON

    public function testJsonRoundTrip(): void
    {
        $data = ['name' => 'Ünïcode/path', 'list' => [1, 2.5, true, null]];
        $json = Serializer::toJson($data);

        $this->assertSame('{"name":"Ünïcode/path","list":[1,2.5,true,null]}', $json);
        $this->assertSame($data, Serializer::fromJson($json));
    }

    public function testToJsonRejectsOversizedOutput(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::toJson(str_repeat('a', self::MAX_SIZE));
    }

    public function testToJsonRejectsUnencodableData(): void
    {
        $this->expectException(\JsonException::class);
        Serializer::toJson("\xB1\x31");
    }

    public function testFromJsonRejectsEmptyString(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Serializer::fromJson('');
    }

    public function testFromJsonRejectsOversizedPayload(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::fromJson(str_repeat(' ', self::MAX_SIZE + 1));
    }

    public function testFromJsonRejectsMalformedJson(): void
    {
        $this->expectException(\JsonException::class);
        Serializer::fromJson('{"a":');
    }

    public function testJsonOnly(): void
    {
        $this->assertSame(['a' => 1], Serializer::jsonOnly('{"a":1}'));
        $this->assertNull(Serializer::jsonOnly(''));
        $this->assertNull(Serializer::jsonOnly('   '));
        $this->assertNull(Serializer::jsonOnly('{bad'));
        $this->assertNull(Serializer::jsonOnly('a:1:{i:0;i:1;}'));
    }

    // ----------------------------------------------------------------- PHP

    public static function phpValues(): array
    {
        return [
            'null'   => [null],
            'false'  => [false],
            'true'   => [true],
            'int'    => [-42],
            'float'  => [1.5],
            'string' => ['hello'],
            'array'  => [['a' => [1, 2], 'b' => 'c']],
        ];
    }

    #[DataProvider('phpValues')]
    public function testPhpRoundTrip($value): void
    {
        $this->assertSame($value, Serializer::fromPhp(Serializer::toPhp($value)));
    }

    public function testToPhpRejectsResource(): void
    {
        $handle = fopen('php://memory', 'r');
        try {
            $this->expectException(\InvalidArgumentException::class);
            Serializer::toPhp($handle);
        } finally {
            fclose($handle);
        }
    }

    public function testToPhpRejectsClosure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Serializer::toPhp(static function () {
        });
    }

    public function testToPhpRejectsNestedClosure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Serializer::toPhp(['callback' => static function () {
        }]);
    }

    public function testToPhpRejectsOversizedOutput(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::toPhp(str_repeat('a', self::MAX_SIZE));
    }

    public function testFromPhpRejectsEmptyString(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Serializer::fromPhp('');
    }

    public function testFromPhpRejectsOversizedPayload(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::fromPhp(str_repeat('a', self::MAX_SIZE + 1));
    }

    public function testFromPhpRejectsMalformedPayload(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Serializer::fromPhp('a:1:{i:0;');
    }

    public function testFromPhpRejectsNulBytesWithoutAllowedClasses(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::fromPhp(serialize("a\0b"));
    }

    public function testFromPhpDoesNotRestoreObjectsByDefault(): void
    {
        $result = Serializer::fromPhp(serialize(new \ArrayObject([1])));

        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $result);
    }

    public function testFromPhpRestoresAllowedClasses(): void
    {
        $object = new \stdClass();
        $object->name = 'x';

        $result = Serializer::fromPhp(serialize($object), [\stdClass::class]);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('x', $result->name);
    }

    // -------------------------------------------------------------- legacy

    public function testLegacyRoundTrip(): void
    {
        $data = ['legacy' => true, 'items' => [1, 2, 3]];

        $this->assertSame($data, Serializer::fromLegacy(Serializer::toLegacy($data)));
    }

    public function testFromLegacyAcceptsRawSerializedPayloadAndLogsIt(): void
    {
        $calls = [];
        Serializer::setLegacyLogger(static function (string $file, int $line, string $preview) use (&$calls): void {
            $calls[] = [$file, $line, $preview];
        });

        $this->assertSame([1, 2], Serializer::fromLegacy(serialize([1, 2])));
        $this->assertCount(1, $calls);
        // the logger reports the first caller outside Serializer
        $this->assertStringNotContainsString('Serializer.php', $calls[0][0]);
        $this->assertGreaterThan(0, $calls[0][1]);
        $this->assertSame(serialize([1, 2]), $calls[0][2]);
    }

    public function testLegacyLoggerReportsTheCallSite(): void
    {
        $this->markTestIncomplete(
            'Upstream XMF 1.3.2 bug: logLegacy() skips the frame that holds the call site (its class is Serializer), '
            . 'so it reports the caller of the calling function instead.'
        );
        $location = null;
        Serializer::setLegacyLogger(static function (string $file, int $line) use (&$location): void {
            $location = [$file, $line];
        });

        $line = __LINE__ + 1;
        Serializer::fromLegacy(serialize([1, 2]));

        $this->assertSame([__FILE__, $line], $location);
    }

    public function testLegacyLoggerTruncatesLongPreviews(): void
    {
        $preview = null;
        Serializer::setLegacyLogger(static function (string $file, int $line, string $text) use (&$preview): void {
            $preview = $text;
        });

        Serializer::fromLegacy(Serializer::toLegacy(str_repeat('x', 200)));

        $this->assertSame(53, \strlen((string) $preview));
        $this->assertStringEndsWith('...', (string) $preview);
    }

    public function testFromLegacyRejectsNonBase64Garbage(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::fromLegacy('not a serialized or base64 value');
    }

    public function testFromLegacyRejectsNulBytesInDecodedPayload(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::fromLegacy(base64_encode(serialize("abc\0def-padding")));
    }

    #[RequiresPhpExtension('zlib')]
    public function testFromLegacyDecodesGzipPayload(): void
    {
        $this->markTestIncomplete(
            'Upstream XMF 1.3.2 bug: safeGzipDecode() calls inflate_add(ZLIB_FINISH) after the stream has ended, '
            . 'which sets ZLIB_BUF_ERROR, so every valid gzip payload is rejected as truncated.'
        );
        $data = ['compressed' => str_repeat('abc', 100)];

        $this->assertSame($data, Serializer::fromLegacy(base64_encode(gzencode(serialize($data)))));
    }

    #[RequiresPhpExtension('zlib')]
    public function testFromLegacyRejectsTruncatedGzip(): void
    {
        $gzip = gzencode(serialize(['compressed' => str_repeat('abc', 100)]));

        $this->expectException(DecompressionException::class);
        Serializer::fromLegacy(base64_encode(substr($gzip, 0, 40)));
    }

    #[RequiresPhpExtension('zlib')]
    public function testFromLegacyRejectsGzipBomb(): void
    {
        $bomb = gzencode(serialize(str_repeat('a', self::MAX_SIZE + 1)), 9);

        $this->expectException(DecompressionException::class);
        $this->expectExceptionMessage('Decompressed payload exceeds');
        Serializer::fromLegacy(base64_encode($bomb));
    }

    public function testFromLegacyAcceptsSerializedNull(): void
    {
        $this->markTestIncomplete(
            'Upstream XMF 1.3.2 bug: fromLegacy() treats a valid serialized null (N;) as a parse failure.'
        );
        $this->assertNull(Serializer::fromLegacy('N;'));
    }

    public function testFromLegacyAcceptsShortToLegacyOutput(): void
    {
        $this->markTestIncomplete(
            'Upstream XMF 1.3.2 bug: base64 payloads under 16 characters, such as toLegacy(false), are rejected.'
        );
        $this->assertFalse(Serializer::fromLegacy(Serializer::toLegacy(false)));
    }

    // ------------------------------------------------------ detect / from

    public static function detectCases(): array
    {
        return [
            'empty'          => ['', Format::AUTO],
            'json object'    => ['{"a":1}', Format::JSON],
            'json array'     => ['  [1,2]', Format::JSON],
            'broken json'    => ['{"a":', Format::AUTO],
            'php array'      => [serialize([1, 2]), Format::PHP],
            'php null'       => ['N;', Format::PHP],
            'php double'     => [serialize(1.5), Format::PHP],
            'php object'     => [serialize(new \stdClass()), Format::PHP],
            'legacy'         => [base64_encode(serialize(['a' => 'b'])), Format::LEGACY],
            'plain text'     => ['hello world', Format::AUTO],
            'base64 text'    => [base64_encode('just some plain text'), Format::AUTO],
        ];
    }

    #[DataProvider('detectCases')]
    public function testDetect(string $payload, string $expected): void
    {
        $this->assertSame($expected, Serializer::detect($payload));
    }

    public function testFromDispatchesOnDetectedFormat(): void
    {
        $data = ['k' => 'v'];

        $this->assertSame($data, Serializer::from(Serializer::toJson($data)));
        $this->assertSame($data, Serializer::from(Serializer::toPhp($data)));
        $this->assertSame($data, Serializer::from(Serializer::toLegacy($data)));
        $this->assertNull(Serializer::from('N;'));
    }

    public function testFromFallsBackToJsonForScalars(): void
    {
        $this->assertSame(42, Serializer::from('42'));
        $this->assertNull(Serializer::from('null'));
    }

    public function testFromRejectsGarbage(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::from('definitely not serialized');
    }

    // --------------------------------------------- typed helpers / tryFrom

    public function testToArrayForEachFormat(): void
    {
        $data = ['x' => 1];

        $this->assertSame($data, Serializer::toArray(Serializer::toJson($data), Format::JSON));
        $this->assertSame($data, Serializer::toArray(Serializer::toPhp($data), Format::PHP));
        $this->assertSame($data, Serializer::toArray(Serializer::toLegacy($data), Format::LEGACY));
        $this->assertSame($data, Serializer::toArray(Serializer::toJson($data)));
    }

    public function testToArrayRejectsNonArray(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Serializer::toArray(Serializer::toPhp('scalar'), Format::PHP);
    }

    public function testToArrayRejectsUnknownFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Serializer::toArray('{}', 'xml');
    }

    public function testToObjectRestoresRequestedClass(): void
    {
        $object = new \ArrayObject([1, 2]);

        $this->assertEquals($object, Serializer::toObject(serialize($object), \ArrayObject::class));
        $this->assertEquals(
            $object,
            Serializer::toObject(base64_encode(serialize($object)), \ArrayObject::class, Format::LEGACY)
        );
    }

    public function testToObjectRejectsUnknownClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Serializer::toObject(serialize(new \stdClass()), 'No\\Such\\ClassName');
    }

    public function testToObjectRejectsJsonFormat(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::toObject('{}', \stdClass::class, Format::JSON);
    }

    public function testToObjectRejectsOtherClasses(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Serializer::toObject(serialize(new \ArrayObject()), \stdClass::class);
    }

    public function testTryFromReturnsDefaultOnFailure(): void
    {
        $this->assertSame(['a' => 1], Serializer::tryFrom('{"a":1}', 'd', Format::JSON));
        $this->assertSame([1], Serializer::tryFrom(serialize([1]), 'd', Format::PHP));
        $this->assertSame([1], Serializer::tryFrom(base64_encode(serialize([1])), 'd', Format::LEGACY));
        $this->assertSame([1], Serializer::tryFrom('[1]', 'd'));
        $this->assertSame('d', Serializer::tryFrom('{bad', 'd', Format::JSON));
        $this->assertSame('d', Serializer::tryFrom('anything', 'd', 'xml'));
    }

    public function testScalarsOnly(): void
    {
        $this->assertSame('x', Serializer::scalarsOnly(Serializer::toJson('x'), Format::JSON));
        $this->assertSame(3, Serializer::scalarsOnly(Serializer::toPhp(3), Format::PHP));
        $this->assertSame(
            'a longer legacy value',
            Serializer::scalarsOnly(Serializer::toLegacy('a longer legacy value'), Format::LEGACY)
        );
        $this->assertNull(Serializer::scalarsOnly('null'));
    }

    public function testScalarsOnlyRejectsArrays(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Serializer::scalarsOnly('[1]', Format::JSON);
    }

    public function testScalarsOnlyRejectsUnknownFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Serializer::scalarsOnly('1', 'xml');
    }

    // --------------------------------------------------------------- debug

    public function testDebugStatsAreEmptyWhenDisabled(): void
    {
        $this->assertSame([], Serializer::getDebugStats());
    }

    public function testDebugStatsRecordOperationsAndErrors(): void
    {
        Serializer::enableDebug();
        Serializer::fromPhp(serialize([1]));
        try {
            Serializer::fromPhp('a:1:{');
        } catch (\UnexpectedValueException $e) {
            // expected
        }

        $stats = Serializer::getDebugStats();

        $this->assertSame(2, $stats['total_operations']);
        $this->assertSame([Format::PHP => 2], $stats['formats_detected']);
        $this->assertCount(1, $stats['errors']);
        $this->assertSame('fromPhp', $stats['errors'][0]['operation']);
        $this->assertIsArray($stats['errors'][0]['trace']);

        Serializer::enableDebug(false);
        $this->assertSame([], Serializer::getDebugStats());
    }
}
