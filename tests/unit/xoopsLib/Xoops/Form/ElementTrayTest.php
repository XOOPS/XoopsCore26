<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class ElementTrayTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ElementTray
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new ElementTray('Caption');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testIsRequired()
    {
        $value = $this->object->isRequired();
        $this->assertFalse($value);

        $button = new Button('button_caption', 'button_name');
        $this->object->addElement($button, true);
        $value = $this->object->isRequired();
        $this->assertTrue($value);

        $value = $this->object->getRequired();
        $this->assertTrue(is_array($value));
        $this->assertInstanceOf('Xoops\Form\Button', $value[0]);

        $value = $this->object->getElements();
        $this->assertTrue(is_array($value));
        $this->assertInstanceOf('Xoops\Form\Button', $value[0]);
    }

    public function testRender()
    {
        $value = $this->object->render();
        $this->assertTrue(is_string($value));
    }
}
