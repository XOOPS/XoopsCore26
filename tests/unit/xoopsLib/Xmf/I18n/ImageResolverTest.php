<?php

declare(strict_types=1);

namespace Xmf\Test\I18n;

use Xmf\I18n\Direction;
use Xmf\I18n\ImageResolver;

class ImageResolverTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        Direction::clearCache();
        ImageResolver::clearCache();
    }

    protected function tearDown(): void
    {
        Direction::clearCache();
        ImageResolver::clearCache();
    }

    public function testEmptyPathReturnsEmpty(): void
    {
        $this->assertSame('', ImageResolver::resolve(''));
    }

    public function testAbsoluteHttpsUrlReturnedAsIs(): void
    {
        $url = 'https://example.com/images/arrow.png';
        $this->assertSame($url, ImageResolver::resolve($url));
    }

    public function testAbsoluteHttpUrlReturnedAsIs(): void
    {
        $url = 'http://example.com/images/arrow.png';
        $this->assertSame($url, ImageResolver::resolve($url));
    }

    public function testProtocolRelativeUrlReturnedAsIs(): void
    {
        $url = '//cdn.example.com/images/arrow.png';
        $this->assertSame($url, ImageResolver::resolve($url));
    }

    public function testPathTraversalInBasePathRejected(): void
    {
        $this->assertSame('../secret/file.png', ImageResolver::resolve('../secret/file.png'));
        $this->assertSame('images/../../etc/passwd.png', ImageResolver::resolve('images/../../etc/passwd.png'));
    }

    public function testPathTraversalInLangRejected(): void
    {
        $result = ImageResolver::resolve('images/arrow.png', '../../etc');
        $this->assertSame('images/arrow.png', $result);
    }

    public function testSlashInLangRejected(): void
    {
        $result = ImageResolver::resolve('images/arrow.png', 'en/../../etc');
        $this->assertSame('images/arrow.png', $result);
    }

    public function testMalformedPathWithNoExtension(): void
    {
        $this->assertSame('noextension', ImageResolver::resolve('noextension', 'en', Direction::LTR));
    }

    public function testResolveWithoutXoopsRootReturnsBasePath(): void
    {
        // Without XOOPS_ROOT_PATH defined, no file system check occurs
        // The method falls through to basePath
        $result = ImageResolver::resolve('images/arrow.png', 'en', Direction::LTR);
        $this->assertSame('images/arrow.png', $result);
    }

    public function testCachingReturnsConsistentResults(): void
    {
        $first = ImageResolver::resolve('images/arrow.png', 'en', Direction::LTR);
        $second = ImageResolver::resolve('images/arrow.png', 'en', Direction::LTR);
        $this->assertSame($first, $second);
    }

    public function testClearCacheWorks(): void
    {
        ImageResolver::resolve('images/arrow.png', 'en', Direction::LTR);
        ImageResolver::clearCache();
        $result = ImageResolver::resolve('images/arrow.png', 'en', Direction::LTR);
        $this->assertSame('images/arrow.png', $result);
    }

    public function testResolveWithDirectoryPrefix(): void
    {
        $result = ImageResolver::resolve('img/icons/arrow.png', 'fr', Direction::LTR);
        $this->assertSame('img/icons/arrow.png', $result);
    }

    public function testResolveWithRtlDirection(): void
    {
        $result = ImageResolver::resolve('images/arrow.png', 'ar', Direction::RTL);
        $this->assertSame('images/arrow.png', $result);
    }

    public function testResolveWithNullLangDefaultsToEn(): void
    {
        $result = ImageResolver::resolve('images/arrow.png', null, Direction::LTR);
        $this->assertSame('images/arrow.png', $result);
    }
}
