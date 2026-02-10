<?php
require_once(__DIR__.'/../init_new.php');

require_once(XOOPS_TU_ROOT_PATH . '/kernel/online.php');

class legacy_onlineTest extends \PHPUnit\Framework\TestCase
{

    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $instance=new \XoopsOnline();
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsOnline', $instance);
    }
}
