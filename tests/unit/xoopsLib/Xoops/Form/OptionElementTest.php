<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

use Xoops\Form\OptionElement;

class OptionElementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Element
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = $this->getMockBuilder('Xoops\Form\OptionElement')
            ->onlyMethods(['defaultRender'])
            ->getMock();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testContracts()
    {
        $this->assertTrue(is_subclass_of('Xoops\Form\OptionElement', 'Xoops\Html\Attributes'));
        $this->assertTrue(is_subclass_of('Xoops\Form\OptionElement', 'Xoops\Form\Element'));
        $this->assertTrue(method_exists($this->object, 'addOption'));
        $this->assertTrue(method_exists($this->object, 'addOptionArray'));
        $this->assertTrue(method_exists($this->object, 'getOptions'));
    }

    public function testAddOption()
    {
        $this->object->addOption('key1', 'value1');
        $this->object->addOption('key2');
        $options = $this->object->getOptions();
        $this->assertArrayHasKey('key1', $options);
        $this->assertArrayHasKey('key2', $options);
    }

    public function testAddOptionArray()
    {
        $this->object->addOptionArray([
            'key1' => 'value1',
            'key2' => 'value2'
        ]);
        $options = $this->object->getOptions();
        $this->assertArrayHasKey('key1', $options);
        $this->assertArrayHasKey('key2', $options);

        $options = $this->object->getOptions(2);
        $this->assertArrayHasKey('key1', $options);
        $this->assertArrayHasKey('key2', $options);
    }
}
