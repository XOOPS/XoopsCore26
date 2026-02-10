<?php
require_once(__DIR__.'/../../../init_new.php');

class XoopsXmlRpcStructTest extends \PHPUnit\Framework\TestCase
{
    protected $myclass = 'XoopsXmlRpcStruct';

    public function test___construct()
    {
        $x = new $this->myclass();
        $this->assertInstanceof($this->myclass, $x);
        $this->assertInstanceof('XoopsXmlRpcTag', $x);
    }

    public function test_render()
    {
        $instance = new $this->myclass();

        $value = $instance->render();
        $this->assertSame('<value><struct></struct></value>', $value);

        $instance->add('instance', clone($instance));
        $value = $instance->render();
        $expected = '<value><struct>'
            . '<member><name>instance</name>'
            . '<value><struct></struct></value>'
            . '</member></struct></value>';
        $this->assertSame($expected, $value);
    }
}
