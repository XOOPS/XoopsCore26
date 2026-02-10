<?php
namespace Xoops\Html\Menu\Render;

require_once(__DIR__.'/../../../../../init_new.php');

class DropDownButtonTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DropDownButton
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new DropDownButton;
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
        $this->assertInstanceOf('\Xoops\Html\Menu\Render\DropDownButton', $this->object);
        $this->assertInstanceOf('\Xoops\Html\Menu\Render\RenderAbstract', $this->object);
    }

    public function testRender()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
