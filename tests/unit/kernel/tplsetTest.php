<?php
require_once(__DIR__.'/../init_new.php');

require_once(XOOPS_TU_ROOT_PATH . '/kernel/tplset.php');

class legacy_tplsetTest extends \PHPUnit\Framework\TestCase
{

    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $instance=new \XoopsTplset();
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsTplSet', $instance);
    }
}
