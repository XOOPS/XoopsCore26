<?php
namespace Xoops\Core\Lists;

require_once __DIR__ . '/../../../../init_new.php';

class ListAbstractTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    protected $className = '\Xoops\Core\Lists\ListAbstract';

    /**
     * @var ListAbstract
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = $this->getMockBuilder('\Xoops\Core\Lists\ListAbstract')
            ->onlyMethods([])
            ->getMock();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetList()
    {
        $reflection = new \ReflectionClass($this->className);
        $this->assertTrue($reflection->hasMethod('getList'));
        $method = $reflection->getMethod('getList');
        $this->assertTrue($method->isStatic());

        $this->assertSame($this->object::getList(), []);
    }

    public function testSetOptionsArray()
    {
        $reflection = new \ReflectionClass($this->className);
        $this->assertTrue($reflection->hasMethod('setOptionsArray'));
        $method = $reflection->getMethod('setOptionsArray');
        $this->assertTrue($method->isStatic());
    }
}
