<?php

declare(strict_types=1);

namespace Xmf\Test\I18n;

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
