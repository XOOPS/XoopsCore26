<?php
namespace Xoops\Core\Text\Sanitizer\Extensions;

use Xoops\Core\Text\Sanitizer;

require_once __DIR__.'/../../../../../../init_new.php';

class UnorderedListTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UnorderedList
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
        $this->object = new UnorderedList($this->sanitizer);
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
        $this->sanitizer->enableComponentForTesting('unorderedlist');
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('ul'));
        $this->assertTrue($this->sanitizer->getShortCodes()->hasShortcode('li'));

        $in = '[ul][li]item[/li][/ul]';
        $expected = '<ul><li>item</li></ul>';
        $actual = $this->sanitizer->filterForDisplay($in);
        $this->assertEquals($expected, $actual);
    }
}
