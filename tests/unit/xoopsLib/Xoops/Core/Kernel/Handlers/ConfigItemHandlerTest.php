<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsConfigItemHandler;

class ConfigItemHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass='Xoops\Core\Kernel\Handlers\XoopsConfigItemHandler';
    protected $conn = null;

    public function setUp(): void
    {
        $this->conn = Xoops::getInstance()->db();
    }

    public function test___construct()
    {
        $instance=new $this->myclass($this->conn);
        $this->assertMatchesRegularExpression('/^.*system_config$/', $instance->table);
        $this->assertSame('\Xoops\Core\Kernel\Handlers\XoopsConfigItem', $instance->className);
        $this->assertSame('conf_id', $instance->keyName);
        $this->assertSame('conf_name', $instance->identifierName);
    }

    public function testContracts()
    {
        $instance=new $this->myclass($this->conn);
        $this->assertInstanceOf('\\Xoops\\Core\\Kernel\\Handlers\\XoopsConfigItemHandler', $instance);
        $this->assertInstanceOf('\\Xoops\\Core\\Kernel\\XoopsPersistableObjectHandler', $instance);
    }
}
