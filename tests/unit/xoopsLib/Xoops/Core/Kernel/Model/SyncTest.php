<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Model\Sync;
use Xoops\Core\Kernel\Handlers\XoopsMembershipHandler;

class SyncTest extends \PHPUnit\Framework\TestCase
{
    protected $conn = null;

    protected $myClass = 'Xoops\Core\Kernel\Model\Sync';
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

    public function test_cleanOrphan()
    {
        $instance=new $this->myClass();
        $this->assertinstanceOf($this->myClass, $instance);

        $handler = new XoopsMembershipHandler($this->conn);
        $result = $instance->setHandler($handler);
        $this->assertTrue($result);

        $handler->table_link=$this->conn->prefix('system_group');
        $handler->field_link='groupid';
        $handler->field_object='groupid';

        $values=$instance->cleanOrphan();
        $this->assertTrue(is_int($values));
        $this->assertTrue($values == 0);

    }
}
