<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsModule;
use Xoops\Core\Kernel\Handlers\XoopsModuleHandler;

class ModuleHandlerTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myclass='Xoops\Core\Kernel\Handlers\XoopsModuleHandler';
    protected $conn = null;
    protected $mid = 0;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->conn = Xoops::getInstance()->db();
    }

    public function test___construct()
    {
        $instance=new $this->myclass($this->conn);
        $this->assertMatchesRegularExpression('/^.*system_module$/', $instance->table);
        $this->assertSame('\Xoops\Core\Kernel\Handlers\XoopsModule', $instance->className);
        $this->assertSame('mid', $instance->keyName);
        $this->assertSame('dirname', $instance->identifierName);
    }

    public function testContracts()
    {
        $instance=new $this->myclass($this->conn);
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsModuleHandler', $instance);
        $this->assertInstanceOf('\Xoops\Core\Kernel\XoopsPersistableObjectHandler', $instance);
    }

    public function test_getById()
    {
        $instance=new $this->myclass($this->conn);
        $value=$instance->getById();
        $this->assertSame(false, $value);
    }

    public function test_getByDirname()
    {
        $instance=new $this->myclass($this->conn);
        $value=$instance->getByDirname('.');
        $this->assertSame(false, $value);
    }

    public function test_insertModule()
    {
        $instance=new $this->myclass($this->conn);
        $module=new XoopsModule();
        $module->setDirty(true);
        $module->setNew(true);
        $module->setVar('name', 'MODULE_DUMMY_FOR_TESTS', true);
        $value=$instance->insertModule($module);
        $this->assertTrue($value);

        $value=$instance->deleteModule($module);
        $this->assertTrue($value);
    }

    public function test_getObjectsArray()
    {
        $instance=new $this->myclass($this->conn);
        $value=$instance->getObjectsArray();
        $this->assertTrue(is_array($value));
    }

    public function test_getNameList()
    {
        $instance=new $this->myclass($this->conn);
        $value=$instance->getNameList();
        $this->assertTrue(is_array($value));
    }
}
