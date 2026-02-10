<?php
namespace Xmf\Test\Key;

use Xmf\Key\ArrayStorage;
use Xmf\Key\Basic;
use Xmf\Key\StorageInterface;

class BasicTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var Basic
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->storage = new ArrayStorage();
        $this->object = new Basic($this->storage, 'test');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetSigning()
    {
        $actual = $this->object->getSigning();
        $this->assertEmpty($actual);
        $actual = $this->object->create();
        $this->assertTrue($actual);
        $actual = $this->object->getSigning();
        $this->assertTrue(is_string($actual));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{128}$/', $actual);
    }

    public function testGetVerifying()
    {
        $actual = $this->object->getVerifying();
        $this->assertEmpty($actual);
        $actual = $this->object->create();
        $this->assertTrue($actual);
        $actual = $this->object->getVerifying();
        $this->assertTrue(is_string($actual));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{128}$/', $actual);
    }

    public function testCreate()
    {
        $actual = $this->object->create();
        $this->assertTrue($actual);

        $actual = $this->object->create();
        $this->assertFalse($actual);
    }

    public function testKill()
    {
        $actual = $this->object->create();
        $this->assertTrue($actual);

        $this->assertTrue($this->storage->exists('test'));

        $actual = $this->object->kill();
        $this->assertTrue($actual);

        $this->assertFalse($this->storage->exists('test'));
    }
}
