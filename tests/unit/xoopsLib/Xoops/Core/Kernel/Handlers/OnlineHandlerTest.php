<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsOnlineHandler;

class OnlineHandlerTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myclass='Xoops\Core\Kernel\Handlers\XoopsOnlineHandler';
    protected $myId = null;
    protected $conn = null;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->conn = Xoops::getInstance()->db();
    }

    public function test___construct()
    {
        $instance=new $this->myclass($this->conn);
        $this->assertMatchesRegularExpression('/^.*system_online$/', $instance->table);
        $this->assertSame('\Xoops\Core\Kernel\Handlers\XoopsOnline', $instance->className);
        $this->assertSame('online_uid', $instance->keyName);
        $this->assertSame('online_uname', $instance->identifierName);
    }

    public function testContracts()
    {
        $instance=new $this->myclass($this->conn);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsOnlineHandler', $instance);
        $this->assertInstanceOf('\Xoops\Core\Kernel\XoopsPersistableObjectHandler', $instance);
    }

    public function test_write()
    {
        $instance=new $this->myclass($this->conn);
        $this->assertInstanceOf($this->myclass, $instance);

        $this->myId = (int)(microtime(true)%10000000);
        $value = $instance->write($this->myId, 'name', time(), 0, '127.0.0.1');
        $this->assertSame(true, $value);
    }

    public function test_destroy()
    {
        $instance=new $this->myclass($this->conn);
        $this->assertInstanceOf($this->myclass, $instance);

        $value = $instance->destroy($this->myId);
        $this->assertSame(true, $value);
    }

    public function test_gc()
    {
        $instance=new $this->myclass($this->conn);
        $this->assertInstanceOf($this->myclass, $instance);

        $value = $instance->gc(time()+10);
        $this->assertSame(true, $value);
    }
}
