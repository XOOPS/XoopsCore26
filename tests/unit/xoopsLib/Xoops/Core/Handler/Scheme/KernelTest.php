<?php
namespace Xoops\Core\Handler\Scheme;

use PHPUnit\Framework\Attributes\DataProvider;
use Xoops\Core\Handler\Factory;
use Xoops\Core\Handler\Scheme\SchemeInterface;

require_once __DIR__ . '/../../../../../init_new.php';

class KernelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Kernel
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Kernel;
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
        $this->assertInstanceOf('\Xoops\Core\Handler\Scheme\SchemeInterface', $this->object);
    }

    /**
     * @param string $name that would be supplied to Xoops::getHandler()
     * @param string $handlerClass FQN of expected handler class
     */
    #[DataProvider('handlerValueProvider')]
    public function testBuild($name, $handlerClass)
    {
        $spec = Factory::getInstance()->newSpec()->scheme('kernel')->name($name);
        $this->assertInstanceOf($handlerClass, $this->object->build($spec));
    }

    public static function handlerValueProvider()
    {
        return array(
            ['block'          , '\Xoops\Core\Kernel\Handlers\XoopsBlockHandler'],
            ['blockmodulelink', '\Xoops\Core\Kernel\Handlers\XoopsBlockModuleLinkHandler'],
            ['config'         , '\Xoops\Core\Kernel\Handlers\XoopsConfigHandler'],
            ['configitem'     , '\Xoops\Core\Kernel\Handlers\XoopsConfigItemHandler'],
            ['configoption'   , '\Xoops\Core\Kernel\Handlers\XoopsConfigOptionHandler'],
            ['group'          , '\Xoops\Core\Kernel\Handlers\XoopsGroupHandler'],
            ['groupperm'      , '\Xoops\Core\Kernel\Handlers\XoopsGroupPermHandler'],
            ['member'         , '\Xoops\Core\Kernel\Handlers\XoopsMemberHandler'],
            ['membership'     , '\Xoops\Core\Kernel\Handlers\XoopsMembershipHandler'],
            ['module'         , '\Xoops\Core\Kernel\Handlers\XoopsModuleHandler'],
            ['online'         , '\Xoops\Core\Kernel\Handlers\XoopsOnlineHandler'],
            ['privmessage'    , '\Xoops\Core\Kernel\Handlers\XoopsPrivateMessageHandler'],
            ['tplfile'        , '\Xoops\Core\Kernel\Handlers\XoopsTplFileHandler'],
            ['tplset'         , '\Xoops\Core\Kernel\Handlers\XoopsTplSetHandler'],
            ['user'           , '\Xoops\Core\Kernel\Handlers\XoopsUserHandler'],
        );
    }

    public function testBuild_exception()
    {
        $this->expectException('\Xoops\Core\Exception\NoHandlerException');
        $handler = Factory::getInstance()->newSpec()->scheme('kernel')->name('nosuchhandler')->build();
    }

    public function testBuild_optional()
    {
        $handler = Factory::getInstance()->newSpec()->scheme('kernel')->name('nosuchhandler')->optional(true)->build();
        $this->assertNull($handler);
        $handler = Factory::getInstance()->newSpec()->scheme('kernel')->name('user')->optional(true)->build();
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsUserHandler', $handler);
    }
}
