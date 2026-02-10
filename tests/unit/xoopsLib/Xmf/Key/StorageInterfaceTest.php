<?php
namespace Xmf\Test\Key;

use Xmf\Key\StorageInterface;

class StorageInterfaceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StorageInterface
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = $this->createMock('\Xmf\Key\StorageInterface');
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
        $this->assertInstanceOf('\Xmf\Key\StorageInterface', $this->object);
        $this->assertTrue(method_exists($this->object, 'save'));
        $this->assertTrue(method_exists($this->object, 'fetch'));
        $this->assertTrue(method_exists($this->object, 'exists'));
        $this->assertTrue(method_exists($this->object, 'delete'));
    }
}
