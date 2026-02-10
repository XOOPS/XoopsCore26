<?php
namespace Xoops\Core\Lists;

require_once __DIR__ . '/../../../../init_new.php';

class CountryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    protected $className = '\Xoops\Core\Lists\Country';

    /**
     * @var Country
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Country;
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
        $this->assertInstanceOf('\Xoops\Core\Lists\Country', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Lists\ListAbstract', $this->object);
    }

    public function testGetList()
    {
        $reflection = new \ReflectionClass($this->className);
        $method = $reflection->getMethod('getList');
        $this->assertTrue($method->isStatic());
    }

    public function testSetOptionsArray()
    {
        $reflection = new \ReflectionClass($this->className);
        $method = $reflection->getMethod('setOptionsArray');
        $this->assertTrue($method->isStatic());
    }
}
