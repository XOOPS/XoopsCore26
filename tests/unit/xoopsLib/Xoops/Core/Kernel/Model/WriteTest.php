<?php
require_once(__DIR__.'/../../../../../init_new.php');

class WriteTest_XoopsObjectInstance extends Xoops\Core\Kernel\XoopsObject
{
}

use Xoops\Core\Kernel\Model\Write;
use Xoops\Core\Kernel\Handlers\XoopsGroupHandler;

class WriteTest extends \PHPUnit\Framework\TestCase
{
    protected $conn = null;

    protected $myClass = 'Xoops\Core\Kernel\Model\Write';
    protected $myAbstractClass = 'Xoops\Core\Kernel\XoopsModelAbstract';

    public function setUp(): void
    {
        $this->conn = \Xoops::getInstance()->db();
    }

    public function test___construct()
    {
        $instance=new $this->myClass();
        $this->assertInstanceOf($this->myClass, $instance);
        $this->assertInstanceOf($this->myAbstractClass, $instance);
    }

    public function test_cleanVars()
    {
        $instance=new $this->myClass();

        $object = new WriteTest_XoopsObjectInstance();
        $object->initVar('dummyVar1', XOBJ_DTYPE_INT, 0);
        $object->initVar('dummyVar2', XOBJ_DTYPE_INT, 0);
        $object->setVar('dummyVar1', 1);
        $object->setVar('dummyVar2', 2);
        $x = $instance->cleanVars($object);
        $this->assertSame(true, $x);
        $cleanVars = $object->cleanVars;
        $this->assertTrue(is_array($cleanVars));
        $this->assertSame(1, $cleanVars['dummyVar1']);
        $this->assertSame(2, $cleanVars['dummyVar2']);
    }

    public function test_insert()
    {
        $this->markTestIncomplete();
    }

    public function test_delete()
    {
        $this->markTestIncomplete();
    }

    public function test_deleteAll()
    {
        $instance=new $this->myClass();

        $handler = new XoopsGroupHandler($this->conn);
        $result = $instance->setHandler($handler);

        $criteria = new Criteria('groupid');

        $x = $instance->deleteAll($criteria);
        $this->assertSame(0, $x);
    }

    public function test_updateAll()
    {
        $instance=new $this->myClass();

        $handler = new XoopsGroupHandler($this->conn);
        $result = $instance->setHandler($handler);

        $criteria = new Criteria('groupid');

        $x = $instance->updateAll('fieldname', 'fieldvalue', $criteria);
        $this->assertSame(0, $x);
    }
}
