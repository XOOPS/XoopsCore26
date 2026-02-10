<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class BlockFormTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BlockForm
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new BlockForm;
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
        $caption = 'button_caption';
        $name = 'button_name';
        $value = 'button_value';
        $button = new Button($caption, $name, $value);
        $this->object->addElement($button);

        $x = $this->object->render();
        $this->assertTrue(false !== strpos($x, '<div class="form-group">'));
        $this->assertTrue(false !== strpos($x, '<label>' . $caption .'</label>'));
        $this->assertTrue(false !== strpos($x, '<input type="button"'));
        $this->assertTrue(false !== strpos($x, 'name="' . $name .'"'));
        $this->assertTrue(false !== strpos($x, 'id="' . $name .'"'));
        $this->assertTrue(false !== strpos($x, 'title="' . $caption .'"'));
        $this->assertTrue(false !== strpos($x, 'value="' . $value .'"'));

        $button->setHidden();
        $x = $this->object->render();
        $this->assertTrue(false !== strpos($x, '<input type="button"'));
        $this->assertTrue(false !== strpos($x, ' hidden '));
    }
}
