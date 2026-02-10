<?php
namespace Xoops\Html\Menu;

require_once(__DIR__.'/../../../../init_new.php');

class ItemListTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ItemList
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new ItemList;
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
        $this->assertInstanceOf('\Xoops\Html\Menu\ItemList', $this->object);
        $this->assertInstanceOf('\Xoops\Html\Menu\Item', $this->object);
        $this->assertInstanceOf('\Xoops\Core\XoopsArray', $this->object);
    }

    public function testAddItem()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
