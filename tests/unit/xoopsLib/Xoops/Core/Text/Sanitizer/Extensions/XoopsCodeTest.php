<?php
namespace Xoops\Core\Text\Sanitizer\Extensions;

use Xoops\Core\Text\Sanitizer;

require_once __DIR__.'/../../../../../../init_new.php';

class XoopsCodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var XoopsCode
     */
    protected $object;

    /**
     * @var Sanitizer
     */
    protected $sanitizer;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->sanitizer = Sanitizer::getInstance();
        $this->object = new XoopsCode($this->sanitizer);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testContracts()
    {
        $this->assertInstanceOf('\Xoops\Core\Text\Sanitizer\ExtensionAbstract', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Text\Sanitizer\SanitizerComponent', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Text\Sanitizer\SanitizerConfigurable', $this->object);
    }

    public function testRegisterExtensionProcessing()
    {
        $this->sanitizer->enableComponentForTesting('xoopscode');
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('siteurl'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('url'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('color'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('size'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('font'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('email'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('b'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('i'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('u'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('d'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('center'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('left'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('right'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function shortcodeProvider(): array
    {
        return [
            'hex color' => ['[color=f00]x[/color]', '<span style="color: #f00">x</span>'],
            'named color' => ['[color=red]x[/color]', '<span style="color: red">x</span>'],
            'color quote escape' => ['[color=red\x22onmouseover=alert(1)]x[/color]', 'x'],
            'color expression' => ['[color=red;background:url(x)]x[/color]', 'x'],
            'size' => ['[size=12px]x[/size]', '<span style="font-size: 12px">x</span>'],
            'size keyword' => ['[size=x-large]x[/size]', '<span style="font-size: x-large">x</span>'],
            'size quote escape' => ['[size=1\x22onclick=alert(1)]x[/size]', 'x'],
            'font' => ['[font=Arial,sans-serif]x[/font]', '<span style="font-family: Arial,sans-serif">x</span>'],
            'font quote' => ["[font='Verdana']x[/font]", '<span style="font-family: &#039;Verdana&#039;">x</span>'],
            'font quote escape' => ['[font=a\x22onclick=alert(1)]x[/font]', 'x'],
            'url' => ['[url=https://example.com/a?b=1&c=2]x[/url]', '<a href="https://example.com/a?b=1&amp;c=2">x</a>'],
            'url javascript' => ['[url=javascript://%0aalert(1)]x[/url]', 'x'],
            'url javascript after scheme text' => ['[url=javascript:alert(1)//https://e.com]x[/url]', 'x'],
            'url mailto' => ['[url=mailto:a@example.com]x[/url]', 'x'],
            'url quote escape' => ['[url=https://e.com/\x22onclick=alert(1)]x[/url]', '<a href="https://e.com/&quot;onclick=alert(1)">x</a>'],
            'email' => ['[email]a@example.com[/email]', '<a href="mailto:a@example.com">a@example.com</a>'],
            'email invalid' => ['[email]a@b" onclick="x[/email]', 'a@b&quot; onclick=&quot;x'],
        ];
    }

    public function testRelativeUrlWithAbsoluteQueryStaysALink()
    {
        $this->sanitizer->enableComponentForTesting('xoopscode');
        $out = $this->sanitizer->getShortCodes()->process('[url=page.php?next=https://e.com]x[/url]');
        $this->assertSame(
            '<a href="' . \Xoops::getInstance()->path('page.php?next=https://e.com', true) . '">x</a>',
            $out
        );
        $this->assertStringStartsWith('<a href="http', $out);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('shortcodeProvider')]
    public function testShortcodeArgumentsCannotLeaveTheirAttribute(string $in, string $expected)
    {
        $this->sanitizer->enableComponentForTesting('xoopscode');
        $this->assertSame($expected, $this->sanitizer->getShortCodes()->process($in));
    }
}
