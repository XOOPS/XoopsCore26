<?php
namespace Xoops\Core\Text\Sanitizer\Extensions;

use Xoops\Core\Text\Sanitizer;

require_once __DIR__.'/../../../../../../init_new.php';

class FlashTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Flash
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
        $this->object = new Flash($this->sanitizer);
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
        $this->sanitizer->enableComponentForTesting('flash');
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('flash'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('swf'));
        $expected = '<object type="application/x-shockwave-flash" data="http://spot.river-styx.com/media/hello.swf" width="300" height="200"></object>';

        $in = '[flash=300,200]http://spot.river-styx.com/media/hello.swf[/flash]';
        $actual = $this->sanitizer->filterForDisplay($in);
        $this->assertEquals($expected, $actual);

        $in = '[flash url="http://spot.river-styx.com/media/hello.swf" width="300" height=200 /]';
        $actual = $this->sanitizer->filterForDisplay($in);
        $this->assertEquals($expected, $actual);
    }
}
