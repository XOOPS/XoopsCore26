<?php
require_once(__DIR__.'/../../../init_new.php');

use Xoops\Core\Registry;

class RegistryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Registry
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Registry();
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
        $instance = $this->object;

        $this->assertInstanceOf('ArrayObject', $instance);
        $this->assertInstanceOf('\Xoops\Core\AttributeInterface', $instance);
        $this->assertInstanceOf('\Xoops\Core\XoopsArray', $instance);
    }

    public function test_get()
    {
        $instance = $this->object;

        $testkey = 'testkey';
        $testvalue = 'testvalue';
        $this->object->remove($testkey);
        $value = $this->object->get($testkey);
        $this->assertNull($value);

        $value = $this->object->get($testkey, $testvalue);
        $this->assertSame($testvalue, $value);

        $value = $instance->remove($testkey);
        $this->assertNull($value);
    }

    public function test_has()
    {
        $instance = $this->object;

        $testkey = 'testkey';
        $testvalue = 'testvalue';

        $value = $instance->has($testkey);
        $this->assertFalse($value);

        $instance->set($testkey, $testvalue);
        $value = $instance->has($testkey);
        $this->assertTrue($value);
    }

    public function test_remove()
    {
        $instance = $this->object;

        $testkey = 'testkey';
        $testvalue = 'testvalue';
        $instance->set($testkey, $testvalue);
        $value = $instance->get($testkey);
        $this->assertSame($testvalue, $value);

        $result = $instance->remove('name_doesnt_exist');
        $this->assertSame(null, $result);

        $result = $instance->remove($testkey);
        $this->assertSame($testvalue, $result);
    }

    public function test_clear()
    {
        $instance = $this->object;

        $testkey = 'testkey';
        $testvalue = 'testvalue';
        $instance->set($testkey, $testvalue);

        $result = $instance->clear();

        $value = $instance->get($testkey);
        $this->assertSame(null, $value);
    }
}
