<?php
require_once(__DIR__.'/../../../../../init_new.php');

class XoopsTplFileTest extends \PHPUnit\Framework\TestCase
{
    public $myclass='Xoops\Core\Kernel\Handlers\XoopsTplFile';

    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $instance=new $this->myclass();
        $this->assertInstanceOf($this->myclass, $instance);
        $value=$instance->getVars();
        $this->assertTrue(isset($value['tpl_id']));
        $this->assertTrue(isset($value['tpl_refid']));
        $this->assertTrue(isset($value['tpl_tplset']));
        $this->assertTrue(isset($value['tpl_file']));
        $this->assertTrue(isset($value['tpl_desc']));
        $this->assertTrue(isset($value['tpl_lastmodified']));
        $this->assertTrue(isset($value['tpl_lastimported']));
        $this->assertTrue(isset($value['tpl_module']));
        $this->assertTrue(isset($value['tpl_type']));
        $this->assertTrue(isset($value['tpl_source']));
    }

    public function test_id()
    {
        $instance=new $this->myclass();
        $value=$instance->id();
        $this->assertSame(null, $value);
    }

    public function test_tpl_id()
    {
        $instance=new $this->myclass();
        $value=$instance->tpl_id();
        $this->assertSame(null, $value);
    }

    public function test_tpl_refid()
    {
        $instance=new $this->myclass();
        $value=$instance->tpl_refid();
        $this->assertSame(0, $value);
    }

    public function test_tpl_tplset()
    {
        $instance=new $this->myclass();
        $value=$instance->tpl_tplset();
        $this->assertSame(null, $value);
    }

    public function test_tpl_file()
    {
        $instance=new $this->myclass();
        $value=$instance->tpl_file();
        $this->assertSame(null, $value);
    }

    public function test_tpl_desc()
    {
        $instance=new $this->myclass();
        $value=$instance->tpl_desc();
        $this->assertSame(null, $value);
    }

    public function test_tpl_lastmodified()
    {
        $instance=new $this->myclass();
        $value=$instance->tpl_lastmodified();
        $this->assertSame(0, $value);
    }

    public function test_tpl_lastimported()
    {
        $instance=new $this->myclass();
        $value=$instance->tpl_lastimported();
        $this->assertSame(0, $value);
    }

    public function tpl_module()
    {
        $instance=new $this->myclass();
        $value=$instance->tpl_module();
        $this->assertSame(null, $value);
    }

    public function test_tpl_type()
    {
        $instance=new $this->myclass();
        $value=$instance->tpl_type();
        $this->assertSame(null, $value);
    }

    public function test_tpl_source()
    {
        $instance=new $this->myclass();
        $value=$instance->tpl_source();
        $this->assertSame(null, $value);
    }

    public function test_getSource()
    {
        $instance=new $this->myclass();
        $value=$instance->getSource();
        $this->assertSame(null, $value);
    }

    public function test_getLastModified()
    {
        $instance=new $this->myclass();
        $value=$instance->getLastModified();
        $this->assertSame('0', $value);
    }
}
