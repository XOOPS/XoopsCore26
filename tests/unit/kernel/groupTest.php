<?php
require_once(__DIR__.'/../init_new.php');

require_once(XOOPS_TU_ROOT_PATH . '/kernel/group.php');

class legacy_groupTest extends \PHPUnit\Framework\TestCase
{

    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $instance=new \XoopsGroup();
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsGroup', $instance);
    }
}
