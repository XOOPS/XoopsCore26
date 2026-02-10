<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

use Xoops\Form\Form;
use Xoops\Form\Button;

class FormTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Form
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = $this->getMockBuilder('\Xoops\Form\Form')->setConstructorArgs(array('title', 'name', 'action'))->onlyMethods(['render'])->getMock();

    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetDisplay()
    {
        $value = $this->object->getDisplay();
        $this->assertSame('', $value);
    }

    public function testGetTitle()
    {
        $value = $this->object->getTitle();
        $this->assertSame('title', $value);
    }

    public function testSetTitle()
    {
        $name = 'form_name';
        $this->object->setTitle($name);
        $value = $this->object->getTitle();
        $this->assertSame($name, $value);
    }

    public function testGetName()
    {
        $value = $this->object->getName();
        $this->assertSame('name', $value);
    }

    public function testGetAction()
    {
        $name = 'form_name';
        $this->object->setAction($name);
        $value = $this->object->getAction();
        $this->assertSame($name, $value);
    }

    public function testGetMethod()
    {
        $value = $this->object->getMethod();
        $this->assertSame('post', $value);
    }

    public function testGetElements()
    {
        $instance = $this->object;

        $button = new Button('button_caption', 'button_name');
        $instance->addElement($button, true);
        $value = $instance->getElements();
        $this->assertTrue(is_array($value));
        $this->assertInstanceOf('\Xoops\Form\Button', $value[0]);

        $value = $instance->getElementNames();
        $this->assertTrue(is_array($value));
        $this->assertSame('button_name', $value[0]);

        $value = $instance->getElementByName('button_name');
        $this->assertInstanceOf('\Xoops\Form\Button', $value);

        $value = $instance->getElementByName('button_doesnt_exist');
        $this->assertSame(null, $value);
    }

    public function testGetElementValue()
    {
        $instance = $this->object;

        $name = 'button_name';
        $button = new Button('button_caption', $name);
        $instance->addElement($button, true);
        $value = $instance->getElements();
        $this->assertTrue(is_array($value));
        $this->assertInstanceOf('\Xoops\Form\Button', $value[0]);

        $value = 'value';
        $instance->setElementValue($name, $value);

        $result = $instance->getElementValue($name);
        $this->assertSame($value, $result);

    }

    public function testGetElementValues()
    {
        $instance = $this->object;

        $name = 'button_name';
        $button = new Button('button_caption', $name);
        $instance->addElement($button, true);
        $value = $instance->getElements();
        $this->assertTrue(is_array($value));
        $this->assertInstanceOf('\Xoops\Form\Button', $value[0]);

        $arrAttr = array($name=>'value1', 'key2'=>'value2');
        $instance->setElementValues($arrAttr);

        $result = $instance->getElementValues();
        $this->assertSame('value1', $result[$name]);
    }

    public function testGetExtra()
    {
        $name = 'form_name';
        $this->object->setExtra($name);
        $value = $this->object->getExtra();
        $this->assertSame(' '.$name, $value);
    }

    public function testGetRequired()
    {
        $button = new Button(['caption' => 'button_caption', 'name' => 'button_name', 'required' => true]);
        $this->object->addElement($button);
        $value = $this->object->getRequired();
        $this->assertTrue(is_array($value));
        $this->assertInstanceOf('\Xoops\Form\Button', $value[0]);
        $this->assertSame($button, $value[0]);
    }

    public function testGetRequired2()
    {
        $button = new Button(['caption' => 'button_caption', 'name' => 'button_name']);
        $this->object->addElement($button, true);
        $value = $this->object->getRequired();
        $this->assertTrue(is_array($value));
        $this->assertInstanceOf('\Xoops\Form\Button', $value[0]);
        $this->assertSame($button, $value[0]);
        $this->assertTrue($button->has('required'));
    }

    public function testDisplay()
    {
        $instance = $this->object;
        ob_start();
        $instance->display();
        $result = ob_get_clean();
        $this->assertSame('', $result);
    }

    public function testRenderValidationJS()
    {
        $value = $this->object->renderValidationJS();
        $this->assertTrue(is_string($value));
        $this->assertTrue(false !== strpos($value,'Start Form Validation JavaScript'));
    }

    public function testAssign()
    {
        // Remove the following lines when you implement this test.
        $this->markTestSkipped(
            'Needs XoopsTpl::assign()'
        );
    }
}
