<?php
require_once(__DIR__.'/../init_new.php');

require_once(XOOPS_TU_ROOT_PATH . '/kernel/configoption.php');

class legacy_configoptionTest extends \PHPUnit\Framework\TestCase
{

    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $instance=new \XoopsConfigOption();
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsConfigOption', $instance);
    }
}
