<?php
require_once(__DIR__.'/../../../../init_new.php');

use Xoops\Core\Database\Connection;

class XoopsObjectHandlerTestInstance extends Xoops\Core\Kernel\XoopsObjectHandler
{
    public function __construct(Connection $db)
    {
        parent::__construct($db);
    }
}

class XoopsObjectHandlerTest_XoopsObjectInstance extends Xoops\Core\Kernel\XoopsObject
{
}

class XoopsObjectHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myClass = 'XoopsObjectHandlerTestInstance';
    protected $classObject = 'XoopsObjectHandlerTest_XoopsObjectInstance';
    protected $conn = null;

    public function setUp(): void
    {
        $this->conn = Xoops\Core\Database\Factory::getConnection();
    }

    public function test___publicProperties()
    {
        $items = array('db2');
        foreach ($items as $item) {
            $prop = new ReflectionProperty($this->myClass, $item);
            $this->assertTrue($prop->isPublic());
        }
    }

    public function test___construct()
    {
        $instance=new $this->myClass($this->conn);
        $this->assertInstanceOf($this->myClass, $instance);
    }

    public function test_create()
    {
        $instance=new $this->myClass($this->conn);
        $x = $instance->create();
        $this->assertSame(null, $x);
    }

    public function test_get()
    {
        $instance=new $this->myClass($this->conn);
        $x = $instance->get(1);
        $this->assertSame(null, $x);
    }

    public function test_insert()
    {
        $instance=new $this->myClass($this->conn);
        $object=new $this->classObject();
        $x = $instance->insert($object);
        $this->assertSame(null, $x);
    }

    public function test_delete()
    {
        $instance=new $this->myClass($this->conn);
        $object=new $this->classObject();
        $x = $instance->delete($object);
        $this->assertSame(null, $x);
    }
}
