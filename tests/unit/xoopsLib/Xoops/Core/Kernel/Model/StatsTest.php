<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsGroupHandler;

class StatsTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $conn = null;

    protected $myClass = 'Xoops\Core\Kernel\Model\Stats';
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

    public function test_getCount()
    {
        $instance=new $this->myClass();
        $this->assertinstanceOf($this->myClass, $instance);

        $handler = new XoopsGroupHandler($this->conn);
        $result = $instance->setHandler($handler);
        $this->assertTrue($result);

        $values=$instance->getCount();
        $this->assertTrue(is_string($values));
        $this->assertTrue(intval($values) >= 0);
    }

    public function test_getCounts()
    {
        $instance=new $this->myClass();
        $this->assertinstanceOf($this->myClass, $instance);

        $handler = new XoopsGroupHandler($this->conn);
        $result = $instance->setHandler($handler);
        $this->assertTrue($result);

        $values=$instance->getCounts();
        $this->assertTrue(is_array($values));
        $this->assertTrue(count($values) >= 0);
        if (!empty($values[1])) {
            $this->assertTrue(is_string($values[1]));
            $this->assertTrue(intval($values[1]) >= 0);
        }
    }
}
