<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class ButtonTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Button
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Button('button_caption', 'button_name', 'button_value');
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
        $value = $this->object->render();
        $this->assertTrue(false !== strpos($value, '<input'));
        $this->assertTrue(false !== strpos($value, 'type="button"'));
        $this->assertTrue(false !== strpos($value, 'name="button_name"'));
        $this->assertTrue(false !== strpos($value, 'id="button_name"'));
        $this->assertTrue(false !== strpos($value, 'title="button_caption"'));
        $this->assertTrue(false !== strpos($value, 'value="button_value"'));
    }

    public function test__construct()
    {
        $oldWay = new Button('mycaption', 'myname', 'myvalue', 'button');
        $newWay = new Button(
            ['caption' => 'mycaption',
            'type' => 'button',
            'name' => 'myname',
            'value' => 'myvalue',]
        );
        $this->assertEquals($oldWay->render(), $newWay->render());
        //$this->assertNotFalse($oldWay->hasClassLike('btn'));
    }
}
