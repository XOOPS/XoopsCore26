<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class ContainerInterfaceTest extends \PHPUnit\Framework\TestCase
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
        if (method_exists($this, 'createMock')) {
            $this->object = $this->createMock('\Xoops\Form\ContainerInterface');
        } else { // need phpunit 4.8 for PHP 5.5
            $this->object = $this->getMock('\Xoops\Form\ContainerInterface');
        }
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
        $element = new Raw('For Testing');
        $this->assertNull($this->object->addElement(new Raw('For Testing')));
        $this->assertNull($this->object->getRequired());
        $this->assertNull($this->object->getElements(false));
    }
}
