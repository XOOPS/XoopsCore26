<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsOnline;

class OnlineTest extends \PHPUnit\Framework\TestCase
{
    public $myclass='Xoops\Core\Kernel\Handlers\XoopsOnline';

    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $instance=new $this->myclass();
        $this->assertInstanceOf($this->myclass, $instance);
        $value=$instance->getVars();
        $this->assertTrue(isset($value['online_uid']));
        $this->assertTrue(isset($value['online_uname']));
        $this->assertTrue(isset($value['online_updated']));
        $this->assertTrue(isset($value['online_module']));
        $this->assertTrue(isset($value['online_ip']));
    }

    public function test_online_uid()
    {
        $instance=new $this->myclass();
        $value=$instance->online_uid();
        $this->assertSame(null, $value);
    }

    public function test_online_uname()
    {
        $instance=new $this->myclass();
        $value=$instance->online_uname();
        $this->assertSame(null, $value);
    }

    public function test_online_updated()
    {
        $instance=new $this->myclass();
        $value=$instance->online_updated();
        $this->assertSame(null, $value);
    }

    public function test_online_module()
    {
        $instance=new $this->myclass();
        $value=$instance->online_module();
        $this->assertSame(null, $value);
    }

    public function test_online_ip()
    {
        $instance=new $this->myclass();
        $value=$instance->online_ip();
        $this->assertSame(null, $value);
    }
}
