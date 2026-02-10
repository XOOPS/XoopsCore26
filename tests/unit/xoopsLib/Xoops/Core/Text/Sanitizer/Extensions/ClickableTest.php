<?php
namespace Xoops\Core\Text\Sanitizer\Extensions;

use Xoops\Core\Text\Sanitizer;

require_once __DIR__.'/../../../../../../init_new.php';

class ClickableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Clickable
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
        $this->object = new Clickable($this->sanitizer);
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
        $this->assertInstanceOf('\Xoops\Core\Text\Sanitizer\FilterAbstract', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Text\Sanitizer\SanitizerComponent', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Text\Sanitizer\SanitizerConfigurable', $this->object);
    }

    public function testApplyFilter()
    {
        $this->sanitizer->enableComponentForTesting('clickable');

        $in = 'http://xoops.org';
        $expected = '<a href="http://xoops.org" title="http://xoops.org"rel="external">http://xoops.org</a>';
        $actual = $this->sanitizer->executeFilter('clickable', $in);
        $this->assertEquals($expected, $actual);

        $in = 'fred@example.com';
        $expected = '<a href="mailto:fred@example.com" title="fred@example.com">fred@example.com</a>';
        $actual = $this->sanitizer->executeFilter('clickable', $in);
        $this->assertEquals($expected, $actual);
    }
}
