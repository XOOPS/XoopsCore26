<?php
namespace Xmf\Test\Key;

use Xmf\Key\ArrayStorage;
use Xmf\Key\KeyAbstract;
use Xmf\Key\StorageInterface;

class KeyAbstractTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var KeyAbstract
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->storage = new ArrayStorage();
        $this->object = $this->getMockBuilder('Xmf\Key\KeyAbstract')->setConstructorArgs(array($this->storage, 'test'))->onlyMethods(['getSigning', 'getVerifying', 'create', 'kill'])->getMock();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testConstruct()
    {
        $this->assertInstanceOf('\Xmf\Key\KeyAbstract', $this->object);

        $class = new \ReflectionClass('\Xmf\Key\KeyAbstract');
        $method = $class->getMethod('__construct');
        $this->assertFalse($method->isAbstract());
    }

    public function testMethodsExist()
    {
        $this->assertTrue(method_exists($this->object, 'getSigning'));
        $this->assertTrue(method_exists($this->object, 'getVerifying'));
        $this->assertTrue(method_exists($this->object, 'create'));
        $this->assertTrue(method_exists($this->object, 'kill'));
    }
}
