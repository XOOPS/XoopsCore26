<?php
namespace Xoops\Html\Menu;

require_once(__DIR__.'/../../../../init_new.php');

class DividerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Divider
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Divider;
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
        $this->assertInstanceOf('\Xoops\Html\Menu\Divider', $this->object);
        $this->assertInstanceOf('\Xoops\Html\Menu\Item', $this->object);
    }
}
