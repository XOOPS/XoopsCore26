<?php
require_once(__DIR__.'/../../../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsConfigHandler;
use Xoops\Core\Kernel\Handlers\XoopsConfigItem;
use Xoops\Core\Kernel\Criteria;

class ConfigHandlerTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    public $myclass='Xoops\Core\Kernel\Handlers\XoopsConfigHandler';
    public $configItemClass='\Xoops\Core\Kernel\Handlers\XoopsConfigItem';
    public $configOptionClass='\Xoops\Core\Kernel\Handlers\XoopsConfigOption';

    public function setUp(): void
    {
        $this->requireDatabase();
    }

    public function test___construct()
    {
        $instance=new $this->myclass();
        $this->assertInstanceOf($this->myclass, $instance);
    }

    public function testContracts()
    {
        $instance=new $this->myclass();
        $this->assertInstanceOf('\Xoops\Core\Kernel\Handlers\XoopsConfigHandler', $instance);
    }

    public function test_createConfig()
    {
        $instance=new $this->myclass();
        $value=$instance->createConfig();
        $this->assertInstanceOf($this->configItemClass, $value);
    }

    public function test_getConfig()
    {
        $instance=new $this->myclass();
        $value=$instance->getConfig(1);
        $this->assertInstanceOf($this->configItemClass, $value);
    }

    public function test_getConfig100()
    {
        $instance=new $this->myclass();
        $value=$instance->getConfig(1, true);
        $this->assertInstanceOf($this->configItemClass, $value);
    }

    public function test_insertConfig()
    {
        $instance=new $this->myclass();
        $item=new XoopsConfigItem();
        $item->setDirty();
        $item->setNew();
        $item->setVar('conf_title', 'CONFTITLE_DUMMY_FOR_TESTS');
        $value=$instance->insertConfig($item);
        $this->assertTrue(intval($value) > 0);
    }

    public function test_deleteConfig()
    {
        $instance=new $this->myclass();
        $item=new XoopsConfigItem();
        $item->setDirty();
        $item->setNew();
        $item->setVar('conf_title', 'CONFTITLE_DUMMY_FOR_TESTS');
        $value=$instance->insertConfig($item);
        $this->assertTrue(intval($value) > 0);

        $ret=$instance->deleteConfig($item);
        $this->assertSame(true, $ret);
    }

    public function test_getConfigs()
    {
        $instance=new $this->myclass();
        $ret=$instance->getConfigs();
        $this->assertTrue(is_array($ret));
    }

    public function test_getConfigCount()
    {
        $instance=new $this->myclass();
        $ret=$instance->getConfigCount();
        $this->assertTrue(is_numeric($ret));
    }

    public function test_getConfigsByCat()
    {
        $instance=new $this->myclass();
        $ret=$instance->getConfigsByCat(1);
        $this->assertTrue(is_array($ret));
    }

    public function test_createConfigOption()
    {
        $instance=new $this->myclass();
        $value=$instance->createConfigOption();
        $this->assertInstanceOf($this->configOptionClass, $value);
    }

    public function test_getConfigOption()
    {
        $instance=new $this->myclass();
        $ret=$instance->getConfigOption(1);
        $this->assertTrue(is_object($ret));
    }

    public function test_getConfigOptions()
    {
        $instance=new $this->myclass();
        $ret=$instance->getConfigOptions();
        $this->assertTrue(is_array($ret));
    }

    public function test_getConfigOptionsCount()
    {
        $instance=new $this->myclass();
        $ret=$instance->getConfigOptionsCount();
        $this->assertTrue(is_numeric($ret));
    }

    public function test_getConfigList()
    {
        $instance=new $this->myclass();
        $ret=$instance->getConfigList(1);
        $this->assertTrue(is_array($ret));
    }

    public function test_deleteAll()
    {
        $instance=new $this->myclass();
        $criteria = new Criteria('conf_title', 'CONFTITLE_DUMMY_FOR_TESTS');
        $configs = $instance->getConfigs($criteria);
        if (is_array($configs)) {
            foreach ($configs as $config) {
                $value = $instance->deleteConfig($config);
                $this->assertTrue($value >= 1);
            }
        }
    }
}
