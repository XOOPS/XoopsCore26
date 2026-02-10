<?php
require_once(__DIR__.'/../init_new.php');

require_once(XOOPS_TU_ROOT_PATH . '/kernel/configitem.php');

class legacy_configitemTest extends \PHPUnit\Framework\TestCase
{

    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $instance=new \XoopsConfigItem();
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsConfigItem', $instance);
    }
}
