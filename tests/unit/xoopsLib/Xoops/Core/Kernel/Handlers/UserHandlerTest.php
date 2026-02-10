<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsUserHandler;

class UserHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass='Xoops\Core\Kernel\Handlers\XoopsUserHandler';
    protected $conn = null;

    public function setUp(): void
    {
        $this->conn = Xoops::getInstance()->db();
    }

    public function test___construct()
    {
        $instance=new $this->myclass($this->conn);
        $this->assertMatchesRegularExpression('/^.*system_user$/', $instance->table);
        $this->assertSame('\Xoops\Core\Kernel\Handlers\XoopsUser', $instance->className);
        $this->assertSame('uid', $instance->keyName);
        $this->assertSame('uname', $instance->identifierName);
    }

    public function testContracts()
    {
        $instance=new $this->myclass($this->conn);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsUserHandler', $instance);
        $this->assertInstanceOf('\Xoops\Core\Kernel\XoopsPersistableObjectHandler', $instance);
    }
}
