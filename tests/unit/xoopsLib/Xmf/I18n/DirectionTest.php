<?php

declare(strict_types=1);

namespace Xmf\Test\I18n;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Xmf\I18n\Direction;

class DirectionTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        Direction::clearCache();
    }

    protected function tearDown(): void
    {
        Direction::clearCache();
    }

    public function testDirDefaultsToLtr(): void
    {
        $this->assertSame(Direction::LTR, Direction::dir('en'));
    }

    public function testDirDetectsRtlFromArabic(): void
    {
        $this->assertSame(Direction::RTL, Direction::dir('ar'));
    }

    // loading the 2.6 \Xoops class would switch every later test off the legacy path
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testDirUsesXoops26CurrentLocale(): void
    {
        require_once \dirname(__DIR__, 5) . '/xoops_lib/Xoops.php';
        \Xoops\Locale::setCurrent('ar_SA');
        $this->assertSame(Direction::RTL, Direction::dir());
    }

    /**
     * Run $callback and return the messages of the user errors it raised.
     */
    private static function userErrors(callable $callback): array
    {
        $errors = [];
        set_error_handler(static function (int $level, string $message) use (&$errors): bool {
            $errors[] = [$level, $message];
            return true;
        }, E_USER_WARNING | E_USER_DEPRECATED);
        try {
            $callback();
        } finally {
            restore_error_handler();
        }
        return $errors;
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLegacyLangcodeSetsGlobalDirection(): void
    {
        \define('_LANGCODE', 'ar');

        $this->assertSame(Direction::RTL, Direction::dir());
        $this->assertSame(Direction::RTL, Direction::dir(Direction::AUTO));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLegacyTextDirectionOverridesLocale(): void
    {
        \define('_LANGCODE', 'en');
        \define('_TEXT_DIRECTION', ' RTL ');

        $this->assertSame(Direction::RTL, Direction::dir());
        $this->assertSame(Direction::LTR, Direction::dir('en'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLegacyInvalidTextDirectionWarnsAndFallsBack(): void
    {
        \define('_LANGCODE', 'he');
        \define('_TEXT_DIRECTION', 'sideways');

        $result = null;
        $errors = self::userErrors(static function () use (&$result): void {
            $result = Direction::dir();
        });

        $this->assertSame(Direction::RTL, $result);
        $this->assertSame(E_USER_WARNING, $errors[0][0]);
        $this->assertStringContainsString('sideways', $errors[0][1]);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLegacyRtlConstantIsDeprecatedButHonoured(): void
    {
        \define('_RTL', true);

        $result = null;
        $errors = self::userErrors(static function () use (&$result): void {
            $result = Direction::dir();
            Direction::dir();
        });

        $this->assertSame(Direction::RTL, $result);
        $this->assertCount(1, $errors, 'the cached result does not warn again');
        $this->assertSame(E_USER_DEPRECATED, $errors[0][0]);

        // clearCache() starts a new cache lifetime, which warns again
        Direction::clearCache();
        $this->assertCount(1, self::userErrors(static function (): void {
            Direction::dir();
        }));
    }

    public function testLocaleCacheIsBounded(): void
    {
        for ($i = 0; $i < 150; ++$i) {
            Direction::dir('xx-' . $i);
        }

        $cache = (new \ReflectionClass(Direction::class))->getStaticPropertyValue('cacheByLocale');
        $limit = (new \ReflectionClassConstant(Direction::class, 'MAX_LOCALE_CACHE'))->getValue();
        $this->assertCount($limit, $cache);
        $this->assertArrayNotHasKey('xx-0', $cache, 'the oldest entry is evicted first');
        $this->assertArrayHasKey('xx-149', $cache);
        $this->assertSame(Direction::RTL, Direction::dir('ar'));
    }

    public function testDirDetectsRtlFromHebrew(): void
    {
        $this->assertSame(Direction::RTL, Direction::dir('he'));
    }

    public function testDirDetectsRtlFromPersian(): void
    {
        $this->assertSame(Direction::RTL, Direction::dir('fa'));
    }

    public function testDirDetectsRtlFromUrdu(): void
    {
        $this->assertSame(Direction::RTL, Direction::dir('ur'));
    }

    public function testDirDetectsLtrFromFrench(): void
    {
        $this->assertSame(Direction::LTR, Direction::dir('fr'));
    }

    public function testDirDetectsLtrFromGerman(): void
    {
        $this->assertSame(Direction::LTR, Direction::dir('de'));
    }

    public function testDirHandlesLocaleWithRegion(): void
    {
        $this->assertSame(Direction::RTL, Direction::dir('ar-SA'));
        $this->assertSame(Direction::LTR, Direction::dir('en-US'));
    }

    public function testDirHandlesLocaleWithUnderscore(): void
    {
        $this->assertSame(Direction::RTL, Direction::dir('ar_SA'));
        $this->assertSame(Direction::LTR, Direction::dir('en_US'));
    }

    public function testDirHandlesEmptyLocale(): void
    {
        $this->assertSame(Direction::LTR, Direction::dir(''));
    }

    public function testAutoSentinelIsNormalizedToNull(): void
    {
        // AUTO is normalized to null inside dir(), triggering global
        // auto-detection. Without legacy constants defined, it defaults
        // to 'en' and resolves to LTR.
        $this->assertSame(Direction::LTR, Direction::dir(Direction::AUTO));
    }

    public function testIsRtlReturnsTrueForRtlLocale(): void
    {
        $this->assertTrue(Direction::isRtl('ar'));
        $this->assertTrue(Direction::isRtl('he'));
    }

    public function testIsRtlReturnsFalseForLtrLocale(): void
    {
        $this->assertFalse(Direction::isRtl('en'));
        $this->assertFalse(Direction::isRtl('fr'));
    }

    public function testCachingReturnsConsistentResults(): void
    {
        $first = Direction::dir('ar');
        $second = Direction::dir('ar');
        $this->assertSame($first, $second);
        $this->assertSame(Direction::RTL, $first);
    }

    public function testClearCacheResetsState(): void
    {
        Direction::dir('ar');
        Direction::clearCache();
        // After clearing, a subsequent call should still produce the same result
        $this->assertSame(Direction::RTL, Direction::dir('ar'));
    }

    public function testConstantValues(): void
    {
        $this->assertSame('ltr', Direction::LTR);
        $this->assertSame('rtl', Direction::RTL);
        $this->assertSame('auto', Direction::AUTO);
    }

    public function testDirDetectsRtlFromOldHebrewCode(): void
    {
        $this->assertSame(Direction::RTL, Direction::dir('iw'));
    }

    public function testDirDetectsRtlFromKurdish(): void
    {
        $this->assertSame(Direction::RTL, Direction::dir('ku'));
        $this->assertSame(Direction::RTL, Direction::dir('ckb'));
    }

    public function testDirDetectsRtlFromYiddish(): void
    {
        $this->assertSame(Direction::RTL, Direction::dir('yi'));
    }

    public function testDirCaseInsensitive(): void
    {
        $this->assertSame(Direction::RTL, Direction::dir('AR'));
        $this->assertSame(Direction::RTL, Direction::dir('Ar'));
    }
}
