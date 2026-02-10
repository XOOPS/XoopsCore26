<?php
namespace Xmf\Test\Key;

use Xmf\Key\ArrayStorage;

class ArrayStorageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ArrayStorage
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new ArrayStorage;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testSave()
    {
        $name = 'name';
        $data = 'data';
        $this->object->save($name, $data);
        $this->assertEquals($data, $this->object[$name]);
    }

    public function testFetch()
    {
        $name = 'name';
        $data = 'data';
        $this->assertFalse($this->object->fetch($name));
        $this->object->save($name, $data);
        $this->assertEquals($this->object->fetch($name), $data);
    }

    public function testExists()
    {
        $name = 'name';
        $data = 'data';
        $this->assertFalse($this->object->exists($name));
        $this->object->save($name, $data);
        $this->assertTrue($this->object->exists($name));
    }

    public function testDelete()
    {
        $name = 'name';
        $data = 'data';
        $this->object->save($name, $data);
        $this->assertTrue($this->object->exists($name));
        $actual = $this->object->delete($name);
        $this->assertTrue($actual);
        $actual = $this->object->delete($name);
        $this->assertFalse($actual);
        $this->assertFalse($this->object->exists($name));
    }
}
