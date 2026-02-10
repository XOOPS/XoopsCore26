<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class ColorPickerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ColorPicker
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new ColorPicker('Caption', 'name');
        \Xoops::getInstance()->setTheme(new \Xoops\Core\Theme\NullTheme);
        //$this->markTestSkipped('side effects');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testRender()
    {
        $level = ob_get_level();
        $value = $this->object->render();
        while (ob_get_level() > $level) ob_end_flush();
        $this->assertTrue(is_string($value));
    }

    public function testRenderValidationJS()
    {
        $value = $this->object->renderValidationJS();
        $this->assertTrue(is_string($value));
    }

    public function test__construct()
    {
        $oldWay = new ColorPicker('mycaption', 'myname');
        $newWay = new ColorPicker(['caption' => 'mycaption', 'type' => 'text', 'name' => 'myname',]);

        $this->assertEquals(substr($oldWay->render(),0,18), substr($newWay->render(),0,18));
        $this->assertEquals(substr($oldWay->render(),-40), substr($newWay->render(),-40));
        $this->assertEquals(strlen($oldWay->render()), strlen($newWay->render()));
    }
}
