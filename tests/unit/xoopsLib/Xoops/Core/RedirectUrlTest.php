<?php
require_once(__DIR__.'/../../../init_new.php');

use PHPUnit\Framework\Attributes\DataProvider;
use Xoops\Core\RedirectUrl;

class RedirectUrlTest extends \PHPUnit\Framework\TestCase
{
    private const BASE = 'https://site.test';
    private const SUB = 'https://site.test/xoops';

    public static function sanitizeCases(): array
    {
        return [
            'bare relative'         => ['viewpmsg.php?op=in', 'viewpmsg.php?op=in'],
            'parent relative'       => ['../index.php', '../index.php'],
            'same site absolute'    => ['https://site.test/user.php', 'https://site.test/user.php'],
            'root relative'         => ['/modules/pm/index.php', '/modules/pm/index.php'],
            'history back'          => ['javascript:history.go(-1)', 'javascript:history.go(-1)'],
            'look-alike host'       => ['https://site.test.evil.test/', self::BASE],
            'userinfo'              => ['https://site.test@evil.test/', self::BASE],
            'scheme relative'       => ['//evil.test/', self::BASE],
            'backslash relative'    => ['/\\evil.test/', self::BASE],
            'other scheme'          => ['http://site.test/', self::BASE],
            'other port'            => ['https://site.test:8443/', self::BASE],
            'javascript'            => ['javascript:alert(1)', self::BASE],
            'entity javascript'     => ['java&#x73;cript:alert(1)', self::BASE],
            'data uri'              => ['data:text/html,x', self::BASE],
            'tab scheme relative'   => ["/\t/evil.test/", self::BASE],
            'DEL char'              => ["/x\x7F", self::BASE],
            'scheme without host'   => ['https:/evil.test/', self::BASE],
            'encoded slash in head' => ['/%2fevil.test/', self::BASE],
            'amp in query'          => ['https://site.test/a.php?x=1&amp;y=2', 'https://site.test/a.php?x=1&amp;y=2'],
        ];
    }

    #[DataProvider('sanitizeCases')]
    public function testSanitize(string $url, string $expected): void
    {
        $this->assertSame($expected, RedirectUrl::sanitize($url, self::BASE));
    }

    public function testSanitizeAllowsExternalWhenAsked(): void
    {
        $this->assertSame('https://evil.test/', RedirectUrl::sanitize('https://evil.test/', self::BASE, true));
        $this->assertSame(self::BASE, RedirectUrl::sanitize('javascript:alert(1)', self::BASE, true));
    }

    public function testDefaultPortMatches(): void
    {
        $this->assertTrue(RedirectUrl::isLocal('https://site.test:443/x', self::BASE));
        $this->assertTrue(RedirectUrl::isLocal('HTTPS://SITE.TEST/x', self::BASE));
    }

    public static function validateCases(): array
    {
        return [
            'root relative in base'   => [self::SUB, '/xoops/modules/pm/', '/xoops/modules/pm/'],
            'absolute in base'        => [self::SUB, 'https://site.test/xoops/x.php', 'https://site.test/xoops/x.php'],
            'outside base path'       => [self::SUB, '/other/x.php', ''],
            'base path prefix only'   => [self::SUB, '/xoopsevil/x.php', ''],
            'dot dot'                 => [self::BASE, '/a/../b', ''],
            'encoded dot dot'         => [self::BASE, '/a/%2e%2e/b', ''],
            'bare relative'           => [self::BASE, 'index.php', ''],
            'scheme relative'         => [self::BASE, '//evil.test/', ''],
            'external'                => [self::BASE, 'https://evil.test/', ''],
            'empty'                   => [self::BASE, '  ', ''],
            'trimmed'                 => [self::BASE, ' /x.php ', '/x.php'],
        ];
    }

    #[DataProvider('validateCases')]
    public function testValidate(string $base, string $redirect, string $expected): void
    {
        $this->assertSame($expected, RedirectUrl::validate($redirect, $base));
    }

    public static function afterLoginCases(): array
    {
        return [
            'empty'                 => [self::BASE, '', 'https://site.test/index.php'],
            'encoded request uri'   => [self::BASE, '%2Fmodules%2Fpm%2F', 'https://site.test/modules/pm/'],
            'subdir path prepended' => [self::SUB, '/modules/pm/', 'https://site.test/xoops/modules/pm/'],
            'subdir path kept'      => [self::SUB, '/xoops/modules/pm/', 'https://site.test/xoops/modules/pm/'],
            'userinfo trick'        => [self::BASE, '@evil.test/', 'https://site.test/index.php'],
            'host suffix trick'     => [self::BASE, '.evil.test/', 'https://site.test/index.php'],
            'scheme relative'       => [self::BASE, '//evil.test/', 'https://site.test/index.php'],
            'register'              => [self::BASE, '/register.php', 'https://site.test/index.php'],
            'entity register'       => [self::BASE, '/&#x72;egister.php', 'https://site.test/index.php'],
            'double encoded reg'    => [self::BASE, '/%2572egister.php', 'https://site.test/index.php'],
            'absolute same site'    => [self::BASE, 'https://site.test/a.php', 'https://site.test/a.php'],
        ];
    }

    #[DataProvider('afterLoginCases')]
    public function testAfterLogin(string $base, string $redirect, string $expected): void
    {
        $this->assertSame($expected, RedirectUrl::afterLogin($redirect, $base));
    }
}
