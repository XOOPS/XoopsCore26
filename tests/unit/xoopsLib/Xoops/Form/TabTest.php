<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class TabTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Tab
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Tab('Caption', 'name');
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
        $button = new Button('Caption', 'name');
        $this->object->addElement($button);
        $value = $this->object->render();
        $this->assertTrue(is_string($value));
        $this->assertTrue(false !== strpos($value, '<div class="form-group">'));
        $this->assertTrue(false !== strpos($value, '<label>Caption</label>'));
        //$this->assertTrue(false !== strpos($value, 'class="btn btn-default"'));
        $this->assertTrue(false !== strpos($value, '<input'));
        $this->assertTrue(false !== strpos($value, 'type="button"'));
        $this->assertTrue(false !== strpos($value, 'title="Caption"'));
        $this->assertTrue(false !== strpos($value, 'id="name"'));
    }
}
