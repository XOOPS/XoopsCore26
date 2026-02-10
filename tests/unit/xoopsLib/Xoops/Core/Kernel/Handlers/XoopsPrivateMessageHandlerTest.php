<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsPrivateMessageHandler;
use Xoops\Core\Kernel\Handlers\XoopsPrivateMessage;

class XoopsPrivateMessageHandlerTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myclass='Xoops\Core\Kernel\Handlers\XoopsPrivateMessageHandler';
    protected $conn = null;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->conn = Xoops::getInstance()->db();
    }

    public function test___construct()
    {
        $instance=new $this->myclass($this->conn);
        $this->assertMatchesRegularExpression('/^.*system_privatemessage$/', $instance->table);
        $this->assertSame('\Xoops\Core\Kernel\Handlers\XoopsPrivateMessage', $instance->className);
        $this->assertSame('msg_id', $instance->keyName);
        $this->assertSame('subject', $instance->identifierName);
    }

    public function testContracts()
    {
        $instance=new $this->myclass($this->conn);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsPrivateMessageHandler', $instance);
        $this->assertInstanceOf('\Xoops\Core\Kernel\XoopsPersistableObjectHandler', $instance);
    }

    public function test_setRead()
    {
        $instance=new $this->myclass($this->conn);
        $msg=new XoopsPrivateMessage();
        $msg->setDirty(true);
        $msg->setNew(true);
        $msg->setVar('subject', 'PRIVMESSAGE_DUMMY_FOR_TESTS', true);
        $value=$instance->insert($msg);
        $this->assertTrue(intval($value) > 0);

        $value=$instance->setRead($msg);
        $this->assertSame(true, $value);
    }
}
