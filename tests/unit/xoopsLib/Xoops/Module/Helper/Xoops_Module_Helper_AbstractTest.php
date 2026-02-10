<?php
require_once(__DIR__.'/../../../../init_new.php');

class Xoops_Module_Helper_AbstractTestInstance extends Xoops\Module\Helper\HelperAbstract
{
    public function getDirname()
    {
        return $this->dirname;
    }

    public function setDirname($dir)
    {
        return parent::setDirname($dir);
    }

    public function getDebug()
    {
        return $this->debug;
    }

    public function setDebug($debug)
    {
        return parent::setDebug($debug);
    }

    public function clearModule()
    {
        $this->module = null;
    }
}

class Xoops_Module_Helper_AbstractTest extends \PHPUnit\Framework\TestCase
{
    use \DatabaseTestTrait;

    protected $myClass = 'Xoops_Module_Helper_AbstractTestInstance';

    public function test___construct()
    {
        $xoops_root_path = \XoopsBaseConfig::get('root-path');
        $dir = $xoops_root_path.'/modules/avatar';
        $instance = new $this->myClass($dir);
        $this->assertInstanceOf($this->myClass, $instance);
    }

    public function test_init()
    {
        $instance = new $this->myClass();

        $x = $instance->init();
        $this->assertSame(null, $x);
    }

    public function test_setDirname()
    {
        $instance = new $this->myClass();

        $dir = 'dirname';
        $x = $instance->setDirname($dir);
        $this->assertSame(null, $x);

        $x = $instance->getDirname();
        $this->assertSame($dir, $x);
    }

    public function test_setDebug()
    {
        $instance = new $this->myClass();

        $debug = true;
        $x = $instance->setDebug($debug);
        $this->assertSame(null, $x);

        $x = $instance->getDebug();
        $this->assertSame($debug, $x);
    }

    public function test_getInstance()
    {
        $instance = $this->myClass;
        $x = $instance::getInstance();
        $this->assertInstanceOf($this->myClass, $x);

        $y = $instance::getInstance();
        $this->assertSame($x, $y);
    }

    public function test_getModule()
    {
        $class = $this->myClass;
        $instance = $class::getInstance();

        $x = $instance->getModule();
        $this->assertInstanceOf('Xoops\Core\Kernel\Handlers\XoopsModule', $x);
    }

    public function test_xoops()
    {
        $class = $this->myClass;
        $instance = $class::getInstance();

        $x = $instance->xoops();
        $this->assertInstanceOf('Xoops', $x);
    }

    public function test_getConfig()
    {
        $this->requireDatabase();
        $class = $this->myClass;
        $instance = $class::getInstance();

        $name = 'sitename';
        $instance->setDirname('system');
        $x = $instance->getConfig($name);
        $this->assertTrue(is_string($x));
        $this->assertTrue(!empty($x));
    }

    public function test_getConfigs()
    {
        $this->requireDatabase();
        $class = $this->myClass;
        $instance = $class::getInstance();

        $name = 'sitename';
        $instance->setDirname('system');
        $x = $instance->getConfigs();
        $this->assertFalse(empty($x));
        $this->assertTrue(is_array($x));
        $this->assertArrayHasKey($name, $x);
    }

    public function test_getHandler()
    {
        $class = $this->myClass;
        $instance = $class::getInstance();

        $instance->setDirname('avatars');
        $x = $instance->getHandler('avatar');
        $this->assertInstanceOf('AvatarsAvatarHandler', $x);
        $this->assertInstanceOf('\Xoops\Core\Kernel\XoopsPersistableObjectHandler', $x);
    }

    public function test_disableCache()
    {
        $class = $this->myClass;
        $instance = $class::getInstance();

        $instance->clearModule();
        $instance->setDirname('avatars');
        $instance->disableCache();

        $x = $instance->xoops()->getModuleConfig('module_cache', 'avatars');
        $this->assertSame(array($instance->getModule()->getVar('mid') => 0), $x);

    }

    public function test_isCurrentModule()
    {
        $class = $this->myClass;
        $instance = $class::getInstance();

        $instance->clearModule();
        $modname = 'avatars';

        $instance->setDirname($modname);
        $save = $instance->xoops()->moduleDirname;
        $instance->xoops()->moduleDirname = $modname;
        $value = $instance->isCurrentModule();
        $instance->xoops()->moduleDirname = $save;
        $this->assertTrue($value);

        $save = $instance->xoops()->moduleDirname;
        $instance->xoops()->moduleDirname = 'not_found';
        $value = $instance->isCurrentModule();
        $instance->xoops()->moduleDirname = $save;
        $this->assertFalse($value);

    }

    public function test_isUserAdmin()
    {
        $class = $this->myClass;
        $instance = $class::getInstance();

        $value = $instance->isUserAdmin();
        $this->assertFalse($value);
    }

    public function test_url()
    {
        $class = $this->myClass;
        $instance = $class::getInstance();

        $name = 'dirname';
        $instance->setDirname($name);
        $x = $instance->url($name);
        $this->assertSame($name, basename($x));
        $this->assertSame($name, basename(dirname($x)));
        $this->assertSame('modules', basename(dirname(dirname(($x)))));
        // modules is the top level, anything above in URL is install dependant
        //$this->assertSame('htdocs', basename(dirname(dirname(dirname($x)))));
    }

    public function test_path()
    {
        $class = $this->myClass;
        $instance = $class::getInstance();

        $instance->setDirname('system');
        $x = $instance->path('class');
        $this->assertSame('class', basename($x));
        $this->assertSame('system', basename(dirname($x)));
        $this->assertSame('modules', basename(dirname(dirname(($x)))));
        $this->assertSame('htdocs', basename(dirname(dirname(dirname(($x))))));
        $this->assertTrue(is_dir($x));
    }

    public function test_redirect()
    {
        $this->markTestSkipped('');
    }

    public function test_loadLanguage()
    {
        $this->markTestSkipped('');
    }

    public function test_loadLocale()
    {
        $this->markTestSkipped('');
    }

    public function test_getForm()
    {
        $this->markTestSkipped('');
    }
}
