<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class SelectEditorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SelectEditor
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new SelectEditor(new BlockForm);
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
        $this->assertTrue(is_string($value));
        $this->assertTrue(false !== strpos($value, '<select'));
        $this->assertTrue(false !== strpos($value, 'name="editor"'));
        $this->assertTrue(false !== strpos($value, 'size="1"'));
        $this->assertTrue(false !== strpos($value, 'id="editor"'));

        $this->assertTrue(false !== strpos($value, '<option'));
        $this->assertTrue(false !== strpos($value, 'value="textarea"'));
        $this->assertTrue(false !== strpos($value, 'value="dhtmltextarea"'));
//      $this->assertTrue(false !== strpos($value, 'value="tinymce"'));
        $this->assertTrue(false !== strpos($value, 'value="tinymce4"'));
        $this->assertTrue(false !== strpos($value, '</option>'));

        $this->assertTrue(false !== strpos($value, '</select>'));
    }
}
