<?php
namespace Xoops\Core\Lists;

require_once __DIR__ . '/../../../../init_new.php';

class SubSetTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    protected $className = '\Xoops\Core\Lists\SubSet';

    /**
     * @var SubSet
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new SubSet;
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
        $this->assertInstanceOf('\Xoops\Core\Lists\SubSet', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Lists\ListAbstract', $this->object);
    }

    /**
     * @todo   Implement testGetList().
     */
    public function testGetList()
    {
        $reflection = new \ReflectionClass($this->className);
        $method = $reflection->getMethod('getList');
        $this->assertTrue($method->isStatic());
    }
}
