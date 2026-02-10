<?php
namespace Xoops\Core\Text\Sanitizer\Extensions;

use Xoops\Core\Text\Sanitizer;

require_once __DIR__.'/../../../../../../init_new.php';

class YouTubeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var YouTube
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
        $this->object = new YouTube($this->sanitizer);
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

    public function testGetDhtmlEditorSupport()
    {
        $support = $this->object->getDhtmlEditorSupport('testeditorarea');
        $this->assertTrue(2 == count($support));
        $this->assertTrue(is_string($support[0]));
        $this->assertTrue(is_string($support[1]));
    }

    public function testRegisterExtensionProcessing()
    {
        $this->sanitizer->enableComponentForTesting('youtube');
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('youtube'));

        $expected1 = '<div class="embed-responsive embed-responsive-16by9">';
        $expected2 = '<iframe class="embed-responsive-item" width="180" height="100" src="https://www.youtube.com/embed/12345678901" frameborder="0" allowfullscreen></iframe>';

        $in = '[youtube=180,100]12345678901[/youtube]';
        $actual = $this->sanitizer->filterForDisplay($in);
        $this->assertStringStartsWith($expected1, $actual);
        $this->assertStringContainsString($expected2, $actual);

        $in = '[youtube url="12345678901" width="180" height=100 /]';
        $actual = $this->sanitizer->filterForDisplay($in);
        $this->assertStringStartsWith($expected1, $actual);
        $this->assertStringContainsString($expected2, $actual);
    }
}
