<?php
namespace Xoops\Core\Text;

require_once __DIR__.'/../../../../init_new.php';
require_once __DIR__.'/../../../../../../xoops_lib/smarty/xoops_plugins/outputfilter.shortcodes.php';

class OutputFilterShortCodesTest extends \PHPUnit\Framework\TestCase
{
    protected function filter($html)
    {
        $template = $this->getMockBuilder('Smarty_Internal_Template')
            ->disableOriginalConstructor()
            ->getMock();
        return smarty_outputfilter_shortcodes($html, $template);
    }

    public function testTextBetweenTagsIsProcessed()
    {
        $this->assertSame('<p><strong>bold</strong></p>', $this->filter('<p>[b]bold[/b]</p>'));
    }

    public function testComparisonTextIsNotTreatedAsTag()
    {
        $this->assertSame('a < b <strong>c</strong> > d', $this->filter('a < b [b]c[/b] > d'));
    }

    public function testAttributesAreNotProcessed()
    {
        $html = '<img src="x.gif" alt="[b]x[/b]" title="[url=http://e.test/ onmouseover=alert(1)]y[/url]">';
        $this->assertSame($html, $this->filter($html));
        $html = '<a onclick="if (a > 0) {}" title=\'b > a [b]x[/b]\'>[b]y[/b]</a>';
        $this->assertSame(
            '<a onclick="if (a > 0) {}" title=\'b > a [b]x[/b]\'><strong>y</strong></a>',
            $this->filter($html)
        );
    }

    public function testMaskShortcodeIsNotPublic()
    {
        $payload = '[nosc42]' . base64_encode('<script>alert(1)</script>') . '[/nosc42]';
        $out = $this->filter('<p>' . $payload . '</p>');
        $this->assertStringNotContainsString('<script>', $out);
    }

    public function testFormAndScriptBodiesAreNotProcessed()
    {
        $html = '<textarea>[b]x[/b]</textarea><script>var a = "[b]";</script><select><option>[b]</option></select>'
            . '<style>a[href] {}</style><!-- [b] -->';
        $this->assertSame($html, $this->filter($html));
    }

    public function testScriptBodyEndsOnlyAtClosingTag()
    {
        $html = '<script>var a = "x/script>[b]";</script>';
        $this->assertSame($html, $this->filter($html));
    }

    public function testHeldMarkupCopiedIntoGeneratedAttributeIsDropped()
    {
        $this->assertSame(
            '<p>x<img src="a">z</a></p>',
            $this->filter('<p>[email]x<img src="a">z[/email]</p>')
        );
    }

    public function testHeldMarkupCannotJoinIntoJavascriptUrl()
    {
        $out = $this->filter('<p>[url=java<b></b>script://%0Aalert(1)]click[/url]</p>');
        $this->assertStringNotContainsString('href', $out);
        $this->assertStringNotContainsString('javascript', $out);
        $this->assertStringContainsString('click', $out);
    }

    public function testGeneratedTagWithoutHeldMarkupIsKept()
    {
        $out = $this->filter('<p>[url=http://example.test/a]click[/url]</p>');
        $this->assertStringContainsString('href="http://example.test/a"', $out);
        $this->assertStringContainsString('>click</a>', $out);
    }
}
