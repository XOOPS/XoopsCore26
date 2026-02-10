<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsConfigItemHandler;
use Xoops\Core\Kernel\Handlers\XoopsGroupHandler;

class JointTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $conn = null;

    protected $myClass = 'Xoops\Core\Kernel\Model\Joint';
    protected $myAbstractClass = 'Xoops\Core\Kernel\XoopsModelAbstract';

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->conn = \Xoops::getInstance()->db();
    }

    public function test___construct()
    {
        $instance=new $this->myClass();
        $this->assertInstanceOf($this->myClass, $instance);
        $this->assertInstanceOf($this->myAbstractClass, $instance);
    }

    public function test_setHandler()
    {
        $instance=new $this->myClass();
        $this->assertInstanceOf($this->myClass, $instance);
        $handler = new XoopsConfigItemHandler($this->conn);
        $result = $instance->setHandler($handler);
        $this->assertTrue($result);
    }

    public function test_getByLink()
    {
        $instance=new $this->myClass();
        $this->assertinstanceOf($this->myClass, $instance);

        $handler = new XoopsGroupHandler($this->conn);
        $result = $instance->setHandler($handler);
        $this->assertTrue($result);

        $handler->table_link=$this->conn->prefix('system_usergroup');
        $handler->field_link='groupid';
        $handler->field_object=$handler->field_link;

        $result = $instance->getByLink(null, null, true, null, null);
        $this->assertTrue(is_array($result));
        $this->assertTrue(count($result)>0);
    }

    public function test_getCountByLink()
    {
        $instance=new $this->myClass();
        $this->assertinstanceOf($this->myClass, $instance);

        $handler = new XoopsGroupHandler($this->conn);
        $result = $instance->setHandler($handler);
        $this->assertTrue($result);

        $handler->table_link=$this->conn->prefix('system_usergroup');
        $handler->field_link='groupid';
        $handler->field_object=$handler->field_link;

        $result = $instance->getCountByLink();
        $this->assertTrue(is_string($result));
        $this->assertTrue(intval($result)>=0);
    }

    public function test_getCountsByLink()
    {
        $instance=new $this->myClass();
        $this->assertinstanceOf($this->myClass, $instance);

        $handler = new XoopsGroupHandler($this->conn);
        $result = $instance->setHandler($handler);
        $this->assertTrue($result);

        $handler->table_link=$this->conn->prefix('system_usergroup');
        $handler->field_link='groupid';
        $handler->field_object=$handler->field_link;

        $result = $instance->getCountsByLink();
        $this->assertTrue(is_array($result));
        $this->assertTrue(count($result)>=0);
    }

    public function test_updateByLink()
    {
        $instance=new $this->myClass();
        $this->assertinstanceOf($this->myClass, $instance);

        $handler = new XoopsGroupHandler($this->conn);
        $result = $instance->setHandler($handler);
        $this->assertTrue($result);

        $handler->table_link=$this->conn->prefix('system_usergroup');
        $handler->field_link='groupid';
        $handler->field_object=$handler->field_link;

        $criteria=new Xoops\Core\Kernel\Criteria('l.uid', 0);
        $arrData=array('name'=>'name');
        $result = $instance->updateByLink($arrData, $criteria);
        $this->assertTrue(is_int($result));
        $this->assertTrue($result >= 0);
    }

    public function test_deleteByLink()
    {
        $instance=new $this->myClass();
        $this->assertinstanceOf($this->myClass, $instance);

        $handler = new XoopsGroupHandler($this->conn);
        $result = $instance->setHandler($handler);
        $this->assertTrue($result);

        $handler->table_link=$this->conn->prefix('system_usergroup');
        $handler->field_link='groupid';
        $handler->field_object=$handler->field_link;

        $criteria=new Xoops\Core\Kernel\Criteria('l.uid', 0);

        $result = $instance->deleteByLink($criteria);
        $this->assertTrue(is_int($result));
        $this->assertTrue($result >= 0);
    }
}
