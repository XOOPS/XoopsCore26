<?php
require_once(__DIR__.'/../init_new.php');

require_once(XOOPS_TU_ROOT_PATH . '/kernel/membership.php');

class legacy_membershipTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $instance=new \XoopsMembership();
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsMembership', $instance);
    }
}
