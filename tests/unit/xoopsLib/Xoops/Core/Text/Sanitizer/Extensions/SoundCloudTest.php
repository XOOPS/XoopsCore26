<?php
namespace Xoops\Core\Text\Sanitizer\Extensions;

use Xoops\Core\Text\Sanitizer;

require_once __DIR__.'/../../../../../../init_new.php';

class SoundCloudTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SoundCloud
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
        $this->object = new SoundCloud($this->sanitizer);
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
        $this->sanitizer->enableComponentForTesting('soundcloud');
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('soundcloud'));
        $expected = '<iframe width="100%" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https://api.soundcloud.com/tracks/80365438&amp;color=ff5500&amp;auto_play=false&amp;hide_related=false&amp;show_comments=true&amp;show_user=true&amp;show_reposts=false"></iframe>';

        $in = '[soundcloud url="https://api.soundcloud.com/tracks/80365438" params="color=ff5500&auto_play=false&hide_related=false&show_comments=true&show_user=true&show_reposts=false" width="100%" height="166" iframe="true" /]';
        $actual = $this->sanitizer->filterForDisplay($in);
        $this->assertEquals($expected, $actual);
    }
}
