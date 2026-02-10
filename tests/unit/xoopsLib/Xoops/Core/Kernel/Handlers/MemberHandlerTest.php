<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsMemberHandler;

class MemberHandlerTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myclass='Xoops\Core\Kernel\Handlers\XoopsMemberHandler';
    protected $conn = null;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->conn = Xoops::getInstance()->db();
    }

    public function test_createGroup()
    {
        $instance=new $this->myclass($this->conn);
        $value=$instance->createGroup();
        $this->assertInstanceOf('\\Xoops\\Core\\Kernel\\Handlers\\XoopsGroup', $value);
    }
    
    public function test_createUser()
    {
        $instance=new $this->myclass($this->conn);
        $value=$instance->createUser();
        $this->assertInstanceOf('\\Xoops\\Core\\Kernel\\Handlers\\XoopsUser', $value);
    }
    
    public function test_getGroup()
    {
        $instance=new $this->myclass($this->conn);
        $value=$instance->getGroup(1);
        $this->assertInstanceOf('\\Xoops\\Core\\Kernel\\Handlers\\XoopsGroup', $value);
    }
    
    public function test_getUser()
    {
        $instance=new $this->myclass($this->conn);
        $value=$instance->getUser(1);
        $this->assertInstanceOf('\\Xoops\\Core\\Kernel\\Handlers\\XoopsUser', $value);
    }
    
    public function test_deleteGroup()
    {
        $instance=new $this->myclass($this->conn);
        $value=$instance->createGroup();
        $ret=$instance->deleteGroup($value);
        $this->assertFalse($ret);
    }
    
    public function test_deleteUser()
    {
        $instance=new $this->myclass($this->conn);
        $value=$instance->createUser();
        $ret=$instance->deleteUser($value);
        $this->assertFalse($ret);
    }
}
