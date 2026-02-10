<?php
namespace Xoops\Core\Text\Sanitizer\Extensions;

use Xoops\Core\Text\Sanitizer;

require_once __DIR__.'/../../../../../../init_new.php';

class Mp3Test extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Mp3
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
        $this->object = new Mp3($this->sanitizer);
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
        $this->sanitizer->enableComponentForTesting('mp3');
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('mp3'));
        $expected = '<audio controls><source src="http://spot.river-styx.com/media/spot6.mp3" type="audio/mpeg"></audio>';

        $in = '[mp3]http://spot.river-styx.com/media/spot6.mp3[/mp3]';
        $actual = $this->sanitizer->filterForDisplay($in);
        $this->assertEquals($expected, $actual);

        $in = '[mp3 url="http://spot.river-styx.com/media/spot6.mp3" /]';
        $actual = $this->sanitizer->filterForDisplay($in);
        $this->assertEquals($expected, $actual);
    }
}
