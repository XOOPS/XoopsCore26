<?php
require_once(__DIR__.'/../../../init_new.php');

use Xoops\Core\Kernel\Handlers\XoopsUser;
use Xoops\Core\Kernel\Handlers\XoopsModule;

class XoopsXmlRpcApiTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestTrait;

    protected $myclass = 'XoopsXmlRpcApi';
    protected $object = null;

    public function setUp(): void
    {
        $this->requireDatabase();
        $params = array('p1'=>'one');
        $response = new XoopsXmlRpcResponse();
        $module = new XoopsModule();
        $this->object = new $this->myclass($params, $response, $module);
    }

    protected function getPropertyValue($name)
    {
        $prop = new ReflectionProperty(get_class($this->object), $name);
        $prop->setAccessible(true);
        return $prop->getValue($this->object);
    }

    public function test___construct()
    {
        $instance = $this->object;

        $prop = $this->getPropertyValue('params');
        $this->assertTrue(is_array($prop));
        $prop = $this->getPropertyValue('response');
        $this->assertTrue(is_a($prop, 'XoopsXmlRpcResponse'));
        $prop = $this->getPropertyValue('module');
        $this->assertTrue(is_a($prop, 'Xoops\Core\Kernel\Handlers\XoopsModule'));
    }

    public function test__setUser()
    {
        $instance = $this->object;

        $user = new XoopsUser();
        $instance->_setUser($user, false);
        $prop = $this->getPropertyValue('user');
        $this->assertTrue(is_a($prop, 'Xoops\Core\Kernel\Handlers\XoopsUser'));
        $prop = $this->getPropertyValue('isadmin');
        $this->assertSame(false, $prop);

        $instance->_setUser($user, true);
        $prop = $this->getPropertyValue('isadmin');
        $this->assertSame(true, $prop);
    }

    public function test__checkUser()
    {
        $instance = $this->object;

        $name = 'name';
        $pwd = 'pwd';
        $x = $instance->_checkUser($name, $pwd);
        $this->assertSame(false, $x);
        $prop = $this->getPropertyValue('user');
        $this->assertSame(null, $prop);
    }

    public function test__checkAdmin()
    {
        $instance = $this->object;

        $x = $instance->_checkAdmin();
        $this->assertSame(false, $x);

        $user = new XoopsUser();
        $instance->_setUser($user, true);

        $x = $instance->_checkAdmin();
        $this->assertSame(true, $x);
    }

    public function test__getPostFields()
    {
        $instance = $this->object;

        $x = $instance->_getPostFields();
        $this->assertTrue(is_array($x));
        $this->assertTrue(is_array($x['title']));
        $this->assertTrue(is_array($x['hometext']));
        $this->assertTrue(is_array($x['moretext']));
        $this->assertTrue(is_array($x['categories']));
    }

    public function test__setXoopsTagMap()
    {
        $instance = $this->object;

        $tag = 'tag';
        $value = 'value';
        $instance->_setXoopsTagMap($tag, $value);
        $prop = $this->getPropertyValue('xoopsTagMap');
        $this->assertSame($value, $prop[$tag]);
    }

    public function test__getXoopsTagMap()
    {
        $instance = $this->object;

        $tag = 'tag';
        $value = 'value';
        $instance->_setXoopsTagMap($tag, $value);
        $x = $instance->_getXoopsTagMap($tag);
        $this->assertSame($value, $x);
    }

    public function test__getTagCdata()
    {
        $instance = $this->object;

        $tag = 'tag';
        $text = 'value <tag>data</tag>';
        $x = $instance->_getTagCdata($text, $tag, true);
        $this->assertSame('data', $x);
        $this->assertSame('value ', $text);

        $tag = 'tag';
        $text = 'value <tag>data</tag>';
        $text1 = $text;
        $x = $instance->_getTagCdata($text, $tag, false);
        $this->assertSame('data', $x);
        $this->assertSame($text1, $text);
    }

    public function test__getXoopsApi()
    {
        $instance = $this->object;

        $params = array();
        $x = $instance->_getXoopsApi($params);
        $this->assertInstanceOf($this->myclass, $x);
    }
}
