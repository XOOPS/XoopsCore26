<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class ButtonTrayTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ButtonTray
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new ButtonTray('name');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetType()
    {
        $value = $this->object->getType();
        $this->assertSame('submit',$value);
    }

    public function testRender()
    {
        $value = $this->object->render();
        $this->assertTrue(is_string($value));
        $this->assertTrue(false !== strpos($value, '<input type="button"'));

        $object = new ButtonTray('name2','','','',true);
        $value = $object->render();
        $this->assertTrue(is_string($value));
        $this->assertTrue(false !== strpos($value, '<input type="submit"'));
        $this->assertTrue(false !== strpos($value, '<input type="button"'));
    }

    public function test__construct()
    {
        $oldWay = new ButtonTray('myname', 'myvalue', 'button');
        $newWay = new ButtonTray(['name' => 'myname', 'value' => 'myvalue', 'type' => 'button',]);
        $this->assertEquals($oldWay->render(), $newWay->render());
    }
}
