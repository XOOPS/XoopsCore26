<?php
namespace Xmf\Key;

class FileStorageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FileStorage
     */
    protected $object;

    /**
     * @var string
     */
    protected $testKey = 'x-unit-test-key-file';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        //$this->markTestIncomplete('FileStorage testing incomplete');
        $this->object = new FileStorage('/tmp', 'fubar');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        @$this->object->delete($this->testKey);
    }

    public function testSave()
    {
        $name = $this->testKey;
        $data = 'data';
        $this->object->save($name, $data);
        $this->assertEquals($data, $this->object->fetch($name));
    }

    public function testFetch()
    {
        $name = $this->testKey;
        $data = 'data';
        $this->assertFalse(@$this->object->fetch($name));
        $this->object->save($name, $data);
        $this->assertEquals($this->object->fetch($name), $data);
    }

    public function testExists()
    {
        $name = $this->testKey;
        $data = 'data';
        $this->assertFalse($this->object->exists($name));
        $this->object->save($name, $data);
        $this->assertTrue($this->object->exists($name));
    }

    public function testDelete()
    {
        $name = $this->testKey;
        $data = 'data';
        $this->object->save($name, $data);
        $this->assertTrue($this->object->exists($name));
        $this->assertTrue($this->object->delete($name));
        $this->assertFalse(@$this->object->delete($name));
        $this->assertFalse($this->object->exists($name));
    }
}
