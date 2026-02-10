<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class SimpleFormTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SimpleForm
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new SimpleForm('Caption', 'name', 'action');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testInsertBreak()
    {
        $this->object->insertBreak();
        $value = $this->object->render();
        $this->assertTrue(false !== strpos($value, '<br />'));

        $this->object->insertBreak('extra','class');
        $value = $this->object->render();
        $this->assertTrue(false !== strpos($value, '<br class="class" />extra'));
    }

    public function testRender()
    {
        $value = $this->object->render();
        $this->assertTrue(is_string($value));
        $this->assertTrue(false !== strpos($value, '<form'));
        $this->assertTrue(false !== strpos($value, 'name="name"'));
        $this->assertTrue(false !== strpos($value, 'id="name"'));
        $this->assertTrue(false !== strpos($value, 'action="action"'));
        $this->assertTrue(false !== strpos($value, 'method="post"'));
        $this->assertTrue(false !== strpos($value, '</form>'));
    }
}
