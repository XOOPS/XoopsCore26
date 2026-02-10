<?php
require_once(__DIR__.'/../init_new.php');

require_once(XOOPS_TU_ROOT_PATH . '/kernel/groupperm.php');

class legacy_grouppermTest extends \PHPUnit\Framework\TestCase
{

    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $instance=new \XoopsGroupPerm();
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsGroupPerm', $instance);
    }
}
