<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class EditorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Editor
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Editor('Caption', 'name', array('name' => 'name'));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testRenderValidationJS()
    {
        $value = $this->object->renderValidationJS();
        $this->assertFalse($value);
    }

    public function testRender()
    {
        $value = $this->object->render();
        $this->assertTrue(is_string($value));
        $this->assertTrue(false !== strpos($value,'<textarea'));
    }
}
