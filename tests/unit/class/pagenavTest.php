<?php

use Xoops\Core\XoopsTpl;

require_once(__DIR__.'/../init_new.php');

class PagenavTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'XoopsPageNav';

    public function setUp(): void
    {
        $xoops=Xoops::getinstance();
        $tpl=$xoops->tpl();
        if (empty($tpl)) {
            $xoops->setTpl(new XoopsTpl);
        }
    }

    public function test___construct()
    {
        $total_items = 10;
        $items_perpage = 3;
        $current_start = 1;
        $start_name = 'start_name';
        $extra_args = 'extra_args';
        $instance = new $this->myclass($total_items, $items_perpage, $current_start, $start_name, $extra_args);
        $this->assertInstanceOf($this->myclass, $instance);
    }

    public function test_renderNav()
    {
        $total_items = 10;
        $items_perpage = 3;
        $current_start = 1;
        $instance = new $this->myclass($total_items, $items_perpage, $current_start);
        $this->assertInstanceOf($this->myclass, $instance);
        $ret = $instance->renderNav();
        $this->assertTrue(is_string($ret));
    }

    public function test_renderNav100()
    {
        $total_items = 10;
        $items_perpage = 3;
        $current_start = 1;
        $start_name = 'start_name';
        $instance = new $this->myclass($total_items, $items_perpage, $current_start, $start_name);
        $ret = $instance->renderNav();
        $this->assertTrue(is_string($ret));
    }
}
