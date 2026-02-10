<?php
require_once(__DIR__.'/../init_new.php');

require_once(XOOPS_TU_ROOT_PATH . '/kernel/privmessage.php');

class legacy_privmessageHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $conn = null;

    public function setUp(): void
    {
        $this->conn = Xoops::getInstance()->db();
    }

    public function test___construct()
    {
        $instance=new \XoopsPrivmessageHandler($this->conn);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsPrivateMessageHandler', $instance);
    }
}
