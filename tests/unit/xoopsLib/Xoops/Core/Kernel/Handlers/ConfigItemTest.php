<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsConfigItem;

class ConfigItemTest extends \PHPUnit\Framework\TestCase
{
    public $myclass='Xoops\Core\Kernel\Handlers\XoopsConfigItem';

    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $instance=new $this->myclass();
        $this->assertInstanceOf($this->myclass, $instance);
        $value=$instance->getVars();
        $this->assertTrue(isset($value['conf_id']));
        $this->assertTrue(isset($value['conf_modid']));
        $this->assertTrue(isset($value['conf_catid']));
        $this->assertTrue(isset($value['conf_name']));
        $this->assertTrue(isset($value['conf_title']));
        $this->assertTrue(isset($value['conf_value']));
        $this->assertTrue(isset($value['conf_desc']));
        $this->assertTrue(isset($value['conf_formtype']));
        $this->assertTrue(isset($value['conf_valuetype']));
        $this->assertTrue(isset($value['conf_order']));
    }

    public function test_id()
    {
        $instance=new $this->myclass();
        $value = $instance->id();
        $this->assertSame(null, $value);
    }

    public function test_conf_id()
    {
        $instance=new $this->myclass();
        $value = $instance->conf_id();
        $this->assertSame(null, $value);
    }

    public function test_conf_modid()
    {
        $instance=new $this->myclass();
        $value = $instance->conf_modid();
        $this->assertSame(null, $value);
    }

    public function test_conf_catid()
    {
        $instance=new $this->myclass();
        $value = $instance->conf_catid();
        $this->assertSame(null, $value);
    }

    public function test_conf_name()
    {
        $instance=new $this->myclass();
        $value = $instance->conf_name();
        $this->assertSame(null, $value);
    }

    public function test_conf_title()
    {
        $instance=new $this->myclass();
        $value = $instance->conf_title();
        $this->assertSame(null, $value);
    }

    public function test_conf_value()
    {
        $instance=new $this->myclass();
        $value = $instance->conf_value();
        $this->assertSame(null, $value);
    }

    public function test_conf_desc()
    {
        $instance=new $this->myclass();
        $value = $instance->conf_desc();
        $this->assertSame(null, $value);
    }

    public function test_conf_formtype()
    {
        $instance=new $this->myclass();
        $value = $instance->conf_formtype();
        $this->assertSame(null, $value);
    }

    public function test_conf_valuetype()
    {
        $instance=new $this->myclass();
        $value = $instance->conf_valuetype();
        $this->assertSame(null, $value);
    }

    public function test_conf_order()
    {
        $instance=new $this->myclass();
        $value = $instance->conf_order();
        $this->assertSame(null, $value);
    }
}
