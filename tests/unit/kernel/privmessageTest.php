<?php
require_once(__DIR__.'/../init_new.php');

require_once(XOOPS_TU_ROOT_PATH . '/kernel/privmessage.php');

class legacy_privmessageTest extends \PHPUnit\Framework\TestCase
{

    public function setUp(): void
    {
    }

    public function test___construct()
    {
        $instance=new \XoopsPrivmessage();
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsPrivateMessage', $instance);
    }
}
