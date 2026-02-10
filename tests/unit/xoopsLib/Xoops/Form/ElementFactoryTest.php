<?php
namespace Xoops\Form;

require_once(__DIR__.'/../../../init_new.php');

class ElementFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ElementFactory
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new ElementFactory;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }


    public function testConst()
    {
        $this->assertTrue(defined('\Xoops\Form\ElementFactory::CLASS_KEY'));
        $this->assertTrue(defined('\Xoops\Form\ElementFactory::FORM_KEY'));
    }

    public function testCreate()
    {
        $spec = [
            ElementFactory::CLASS_KEY => 'Raw',
            'value' => 'myvalue',
        ];
        $actual = $this->object->create($spec);
        $this->assertInstanceOf('\Xoops\Form\Raw', $actual);
    }

    public function testValidateException1()
    {
        $this->expectException('\DomainException');
        $value = $this->object->create([]);
    }

    public function testValidateException2()
    {
        $spec = [
            ElementFactory::CLASS_KEY => 'NoSuchClassExists',
        ];
        $this->expectException('\DomainException');
        $value = $this->object->create($spec);
    }

    public function testValidateException3()
    {
        $spec = [
            ElementFactory::CLASS_KEY => '\ArrayObject',
        ];
        $this->expectException('\DomainException');
        $value = $this->object->create($spec);
    }

    public function testSetContainer()
    {
        $container = new ElementTray([]);
        $this->object->setContainer($container);

        $spec = new \ArrayObject([
            ElementFactory::CLASS_KEY => 'Raw',
            'value' => 'myvalue',
        ]);
        $actual = $this->object->create($spec);
        $this->assertInstanceOf('\Xoops\Form\Raw', $actual);
        $this->assertArrayHasKey(ElementFactory::FORM_KEY, $spec);
        $this->assertSame($container, $spec[ElementFactory::FORM_KEY]);
    }
}
