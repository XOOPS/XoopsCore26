<?php
namespace Xoops\Core\Handler;

require_once __DIR__ . '/../../../../init_new.php';

class FactorySpecTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FactorySpec
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $factory = Factory::getInstance();
        $this->object = FactorySpec::getInstance($factory);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetInstance()
    {
        $factory = Factory::getInstance();
        $instance = FactorySpec::getInstance($factory);
        $this->assertInstanceOf('\Xoops\Core\Handler\FactorySpec', $instance);
        $this->assertNotSame($this->object, $instance);
    }

    public function testScheme()
    {
        $instance = $this->object->scheme('testing');
        $this->assertInstanceOf('\Xoops\Core\Handler\FactorySpec', $instance);
        $this->assertEquals('testing', $this->object->getScheme());
    }

    public function testName()
    {
        $instance = $this->object->name('testing');
        $this->assertInstanceOf('\Xoops\Core\Handler\FactorySpec', $instance);
        $this->assertEquals('testing', $this->object->getName());
    }

    public function testDirname()
    {
        $instance = $this->object->dirname('testing');
        $this->assertInstanceOf('\Xoops\Core\Handler\FactorySpec', $instance);
        $this->assertEquals('testing', $this->object->getDirname());
    }

    public function testOptional()
    {
        $instance = $this->object->optional(true);
        $this->assertInstanceOf('\Xoops\Core\Handler\FactorySpec', $instance);
        $this->assertEquals(true, $this->object->getOptional());
    }

    public function testFqn()
    {
        $instance = $this->object->fqn('testing');
        $this->assertInstanceOf('\Xoops\Core\Handler\FactorySpec', $instance);
        $this->assertEquals('testing', $this->object->getFQN());
    }

    public function testBuild()
    {
        $name = '\Xoops\Core\Kernel\Handlers\XoopsUserHandler';
        $this->object->scheme('fqn')->name($name);
        $this->assertInstanceOf($name, $this->object->build($this->object));
    }

    public function testBuild_exception()
    {
        $name = '\Xoops\Core\Kernel\Handlers\NoSuchName';
        $this->object->scheme('fqn')->name($name);
        $this->expectException('\Xoops\Core\Exception\NoHandlerException');
        $this->object->build($this->object);
    }

    public function testBuild_optional()
    {
        $name = '\Xoops\Core\Kernel\Handlers\NoSuchName';
        $this->object->scheme('fqn')->name($name)->optional(true);
        $handler = $this->object->build($this->object);
        $this->assertNull($handler);

        $name = '\Xoops\Core\Kernel\Handlers\XoopsUserHandler';
        $this->object->scheme('fqn')->name($name)->optional(true);
        $handler = $this->object->build($this->object);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsUserHandler', $handler);
    }

    public function testGetFactory()
    {
        $factory = Factory::getInstance();
        $this->assertSame($factory, $this->object->getFactory());
    }
}
