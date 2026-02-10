<?php
namespace Xoops\Core\Handler;

use Xoops\Core\Database\Connection;
use Xoops\Core\Exception\InvalidHandlerSpecException;
use Xoops\Core\Exception\NoHandlerException;

require_once __DIR__ . '/../../../../init_new.php';

class FactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Factory
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = Factory::getInstance();
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
        $instance = Factory::getInstance();
        $this->assertSame($this->object, $instance, 'Singleton');
    }

    public function testCreate()
    {
        $handler = $this->object->create('user');
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsUserHandler', $handler);
        $handler2 = $this->object->create('user');
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsUserHandler', $handler2);
        $this->assertNotSame($handler, $handler2);

        $handler = $this->object->create('\Xoops\Core\Kernel\Handlers\XoopsUserHandler');
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsUserHandler', $handler);
        $this->assertNotSame($handler, $handler2);

        $handler = $this->object->create('avatar', 'avatars');
        $this->assertInstanceOf('\AvatarsAvatarHandler', $handler);


    }

    public function testRegisterScheme()
    {
        $this->object->registerScheme('testscheme', '\Xoops\Core\Handler\Scheme\FQN');
        $handler = $this->object->create('testscheme:testname', null, true);
        $this->assertNull($handler);

        $this->expectException('\Xoops\Core\Exception\InvalidHandlerSpecException');
        $this->object->create('testscheme2:testname', null, true);
    }

    public function testNewSpec()
    {
        $instance = $this->object->newSpec();
        $instance2 = $this->object->newSpec();
        $this->assertInstanceOf('\Xoops\Core\Handler\FactorySpec', $instance);
        $this->assertInstanceOf('\Xoops\Core\Handler\FactorySpec', $instance2);

        $this->assertNotSame($instance, $instance2);
    }

    public function testNewSpec_static()
    {
        $instance = Factory::newSpec();
        $instance2 = Factory::newSpec();
        $this->assertInstanceOf('\Xoops\Core\Handler\FactorySpec', $instance);
        $this->assertInstanceOf('\Xoops\Core\Handler\FactorySpec', $instance2);

        $this->assertNotSame($instance, $instance2);
        $this->assertSame($instance->getFactory(), $instance2->getFactory());
    }

    public function testBuild()
    {
        $spec = $this->object->newSpec()->scheme('kernel')->name('user');
        $handler = $this->object->build($spec);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsUserHandler', $handler);
    }

    public function testBuild_exception()
    {
        $spec = $this->object->newSpec()->scheme('nosuchscheme');
        $this->expectException('\Xoops\Core\Exception\InvalidHandlerSpecException');
        $handler = $this->object->build($spec);
    }

    public function testBuild_optional()
    {
        $spec = $this->object->newSpec()->scheme('kernel')->name('nosuchhandler')->optional(true);
        $handler = $this->object->build($spec);
        $this->assertNull($handler);
        $spec = $this->object->newSpec()->scheme('kernel')->name('user')->optional(false);
        $handler = $this->object->build($spec);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsUserHandler', $handler);
    }

    public function testDb()
    {
        $this->assertInstanceOf('Xoops\Core\Database\Connection', $this->object->db());
    }
}
