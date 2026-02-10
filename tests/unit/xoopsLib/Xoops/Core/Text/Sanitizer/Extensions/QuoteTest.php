<?php
namespace Xoops\Core\Text\Sanitizer\Extensions;

use Xoops\Core\Text\Sanitizer;

require_once __DIR__.'/../../../../../../init_new.php';

class QuoteTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Quote
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
        $this->object = new Quote($this->sanitizer);
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
        $this->sanitizer->enableComponentForTesting('quote');

        $in = '[quote]stuff[/quote]';
        $expected = 'Quote:<div class="xoopsQuote"><blockquote>stuff</blockquote></div>';
        $actual = $this->sanitizer->executeFilter('quote', $in);
        $this->assertEquals($expected, $actual);
        //var_dump($actual);
    }
}
