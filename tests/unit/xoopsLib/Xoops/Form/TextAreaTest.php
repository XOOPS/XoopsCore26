<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class TextAreaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TextArea
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new TextArea('Caption', 'name', 'value', 5, 10, 'placeholder');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetRows()
    {
        $value = $this->object->getRows();
        $this->assertSame(5, $value);
    }

    public function testGetCols()
    {
        $value = $this->object->getCols();
        $this->assertSame(10, $value);
    }

    public function testGetPlaceholder()
    {
        $value = $this->object->getPlaceholder();
        $this->assertSame('placeholder', $value);
    }

    public function testRender()
    {
        $value = $this->object->render();
        $this->assertTrue(is_string($value));
        $this->assertTrue(false !== strpos($value, '<textarea'));
        $this->assertTrue(false !== strpos($value, 'name="name"'));
        $this->assertTrue(false !== strpos($value, 'rows="5"'));
        $this->assertTrue(false !== strpos($value, 'cols="10"'));
        $this->assertTrue(false !== strpos($value, 'placeholder="placeholder"'));
        $this->assertTrue(false !== strpos($value, 'title="Caption"'));
        $this->assertTrue(false !== strpos($value, 'id="name"'));
        $this->assertTrue(false !== strpos($value, '>value<'));
    }

    public function test__construct()
    {
        $oldWay = new TextArea('mycaption', 'myname', 'myvalue');
        $newWay = new TextArea(['caption' => 'mycaption', 'name' => 'myname', 'value' => 'myvalue',]);
        $this->assertEquals($oldWay->render(), $newWay->render());
    }
}
