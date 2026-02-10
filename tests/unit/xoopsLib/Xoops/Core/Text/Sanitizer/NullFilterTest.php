<?php
namespace Xoops\Core\Text\Sanitizer;

use Xoops\Core\Text\Sanitizer;

require_once __DIR__.'/../../../../../init_new.php';

class NullFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NullFilter
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
        $this->object = new NullFilter($this->sanitizer);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testApplyFilter()
    {
        $text = 'Why does my cat sleep so much?';
        $expected = $text;
        $actual = $this->sanitizer->executeFilter('nosuchfilter', $text);
        $this->assertEquals($expected, $actual);
    }
}
