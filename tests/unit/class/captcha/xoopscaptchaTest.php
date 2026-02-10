<?php
require_once(__DIR__.'/../../init_new.php');

class XoopsCaptchaTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'XoopsCaptcha';

    /**
     * @var XoopsCaptcha
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $class = $this->myclass;
        $this->object = $class::getInstance();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function test___publicProperties()
    {
        $items = array('handler','active','path_basic','path_plugin','configPath','name','config','message');
        foreach ($items as $item) {
            $prop = new ReflectionProperty($this->myclass, $item);
            $this->assertTrue($prop->isPublic());
        }
    }

    public function testGetInstance()
    {
        $this->assertInstanceOf('XoopsCaptcha', $this->object);

        $instance = $this->object;
        $this->assertTrue(is_string($instance->path_basic));
        $this->assertTrue(is_string($instance->path_plugin));
        $this->assertTrue(is_string($instance->configPath));
        $this->assertTrue(is_array($instance->config));
        $this->assertTrue(is_string($instance->name));

        $class = $this->myclass;
        $instance2 = $class::getInstance();
        $this->assertSame($this->object, $instance2);
    }

    public function testLoadConfig()
    {
        $x = $this->object->loadConfig();
        $this->assertTrue(is_array($x));
        $this->assertTrue(isset($x['disabled']));
        $this->assertTrue(isset($x['mode']));
        $this->assertTrue(isset($x['name']));
        $this->assertTrue(isset($x['skipmember']));
        $this->assertTrue(isset($x['maxattempts']));

        $x = $this->object->loadConfig('text');
        $this->assertTrue(is_array($x));
        $this->assertTrue(isset($x['num_chars']));
    }

    public function testLoadBasicConfig()
    {
        $x = $this->object->loadBasicConfig();
        $this->assertTrue(is_array($x));
        $this->assertTrue(isset($x['disabled']));
        $this->assertTrue(isset($x['mode']));
        $this->assertTrue(isset($x['name']));
        $this->assertTrue(isset($x['skipmember']));
        $this->assertTrue(isset($x['maxattempts']));
    }

    public function testReadConfig()
    {
        $x = $this->object->readConfig('captcha.config');
        $this->assertTrue(is_array($x));
        $this->assertTrue(isset($x['disabled']));
        $this->assertTrue(isset($x['mode']));
        $this->assertTrue(isset($x['name']));
        $this->assertTrue(isset($x['skipmember']));
        $this->assertTrue(isset($x['maxattempts']));
    }

    public function testWriteConfig()
    {
        $instance = $this->object;
        $filename = 'test.config';
        $x = $instance->writeConfig($filename, $instance->config);
        $this->assertTrue($x);
        $file = $instance->configPath . $filename . '.php';
        $this->assertTrue(file_exists($file));
        @unlink($file);
    }

    public function testIsActive()
    {
        $instance = $this->object;
        $instance->active = true;
        $x = $instance->isActive();
        $this->assertTrue($x);
        $instance->active = null;

        $save = $instance->config['disabled'];
        $instance->config['disabled'] = true;
        $x = $instance->isActive();
        $this->assertFalse($x);
        $instance->config['disabled'] = $save;
    }

    public function testLoadHandler()
    {
        $instance = $this->object;
        $handler = 'text';
        $x = $instance->loadHandler($handler);
        $this->assertinstanceOf('XoopsCaptchaText', $x);
    }

    public function testSetConfigs()
    {
        $instance = $this->object;
        $config = array('dummy1' => 1, 'dummy2' => 2, 'message' => 'message');
        $x = $instance->setConfigs($config);
        $this->assertTrue($x);
        $this->assertSame(1, $instance->config['dummy1']);
        $this->assertSame(2, $instance->config['dummy2']);
        $this->assertSame('message', $instance->message);
    }

    public function testVerify()
    {
        $instance = $this->object;
        $instance->active = false;
        $sessionName = $instance->name;
        $_SESSION = array();
        $_SESSION["{$sessionName}_skipmember"] = 'user1';
        $_SESSION["{$sessionName}_maxattempts"] = 11;
        $_SESSION["{$sessionName}_attempt"] = 1;
        $x = $instance->verify();
        $this->assertTrue($x);
    }

    public function testGetCaption()
    {
        $instance = $this->object;
        $x = $instance->getCaption();
        $this->assertSame(XoopsLocale::CONFIRMATION_CODE, $x);
    }

    public function testGetMessage()
    {
        $instance = $this->object;
        $instance->message = array('message1', 'message2');
        $x = $instance->getMessage();
        $this->assertSame(implode('<br />', $instance->message), $x);
    }

    public function testDestroyGarbage()
    {
        $instance = $this->object;
        $x = $instance->destroyGarbage();
        $this->assertSame(true, $x);
    }

    public function testRender()
    {
        $instance = $this->object;
        $x = $instance->render();
        $this->assertTrue(is_string($x));
    }

    public function testRenderValidationJS()
    {
        $instance = $this->object;
        $x = $instance->renderValidationJS();
        $this->assertSame('', $x);
    }

    public function testSetCode()
    {
        $instance = $this->object;
        $x = $instance->setCode();
        $this->assertSame(false, $x);
        $x = $instance->setCode('code');
        $this->assertSame(true, $x);
    }

    public function testLoadForm()
    {
        $instance = $this->object;
        $x = $instance->loadForm();
        $this->assertTrue(is_string($x));
    }
}
