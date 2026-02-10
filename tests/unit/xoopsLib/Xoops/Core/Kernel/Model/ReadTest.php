<?php
require_once(__DIR__.'/../../../../../init_new.php');

class ReadTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $conn = null;

    protected $myClass = 'Xoops\Core\Kernel\Model\Read';
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

    public function test_getAll()
    {
        $instance=new $this->myClass();
        $this->assertinstanceOf($this->myClass, $instance);

        $handler = new \Xoops\Core\Kernel\Handlers\XoopsGroupHandler($this->conn);
        $result = $instance->setHandler($handler);
        $this->assertTrue($result);

        $values=$instance->getAll();
        $this->assertTrue(is_array($values));
        $this->assertTrue(count($values) >= 0);
        if (!empty($values[1])) {
            $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsGroup', $values[1]);
        }
    }

    public function test_getObjects()
    {
        $instance=new $this->myClass();
        $this->assertinstanceOf($this->myClass, $instance);

        $handler = new \Xoops\Core\Kernel\Handlers\XoopsGroupHandler($this->conn);
        $result = $instance->setHandler($handler);
        $this->assertTrue($result);

        $values=$instance->getObjects();
        $this->assertTrue(is_array($values));
        $this->assertTrue(count($values) >= 0);
        if (!empty($values[1])) {
            $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsGroup', $values[1]);
        }
    }

    public function test_getList()
    {
        $instance=new $this->myClass();
        $this->assertinstanceOf($this->myClass, $instance);

        $handler = new \Xoops\Core\Kernel\Handlers\XoopsGroupHandler($this->conn);
        $result = $instance->setHandler($handler);
        $this->assertTrue($result);

        $values=$instance->getList();
        $this->assertTrue(is_array($values));
        $this->assertTrue(count($values) >= 0);
        if (!empty($values[1])) {
            $this->assertTrue(is_string($values[1]));
        }
    }

    public function test_getIds()
    {
        $instance=new $this->myClass();
        $this->assertinstanceOf($this->myClass, $instance);

        $handler = new \Xoops\Core\Kernel\Handlers\XoopsGroupHandler($this->conn);
        $result = $instance->setHandler($handler);
        $this->assertTrue($result);

        $values=$instance->getIds();
        $this->assertTrue(is_array($values));
        $this->assertTrue(count($values) >= 0);
        if (!empty($values[1])) {
            $this->assertTrue(is_string($values[1]));
            $this->assertTrue(intval($values[1]) >= 0);
        }
    }

    public function test_getRandomObject()
    {
        $instance=new $this->myClass();
        $this->assertinstanceOf($this->myClass, $instance);

        $handler = new \Xoops\Core\Kernel\Handlers\XoopsGroupHandler($this->conn);
        $result = $instance->setHandler($handler);
        $this->assertTrue($result);

        $values=$instance->getRandomObject();
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsGroup', $values);
    }
}
